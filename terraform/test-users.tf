

resource "random_password" "user_test_reader_password" {
    length           = 16
    special          = true
    override_special = "!@#$%*=+?"
}

resource "keycloak_user" "user_test_reader" {
    realm_id = keycloak_realm.realm.id
    username = "test_reader"
    enabled  = true

    email      = "test_reader@example.test"
    first_name = "Alice"
    last_name  = "Alinson"

    email_verified = true

    attributes = {
        "locale" = "fr"
    }

    initial_password {
        value     = random_password.user_test_reader_password.result
        temporary = false
    }
}

resource "keycloak_user_groups" "user_test_reader_groups" {
    realm_id = keycloak_realm.realm.id
    user_id  = keycloak_user.user_test_reader.id
    group_ids = [
        keycloak_group.readers.id,
    ]
}



resource "random_password" "user_test_user_password" {
    length           = 16
    special          = true
    override_special = "!@#$%*=+?"
}

resource "keycloak_user" "user_test_user" {
    realm_id = keycloak_realm.realm.id
    username = "test_user"
    enabled  = true

    email      = "test_user@example.test"
    first_name = "Bob"
    last_name  = "Bobinson"

    email_verified = true

    attributes = {
        "locale" = "fr"
    }

    initial_password {
        value     = random_password.user_test_user_password.result
        temporary = false
    }
}

resource "keycloak_user_groups" "user_test_user_groups" {
    realm_id = keycloak_realm.realm.id
    user_id  = keycloak_user.user_test_user.id
    group_ids = [
        keycloak_group.users.id,
    ]
}

resource "random_password" "user_test_writer_password" {
    length           = 16
    special          = true
    override_special = "!@#$%*=+?"
}

resource "keycloak_user" "user_test_writer" {
    realm_id = keycloak_realm.realm.id
    username = "test_writer"
    enabled  = true

    email      = "test_writer@example.test"
    first_name = "Carol"
    last_name  = "Carolinson"

    email_verified = true

    attributes = {
        "locale" = "fr"
    }

    initial_password {
        value     = random_password.user_test_writer_password.result
        temporary = false
    }
}

resource "keycloak_user_groups" "user_test_writer_groups" {
    realm_id = keycloak_realm.realm.id
    user_id  = keycloak_user.user_test_writer.id
    group_ids = [
        keycloak_group.writers.id,
    ]
}



resource "random_password" "user_test_admin_password" {
    length           = 16
    special          = true
    override_special = "!@#$%*=+?"
}

resource "keycloak_user" "user_test_admin" {
    realm_id = keycloak_realm.realm.id
    username = "test_admin"
    enabled  = true

    email      = "test_admin@example.test"
    first_name = "Dave"
    last_name  = "Davidson"

    email_verified = true

    attributes = {
        "locale" = "fr"
    }

    initial_password {
        value     = random_password.user_test_admin_password.result
        temporary = false
    }
}

resource "keycloak_user_groups" "user_test_admin_groups" {
    realm_id = keycloak_realm.realm.id
    user_id  = keycloak_user.user_test_admin.id
    group_ids = [
        keycloak_group.admins.id,
    ]
}




output "test_users" {

    sensitive = true

    value = {
        test_reader = {
            username = keycloak_user.user_test_reader.username
            password = random_password.user_test_reader_password.result
        }
        test_user = {
            username = keycloak_user.user_test_user.username
            password = random_password.user_test_user_password.result
        }
        test_writer = {
            username = keycloak_user.user_test_writer.username
            password = random_password.user_test_writer_password.result
        }
        test_admin = {
            username = keycloak_user.user_test_admin.username
            password = random_password.user_test_admin_password.result
        }
    }
}