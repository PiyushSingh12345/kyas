<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class ValidateHostHeader
{
    /**
     * Ensure incoming requests are only served for trusted hostnames.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $host = Str::lower($request->getHost());

        if (! $this->isTrustedHost($host)) {
            abort(Response::HTTP_BAD_REQUEST, 'Invalid Host header.');
        }

        return $next($request);
    }

    /**
     * Determine whether the resolved host is allowed.
     */
    protected function isTrustedHost(string $host): bool
    {
        foreach ($this->trustedHosts() as $trustedHostPattern) {
            if (Str::is($trustedHostPattern, $host)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Build the trusted host patterns from config and environment.
     *
     * Supports wildcard patterns like "*.example.com".
     *
     * @return array<int, string>
     */
    protected function trustedHosts(): array
    {
        $configuredHosts = config('app.trusted_hosts', []);

        if (! is_array($configuredHosts)) {
            $configuredHosts = [];
        }

        $appUrlHost = parse_url((string) config('app.url'), PHP_URL_HOST);

        $hosts = array_filter([
            ...$configuredHosts,
            is_string($appUrlHost) ? $appUrlHost : null,
        ]);

        $hosts = [
            ...$hosts,
            ...$this->implicitTrustedHosts(is_string($appUrlHost) ? $appUrlHost : ''),
        ];

        if (app()->environment(['local', 'testing'])) {
            $hosts = [
                ...$hosts,
                'localhost',
                '127.0.0.1',
                '::1',
            ];
        }

        $hosts = array_map(
            static fn (string $host) => Str::lower(trim($host)),
            $hosts
        );

        return array_values(array_unique(array_filter($hosts)));
    }

    /**
     * Hosts that should be trusted without listing them in TRUSTED_HOSTS:
     * the address Apache/PHP bound for this request, and IPs that DNS maps
     * from APP_URL (so requests using the site's public IP as Host still match DNS for APP_URL).
     *
     * @return array<int, string>
     */
    protected function implicitTrustedHosts(string $appUrlHost): array
    {
        $implicit = [];

        $serverAddr = $_SERVER['SERVER_ADDR'] ?? null;
        if (is_string($serverAddr) && $serverAddr !== '' && filter_var($serverAddr, FILTER_VALIDATE_IP)) {
            $implicit[] = $serverAddr;
        }

        if ($appUrlHost !== '' && ! filter_var($appUrlHost, FILTER_VALIDATE_IP)) {
            foreach ($this->resolvedIpsForHostname($appUrlHost) as $ip) {
                $implicit[] = $ip;
            }
        }

        return $implicit;
    }

    /**
     * @return array<int, string>
     */
    protected function resolvedIpsForHostname(string $hostname): array
    {
        $ips = [];

        if (function_exists('dns_get_record')) {
            $records = @dns_get_record($hostname, DNS_A | DNS_AAAA) ?: [];
            foreach ($records as $record) {
                if (! empty($record['ip']) && is_string($record['ip'])) {
                    $ips[] = $record['ip'];
                }
                if (! empty($record['ipv6']) && is_string($record['ipv6'])) {
                    $ips[] = $record['ipv6'];
                }
            }
        }

        if ($ips === []) {
            $fallback = @gethostbyname($hostname);
            if (is_string($fallback) && $fallback !== $hostname && filter_var($fallback, FILTER_VALIDATE_IP)) {
                $ips[] = $fallback;
            }
        }

        return array_values(array_unique($ips));
    }
}
