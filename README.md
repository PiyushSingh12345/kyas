# My New Project

## TLS and server banner hardening (security fix)

### Apache 2 (production): `kyas-ssl.conf`

Use the self-contained vhost at `deploy/apache/kyas-ssl.conf` (no `Include` of other project snippets). Copy it to the server, for example:

- `/etc/apache2/sites-available/kyas-ssl.conf`

Then enable SSL, enable the site, adjust `ServerName`, `DocumentRoot`, certificate paths, run `apache2ctl configtest`, and reload Apache.

Optional banner hardening (server-wide): `deploy/apache/tls-server-hardening.conf` — include once from `apache2.conf` or a conf-enabled fragment if you want `ServerTokens Prod` and no `X-Powered-By`.

### XAMPP (local Windows)

TLS policy is inlined in `conf/extra/httpd-ssl.conf` (no Include of repo files). Restart Apache from the XAMPP control panel after changes.

Scan the **same host and port** your app uses for HTTPS (a load balancer or another node may still show old TLS if not updated).

Quick verification examples:

- `openssl s_client -connect <host>:443 -tls1` should fail
- `openssl s_client -connect <host>:443 -tls1_1` should fail
- `openssl s_client -connect <host>:443 -tls1_2` should succeed with an AEAD suite (GCM/CHACHA20)

## CORS origin hardening (security fix)

To prevent wildcard CORS (`Access-Control-Allow-Origin: *`), configure explicit origins using:

- `CORS_ALLOWED_ORIGINS` in `.env` (comma-separated)
- full origins are recommended (including scheme)

Example production value:

`CORS_ALLOWED_ORIGINS=https://app.example.com,https://admin.example.com`

If this variable is empty, the middleware falls back to `APP_URL` and `TRUSTED_HOSTS`.

Verification commands:

- Allowed origin should return that same origin:
  `curl -i -H "Origin: https://app.example.com" https://<host>/`
- Disallowed origin should not receive `Access-Control-Allow-Origin`:
  `curl -i -H "Origin: https://evil.example" https://<host>/`
- Header must never be wildcard:
  `curl -i -H "Origin: https://app.example.com" https://<host>/ | findstr /I "Access-Control-Allow-Origin"`
