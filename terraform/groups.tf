resource "keycloak_group" "readers" {
    realm_id = keycloak_realm.realm.id
    name     = "readers"
}

resource "keycloak_group_roles" "readers_roles" {
    realm_id = keycloak_realm.realm.id
    group_id = keycloak_group.readers.id
    role_ids  = [
        keycloak_role.api_role_data_reader.id
    ]
}

resource "keycloak_group" "users" {
    realm_id = keycloak_realm.realm.id
    name     = "users"
    parent_id = keycloak_group.readers.id
}


resource "keycloak_group_roles" "users_roles" {
    realm_id = keycloak_realm.realm.id
    group_id = keycloak_group.users.id
    role_ids  = [
        keycloak_role.api_role_user.id
    ]
}


resource "keycloak_group" "writers" {
    realm_id = keycloak_realm.realm.id
    name     = "writers"
    parent_id = keycloak_group.users.id
}


resource "keycloak_group_roles" "writers_roles" {
    realm_id = keycloak_realm.realm.id
    group_id = keycloak_group.writers.id
    role_ids  = [
        keycloak_role.api_role_data_writer.id
    ]
}


resource "keycloak_group" "admins" {
    realm_id = keycloak_realm.realm.id
    name     = "admins"
    parent_id = keycloak_group.writers.id
}


resource "keycloak_group_roles" "admins_roles" {
    realm_id = keycloak_realm.realm.id
    group_id = keycloak_group.admins.id
    role_ids  = [
        keycloak_role.api_role_admin.id
    ]
}
