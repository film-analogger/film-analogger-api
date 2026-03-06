resource "random_password" "client-api-client-secret" {
    length           = 16
    special          = true
    override_special = "%*=+-_"
}

resource "keycloak_openid_client" "client-api" {
    realm_id  = keycloak_realm.realm.id
    client_id = "film-analogger-api"

    name    = "Film Analogger API"
    enabled = true

    description = "OpenID client for the Film Analogger API, an api to manage analog process"

    access_type           = "BEARER-ONLY"
    standard_flow_enabled = false

    client_secret = random_password.client-api-client-secret.result

}

resource "keycloak_role" "api_role_data_reader" {
    realm_id    = keycloak_realm.realm.id
    client_id   = keycloak_openid_client.client-api.id
    name        = "data_reader"
    description = "Read data from the Film Analogger API"
}

resource "keycloak_role" "api_role_user" {
    realm_id    = keycloak_realm.realm.id
    client_id   = keycloak_openid_client.client-api.id
    name        = "user"
    description = "User role for the Film Analogger API"

    composite_roles = [
        keycloak_role.api_role_data_reader.id,
    ]

}

resource "keycloak_role" "api_role_data_writer" {
    realm_id    = keycloak_realm.realm.id
    client_id   = keycloak_openid_client.client-api.id
    name        = "data_writer"
    description = "Write data to the Film Analogger API"

    composite_roles = [
        keycloak_role.api_role_data_reader.id,
        keycloak_role.api_role_user.id,
    ]
}

resource "keycloak_role" "api_role_admin" {
    realm_id    = keycloak_realm.realm.id
    client_id   = keycloak_openid_client.client-api.id
    name        = "admin"
    description = "Manage the Film Analogger API"
    
    composite_roles = [
        keycloak_role.api_role_data_writer.id,
    ]
}


output "client-api" {
    value = {
         client_id     = keycloak_openid_client.client-api.client_id
         client_secret = random_password.client-api-client-secret.result
    }
    sensitive = true
}


resource "keycloak_openid_client" "client-api-swagger" {
    realm_id  = keycloak_realm.realm.id
    client_id = "film-analogger-api-swagger"

    name    = "Film Analogger API Swagger UI"
    enabled = true

    description = "OpenID client for the Film Analogger API Swagger UI"

    access_type           = "PUBLIC"
    standard_flow_enabled = true

    valid_redirect_uris = [
        "http://localhost:1080*",
    ]

    web_origins = [
        "http://localhost:1080",
    ]

    root_url = "http://localhost:1080"

    access_token_lifespan = "3600"
}