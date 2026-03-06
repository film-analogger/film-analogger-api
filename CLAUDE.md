# Film Analogger API

## Project Overview

REST/GraphQL API for managing analog photography data (films, manufacturers, chemistry, users). Built with Symfony 8.0, API Platform 4.2, MongoDB (Doctrine ODM), and Keycloak OAuth2 authentication.

## Tech Stack

- **Language**: PHP 8.5+
- **Framework**: Symfony 8.0 + API Platform 4.2
- **Database**: MongoDB via Doctrine ODM
- **Auth**: Keycloak (OAuth2 Bearer tokens)
- **Server**: FrankenPHP
- **Infrastructure**: Docker Compose, Terraform (for Keycloak)

## Development Environment

Everything runs inside Docker. Never assume local PHP is available.

```bash
# Start the project
docker compose up -d

# Run tests
docker compose exec php bin/phpunit

# Run a specific test suite
docker compose exec php bin/phpunit --testsuite=Api
docker compose exec php bin/phpunit --testsuite=Unit

# Symfony console
docker compose exec php bin/console <command>

# Load fixtures
docker compose exec php bin/console doctrine:mongodb:fixtures:load

# Format code
yarn lint
```

## Project Structure

```
src/
  Document/          # MongoDB document entities (Film, Manufacturer, Chemistry, etc.)
  Document/Trait/    # Shared traits (TimestampableBlameableTrait, TranslatableTrait)
  DataFixtures/      # Test/seed data
  Security/          # Keycloak auth (authenticator, user provider, roles)
  EventListener/     # Symfony event subscribers (locale, translations, user provisioning)
  OpenApi/           # Swagger/OpenAPI customization
  Repository/        # Doctrine ODM repositories
  Serializer/        # Serialization group constants
  POPO/              # Plain Old PHP Objects (TranslatedField)
tests/
  Api/               # Integration tests (HTTP requests against API)
  Unit/              # Unit tests
config/              # Symfony configuration (security, api_platform, doctrine_mongodb, etc.)
terraform/           # Keycloak infrastructure as code
```

## Coding Conventions

- **Formatting**: Prettier with `@prettier/plugin-php` (config in `.prettierrc`)
- **Standard**: PSR-12 naming conventions
- **Commits**: Conventional Commits (feat:, fix:, chore:, docs:, refactor:, perf:, test:)
- **Language**: English for all code, comments, and documentation

## Key Patterns

- **API Resources**: Defined via PHP attributes on Document classes (API Platform style), not controllers
- **Security**: Role-based access control with `#[ApiResource(security: '...')]` attributes
  - Roles: `ROLE_data_reader`, `ROLE_data_writer`, `ROLE_admin`, `ROLE_user`
- **Serialization**: Uses named groups (`read-film`, `write-film`, etc.) defined in `SerializationGroups.php`
- **i18n**: Multilingual support via Gedmo Translatable (English + French)
- **Traits**: `TimestampableBlameableTrait` (createdAt/updatedAt/createdBy/updatedBy) and `TranslatableTrait` — ask before applying to new entities
- **Tests**: Extend `AbstractFilmTestCase` which provides factory methods and logged-in client helpers (`loggedClientAdmin()`, `loggedClientDataWriter()`, etc.)
- **Setters**: Chainable (return `$this`)

## Rules

- Always run tests after making code changes
- Never push to remote without asking for confirmation
- Ask which security roles to apply when creating new API endpoints
- Ask whether to include traits (Timestampable, Translatable) when creating new entities

## Off-Limits (ask before modifying)

- Security configuration (`config/packages/security.yaml`, `src/Security/`)
- Docker/infrastructure files (`Dockerfile`, `compose.yaml`, `compose.override.yaml`, `terraform/`)
- Keycloak configuration
