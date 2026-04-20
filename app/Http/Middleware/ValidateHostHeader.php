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
}
