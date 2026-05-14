# My New Project

## TLS and server banner hardening (security fix)

### Apache 2 (production): `kyas-ssl.conf`

Use the self-contained vhost at `deploy/apache/kyas-ssl.conf` (no `Include` of other project snippets). Copy it to the server, for example:

- `/etc/apache2/sites-available/kyas-ssl.conf`

Then enable SSL, enable the site, adjust `ServerName`, `DocumentRoot`, certificate paths, run `apache2ctl configtest`, and reload Apache.

### Apache2 global TLS enforcement (recommended)

To ensure scanner findings are fixed even on default/fallback SSL vhosts, deploy:

- `deploy/apache/apache2-ssl-policy.conf` -> `/etc/apache2/conf-available/zzz-kyas-ssl-policy.conf`

The `zzz-` prefix makes Apache load this **after** `mods-enabled/ssl.conf` (which often defaults to permissive cipher classes like `HIGH:MEDIUM`). If a weaker `SSLCipherSuite` or `SSLProtocol` is applied **later** than this policy, scanners will still report SWEET32 / TLS 1.0 until that override is fixed.

Enable it and ensure the intended SSL site is active:

- `sudo a2enconf zzz-kyas-ssl-policy`
- `sudo a2dissite default-ssl` (if not needed; it frequently reintroduces TLS 1.0 and 3DES)
- `sudo a2ensite kyas-ssl`
- `sudo apache2ctl configtest && sudo systemctl reload apache2`

Optional banner hardening (server-wide): `deploy/apache/tls-server-hardening.conf` for `ServerTokens Prod` and removing `X-Powered-By`.

### XAMPP (local Windows)

In `D:/xampp/apache/conf/extra/httpd-ssl.conf`, inside the `<VirtualHost _default_:443>` (or the `<VirtualHost *:443>` that actually serves your hostname), add **at the very end of the vhost** (after any existing `SSLProtocol` / `SSLCipherSuite` lines):

`Include "D:/xampp/htdocs/kyas/deploy/apache/tls-hardening.conf"`

If `httpd-ssl.conf` (or another included file) has `SSLProtocol` / `SSLCipherSuite` directives **after** this `Include`, they will override the hardening snippet and scanners will still report TLS 1.0/1.1, BEAST, SWEET32, and weak ciphers. In that case, either move the `Include` to the bottom, or replace the entire vhost using:

- `deploy/apache/kyas-xampp-ssl-vhost.conf`

Then restart Apache from the XAMPP control panel.

Scan the **same host and port** your app uses for HTTPS (a load balancer or another node may still show old TLS if not updated).

Use this to confirm which vhost answers on `:443`:

- `sudo apache2ctl -S`

Quick verification examples:

- `openssl s_client -connect <host>:443 -tls1` should fail
- `openssl s_client -connect <host>:443 -tls1_1` should fail
- `openssl s_client -connect <host>:443 -tls1_2` should succeed with an AEAD suite (GCM/CHACHA20)

On the **Apache server** (Linux images usually have `openssl`), paste the same `SSLCipherSuite` string from `deploy/apache/apache2-ssl-policy.conf` into:

- `openssl ciphers -V '<paste here>'`

There must be **no** suites whose names contain `DES-CBC3`, `3DES`, or `DES-CBC` (aside from the letters inside unrelated algorithm names such as `AES`).

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
