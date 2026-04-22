# My New Project

## TLS and server banner hardening (security fix)

To address insecure protocol findings and server-version disclosure, this project includes an Apache hardening snippet at `deploy/apache/tls-hardening.conf`.

Apply it inside the HTTPS VirtualHost for this site, then restart Apache:

- include the file in the SSL VirtualHost
- place this include after other SSL directives so it overrides weaker defaults
- keep only `TLSv1.2` and `TLSv1.3`
- set Apache to not disclose version details (`ServerTokens Prod`, `ServerSignature Off`)
- remove `X-Powered-By` response header
- retest with your vulnerability scanner
- ensure no CBC/3DES/RC4/MD5/SHA1 cipher suites are enabled for TLS 1.2

Example VirtualHost line:

`Include "D:/xampp/htdocs/kyas/deploy/apache/tls-hardening.conf"`

XAMPP Apache location (common):

- edit `D:/xampp/apache/conf/extra/httpd-ssl.conf`
- in the target `<VirtualHost _default_:443>` for this application, add:
  `Include "D:/xampp/htdocs/kyas/deploy/apache/tls-hardening.conf"`
- ensure this include is placed after other `SSLProtocol`/`SSLCipherSuite` directives
- restart Apache from XAMPP control panel

Quick verification examples:

- `openssl s_client -connect <host>:443 -tls1` should fail
- `openssl s_client -connect <host>:443 -tls1_1` should fail
- `openssl s_client -connect <host>:443 -tls1_2` should succeed with an AEAD suite (GCM/CHACHA20)
