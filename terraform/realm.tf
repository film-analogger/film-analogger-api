resource "keycloak_realm" "realm" {
  realm             = "film-analogger"
  enabled           = true
  display_name      = "Film Analogger"
  display_name_html = "<b>Film Analogger</b>"

  login_theme = "keycloak.v2"
  account_theme = "keycloak.v3"
  admin_theme = "keycloak.v2"

  access_code_lifespan = "1h"

  # TODO: "all" in prod 
  ssl_required    = "external"
  password_policy = "upperCase(1) and length(8) and forceExpiredPasswordChange(365) and notUsername"

  reset_password_allowed = false
  remember_me = false
  login_with_email_allowed = true
  verify_email = true

  smtp_server {
    host = "smtp-relay.gmail.com"
    from = var.email_from_address

    auth {
      username = var.email_smtp_username
      password = var.email_smtp_password
    }
  }

  internationalization {
    supported_locales = [
      "en",
      "fr",
    ]
    default_locale    = "en"
  }

  security_defenses {
    headers {
      x_frame_options                     = "DENY"
      content_security_policy             = "frame-src 'self'; frame-ancestors 'self'; object-src 'none';"
      content_security_policy_report_only = ""
      x_content_type_options              = "nosniff"
      x_robots_tag                        = "none"
      x_xss_protection                    = "1; mode=block"
      strict_transport_security           = "max-age=31536000; includeSubDomains"
    }
    brute_force_detection {
      permanent_lockout                 = false
      max_login_failures                = 5
      wait_increment_seconds            = 60
      quick_login_check_milli_seconds   = 1000
      minimum_quick_login_wait_seconds  = 60
      max_failure_wait_seconds          = 900
      failure_reset_time_seconds        = 43200
    }
  }

  web_authn_policy {
    relying_party_entity_name = "Example"
    relying_party_id          = "keycloak.example.com"
    signature_algorithms      = ["ES256", "RS256"]
  }

  sso_session_idle_timeout = "384h0m0s"
  sso_session_max_lifespan = "384h0m0s"

  offline_session_idle_timeout = "720h0m0s"


  access_code_lifespan_login = "4h0m0s"
  access_code_lifespan_user_action = "10m0s"
}