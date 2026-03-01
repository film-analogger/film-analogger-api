resource "keycloak_group" "readers" {
    realm_id = keycloak_realm.realm.id
    name     = "readers"
}

resource "keycloak_group" "users" {
    realm_id = keycloak_realm.realm.id
    name     = "users"
    parent_id = keycloak_group.readers.id
}

resource "keycloak_group" "writers" {
    realm_id = keycloak_realm.realm.id
    name     = "writers"
    parent_id = keycloak_group.users.id
}

resource "keycloak_group" "admins" {
    realm_id = keycloak_realm.realm.id
    name     = "admins"
    parent_id = keycloak_group.writers.id
}