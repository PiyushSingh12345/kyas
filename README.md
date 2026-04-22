# My New Project

## TLS 1.0/1.1 hardening (security fix)

To address insecure protocol findings, this project includes an Apache TLS hardening snippet at `deploy/apache/tls-hardening.conf`.

Apply it inside the HTTPS VirtualHost for this site, then restart Apache:

- include the file in the SSL VirtualHost
- keep only `TLSv1.2` and `TLSv1.3`
- retest with your vulnerability scanner
- ensure no CBC cipher suites are enabled for TLS 1.2

Example VirtualHost line:

`Include "D:/xampp/htdocs/kyas/deploy/apache/tls-hardening.conf"`

Quick verification examples:

- `openssl s_client -connect <host>:443 -tls1` should fail
- `openssl s_client -connect <host>:443 -tls1_1` should fail
- `openssl s_client -connect <host>:443 -tls1_2` should succeed with an AEAD suite (GCM/CHACHA20)
