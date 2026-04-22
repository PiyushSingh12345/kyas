<?php

namespace Tests\Feature;

use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    public function test_root_response_has_clickjacking_protection_headers(): void
    {
        $response = $this->get('/');

        $response->assertHeader('X-Frame-Options', 'DENY');

        $contentSecurityPolicy = $response->headers->get('Content-Security-Policy', '');
        $this->assertStringContainsString("frame-ancestors 'none'", $contentSecurityPolicy);
    }

    public function test_api_response_has_clickjacking_protection_headers(): void
    {
        $response = $this->getJson('/api/reappropriations');

        $response->assertHeader('X-Frame-Options', 'DENY');

        $contentSecurityPolicy = $response->headers->get('Content-Security-Policy', '');
        $this->assertStringContainsString("frame-ancestors 'none'", $contentSecurityPolicy);
    }

    public function test_root_response_has_recommended_security_headers(): void
    {
        $response = $this->withHeaders([
            'X-Forwarded-Proto' => 'https',
        ])->withServerVariables([
            'HTTPS' => 'on',
            'SERVER_PORT' => 443,
        ])->get('/');

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('Referrer-Policy', 'no-referrer');
        $response->assertHeader('Strict-Transport-Security', 'max-age=63072000; includeSubDomains; preload');

        $contentSecurityPolicy = $response->headers->get('Content-Security-Policy', '');
        $this->assertStringContainsString("default-src 'self'", $contentSecurityPolicy);
    }
}
