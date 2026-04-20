<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Apply security headers including CSP to reduce injection impact.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $headers = $response->headers;
        $isLocal = app()->environment(['local', 'testing']);

        $scriptSources = ["'self'", "'unsafe-inline'", "'unsafe-eval'"];
        $styleSources = ["'self'", "'unsafe-inline'"];
        $connectSources = ["'self'", 'ws:', 'wss:'];

        // Allow Vite dev server/HMR only in local/testing.
        if ($isLocal) {
            $scriptSources = array_merge($scriptSources, ['http://127.0.0.1:5173', 'http://localhost:5173']);
            $styleSources = array_merge($styleSources, ['http://127.0.0.1:5173', 'http://localhost:5173']);
            $connectSources = array_merge($connectSources, [
                'http://127.0.0.1:5173',
                'http://localhost:5173',
                'ws://127.0.0.1:5173',
                'ws://localhost:5173',
            ]);
        }

        // Compatibility-safe CSP for current frontend stack (enforced).
        $enforcedDirectives = [
            "default-src 'self'",
            "base-uri 'self'",
            "object-src 'none'",
            "frame-ancestors 'self'",
            "form-action 'self'",
            'script-src ' . implode(' ', $scriptSources),
            'style-src ' . implode(' ', $styleSources),
            "img-src 'self' data: blob: https:",
            "font-src 'self' data: https:",
            'connect-src ' . implode(' ', $connectSources),
            "frame-src 'self'",
        ];
        if (! $isLocal) {
            $enforcedDirectives[] = 'upgrade-insecure-requests';
        }
        $enforcedCsp = implode('; ', $enforcedDirectives);

        if (! $headers->has('Content-Security-Policy')) {
            $headers->set('Content-Security-Policy', $enforcedCsp);
        }

        // Stricter CSP in report-only mode for safe phased rollout.
        $reportOnlyDirectives = [
            "default-src 'self'",
            "base-uri 'self'",
            "object-src 'none'",
            "frame-ancestors 'self'",
            "form-action 'self'",
            $isLocal
                ? "script-src 'self' http://127.0.0.1:5173 http://localhost:5173"
                : "script-src 'self'",
            $isLocal
                ? "style-src 'self' http://127.0.0.1:5173 http://localhost:5173"
                : "style-src 'self'",
            "img-src 'self' data: blob: https:",
            "font-src 'self' data: https:",
            $isLocal
                ? "connect-src 'self' ws: wss: http://127.0.0.1:5173 http://localhost:5173 ws://127.0.0.1:5173 ws://localhost:5173"
                : "connect-src 'self' ws: wss:",
            "frame-src 'self'",
            "report-uri /csp-report",
        ];
        if (! $isLocal) {
            $reportOnlyDirectives[] = 'upgrade-insecure-requests';
        }
        $reportOnlyCsp = implode('; ', $reportOnlyDirectives);

        if (! $headers->has('Content-Security-Policy-Report-Only')) {
            $headers->set('Content-Security-Policy-Report-Only', $reportOnlyCsp);
        }

        if (! $headers->has('X-Content-Type-Options')) {
            $headers->set('X-Content-Type-Options', 'nosniff');
        }

        if (! $headers->has('X-Frame-Options')) {
            $headers->set('X-Frame-Options', 'SAMEORIGIN');
        }

        if (! $headers->has('Referrer-Policy')) {
            $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        }

        if (! $headers->has('Permissions-Policy')) {
            $headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        }

        return $response;
    }
}

