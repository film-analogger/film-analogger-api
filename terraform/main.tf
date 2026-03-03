
terraform {
    required_version = ">= 1.10, < 2.0"

    required_providers {
        keycloak = {
            source  = "keycloak/keycloak"
            version = "5.7"
        }
        random = {
            source  = "hashicorp/random"
            version = "3.8"
        }
    }
}


provider "keycloak" {
    client_id     = var.keycloak_realm_master_client_id
    client_secret = var.keycloak_realm_master_client_secret
    url           = var.keycloak_server_url
}