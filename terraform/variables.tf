variable "keycloak_realm_master_client_secret" {
    description = "The client secret for the Keycloak master realm client used by Terraform to authenticate with Keycloak. This should be set to the value of the 'terraform' client secret in Keycloak."
    type        = string
    sensitive   = true
}


variable "keycloak_realm_master_client_id" {
    description = "The client ID for the Keycloak master realm client used by Terraform to authenticate with Keycloak. This should be set to the value of the 'terraform' client ID in Keycloak."
    type        = string
    default     = "terraform"
}


variable "keycloak_server_url" {
    description = "The URL for the Keycloak server used by Terraform to authenticate with Keycloak."
    type        = string
    default     = "http://localhost:8080"
}

variable "email_from_address" {
    description = "The email address used as the sender in the SMTP server configuration."
    type        = string
    default     = "example@example.com"
}

variable "email_smtp_username" {
    description = "The username for the SMTP server used by Terraform to send emails."
    type        = string
    default     = "example@example.com"
}

variable "email_smtp_password" {
    description = "The password for the SMTP server used by Terraform to send emails."
    type        = string
    default     = "password"
}
