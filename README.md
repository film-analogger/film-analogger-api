# Film Analogger API

REST and GraphQL API for managing analog photography data: films, manufacturers, chemistries,
development processes and users.

Built with Symfony 8.1 + API Platform 4.3 on top of MongoDB, served by FrankenPHP and secured by
Keycloak (OAuth2 bearer tokens).

## Table of contents

- [Stack](#stack)
- [Requirements](#requirements)
- [Getting started](#getting-started)
- [Keycloak setup (Terraform)](#keycloak-setup-terraform)
- [Database initialization](#database-initialization)
- [Fixtures](#fixtures)
- [API](#api)
    - [Authentication](#authentication)
    - [Roles](#roles)
    - [Resources](#resources)
    - [Internationalization](#internationalization)
    - [Avatar upload](#avatar-upload)
- [Tests](#tests)
- [Code style and commits](#code-style-and-commits)
- [Project structure](#project-structure)
- [Environment variables](#environment-variables)
- [License](#license)

## Stack

| Concern        | Technology                              |
| -------------- | --------------------------------------- |
| Language       | PHP 8.5+                                |
| Framework      | Symfony 8.1                             |
| API            | API Platform 4.3 (REST + GraphQL)       |
| Database       | MongoDB (Doctrine ODM)                  |
| Auth           | Keycloak 26 (OAuth2 / OpenID Connect)   |
| Server         | FrankenPHP (Caddy)                      |
| i18n           | Gedmo Translatable (`en`, `fr`)         |
| Infrastructure | Docker Compose, Terraform for Keycloak  |

## Requirements

- Docker and Docker Compose
- Terraform >= 1.10 (to provision the Keycloak realm)
- Yarn 4 (only for linting and git hooks)

Everything else runs inside containers — no local PHP installation is needed.

## Getting started

```bash
# Build and start the stack (API, MongoDB, Keycloak, PostgreSQL)
docker compose up -d --build

# Install git hooks and lint tooling
yarn install
```

Once started:

| Service            | URL                                              |
| ------------------ | ------------------------------------------------ |
| API / Swagger UI   | http://localhost:1080                            |
| GraphQL playground | http://localhost:1080/graphql                    |
| Keycloak admin     | http://localhost:8080 (`admin` / `!ChangeMe!`)   |
| MongoDB            | `mongodb://api-platform:!ChangeMe!@localhost:27017` |

The dev port is `1080` (see `compose.override.yaml`). Override it with the `HTTP_PORT` environment
variable if needed.

## Keycloak setup (Terraform)

The `film-analogger` realm, its clients, roles, groups and test users are managed as code in
`terraform/`.

Before running Terraform, create a client in the Keycloak `master` realm so Terraform can
authenticate against the Keycloak admin API:

- **Settings**: Client type `OpenID Connect`, Client ID `terraform`
- **Capability config**: `Client authentication` ON, `Standard flow` OFF, `Direct access grants`
  OFF, `Service accounts roles` ON
- **Service accounts roles**: assign the realm role `admin` (the master realm's composite admin
  role), so Terraform can create the `film-analogger` realm and manage its clients, roles, groups
  and users
- **Credentials**: copy the generated client secret

```bash
cd terraform
cp terraform.tfvars.dist terraform.tfvars   # then fill in the values
terraform init
terraform apply
```

`terraform.tfvars` needs at least `keycloak_realm_master_client_secret`, which is the secret of the
`terraform` client created in the Keycloak `master` realm.

Retrieve the generated credentials:

```bash
terraform output -json client-api    # client_id / client_secret for the API
terraform output -json test_users    # test users and their passwords
```

Copy the API client secret into `.env.local` as `OAUTH_KEYCLOAK_CLIENT_SECRET`.

Terraform provisions four test users (`test_reader`, `test_user`, `test_writer`, `test_admin`),
one per group, so every permission level can be exercised locally.

## Database initialization

Create the MongoDB collections, indexes and validation rules for the mapped documents:

```bash
docker compose exec php bin/console doctrine:mongodb:schema:create
```

If the document mapping changes later (new field, new index, ...), update the existing schema
instead of recreating it:

```bash
docker compose exec php bin/console doctrine:mongodb:schema:update
```

There is no migrations bundle in this project — MongoDB ODM has no equivalent of Doctrine
migrations, indexes and validation rules are just reconciled in place by `schema:update`.

## Fixtures

```bash
docker compose exec php bin/console doctrine:mongodb:fixtures:load
```

Loads manufacturers, films, chemistry types and chemistries, with their English and French
translations.

## API

### Authentication

All endpoints except `/`, `/docs` and the OpenAPI documents require a Keycloak bearer token:

```
Authorization: Bearer <access_token>
```

Tokens are validated against the realm JWKS. The Swagger UI is wired to the
`film-analogger-api-swagger` public client, so you can log in directly from the docs page with the
**Authorize** button and use the authorization code flow.

To get a token from the CLI:

```bash
curl -X POST 'http://localhost:8080/realms/film-analogger/protocol/openid-connect/token' \
  -d 'client_id=film-analogger-api-swagger' \
  -d 'grant_type=password' \
  -d 'username=test_admin' \
  -d 'password=<password from terraform output>'
```

On the first authenticated request, an `AppUser` document is provisioned automatically from the
token claims.

### Roles

Roles are Keycloak client roles, mapped to Symfony roles with a `ROLE_` prefix. They are composite,
each one inheriting the previous:

| Role                | Grants                                              |
| ------------------- | --------------------------------------------------- |
| `ROLE_data_reader`  | Read films, manufacturers, chemistries, users        |
| `ROLE_user`         | `data_reader` + edit your own profile                |
| `ROLE_data_writer`  | `user` + create, update and delete catalog data      |
| `ROLE_admin`        | `data_writer` + administration                       |

Corresponding Keycloak groups: `readers`, `users`, `writers`, `admins`.

### Resources

| Resource        | Operations                                                          | Notes                          |
| --------------- | ------------------------------------------------------------------- | ------------------------------ |
| `Film`          | `GET` (item + collection), `POST`, `PATCH`, `DELETE`                | Translatable, timestampable    |
| `Manufacturer`  | `GET` (item + collection), `POST`, `PATCH`, `DELETE`                | Translatable, timestampable    |
| `Chemistry`     | `GET` (item + collection), `POST`, `PATCH`, `DELETE`                | Translatable, embeds dilutions |
| `ChemistryType` | `GET` (item + collection), `POST`, `PATCH`, `DELETE`                | Translatable                   |
| `AppUser`       | `GET` (item + collection), `PATCH`, `POST /app_users/{id}/avatar`   | Patch limited to your own user |

Supported formats: `application/ld+json` (JSON-LD/Hydra, default), `application/json` and
`multipart/form-data` for uploads.

### Internationalization

Available locales are `en` (default) and `fr`. Pick one per request with either header:

```
X-LOCALE: fr
Accept-Language: fr-FR,fr;q=0.9,en;q=0.8
```

Responses carry `Content-Language` and advertise the available locales in `Accept-Language`.
Unknown locales fall back to the default one. Translatable fields are also exposed as a
`translations` object so a client can read every locale in a single call.

### Avatar upload

```bash
curl -X POST 'http://localhost:1080/app_users/{id}/avatar' \
  -H 'Authorization: Bearer <token>' \
  -F 'avatarFile=@avatar.jpg'
```

Files are stored under `public/avatars` and served from `/avatars`.

## Tests

```bash
# Full suite
docker compose exec php bin/phpunit

# One suite
docker compose exec php bin/phpunit --testsuite=Api
docker compose exec php bin/phpunit --testsuite=Unit

# One file
docker compose exec php bin/phpunit tests/Api/DataApi/FilmTest.php
```

`tests/Api` holds integration tests that issue real HTTP requests, `tests/Unit` holds unit tests.
API tests extend `AbstractFilmTestCase`, which provides document factories and pre-authenticated
clients (`loggedClientAdmin()`, `loggedClientDataWriter()`, `loggedClientDataReader()`, …). In the
`test` environment Keycloak is replaced by mocks (`src/Security/Mock/`), so no running Keycloak is
required.

The suite runs automatically on `pre-push` via Husky.

## Code style and commits

```bash
yarn lint <files>   # prettier + @prettier/plugin-php
```

Git hooks (Husky):

- `pre-commit` — Prettier on staged PHP files
- `commit-msg` — Commitizen validates the message
- `pre-push` — clears the test cache and runs PHPUnit

Commits follow [Conventional Commits](https://www.conventionalcommits.org/); use `cz commit` to be
guided. Releases are cut with `cz bump`, which updates `composer.json`,
`config/packages/api_platform.yaml` and `CHANGELOG.md`.

## Project structure

```
src/
  Document/          # MongoDB documents, API resources declared via attributes
  Document/Trait/    # TimestampableBlameableTrait, TranslatableTrait
  DataFixtures/      # Seed data
  Security/          # Keycloak authenticator, user provider, roles, test mocks
  EventListener/     # Locale, translations, Gedmo extensions, user provisioning
  Serializer/        # Serialization groups and custom (de)normalizers
  State/             # API Platform processors (avatar upload)
  OpenApi/           # OpenAPI/Swagger customization
  Repository/        # Doctrine ODM repositories
  POPO/              # Plain PHP objects (TranslatedField)
config/              # Symfony configuration
terraform/           # Keycloak realm as code
tests/Api/           # HTTP integration tests
tests/Unit/          # Unit tests
frankenphp/          # Caddyfile and PHP ini overrides
```

## Environment variables

Defaults live in `.env`; put local overrides in `.env.local` (git-ignored).

| Variable                            | Description                                            |
| ----------------------------------- | ------------------------------------------------------ |
| `APP_ENV`                           | `dev`, `test` or `prod`                                |
| `APP_SECRET`                        | Symfony secret                                         |
| `MONGODB_URI` / `MONGODB_DB`        | MongoDB connection                                     |
| `CORS_ALLOW_ORIGIN`                 | Allowed origins regex                                  |
| `OAUTH_KEYCLOAK_ISSUER`             | Public Keycloak URL                                    |
| `OAUTH_KEYCLOAK_ISSUER_DEV_OVERRIDE`| Internal Keycloak URL used from inside the containers   |
| `OAUTH_KEYCLOAK_REALM`              | Realm name (`film-analogger`)                          |
| `OAUTH_KEYCLOAK_CLIENT_ID`          | API client id                                          |
| `OAUTH_KEYCLOAK_CLIENT_SECRET`      | API client secret (from `terraform output`)            |
| `OAUTH_KEYCLOAK_CLIENT_ID_SWAGGER`  | Public client used by the Swagger UI                   |

## License

MIT — see [LICENCE](LICENCE).
