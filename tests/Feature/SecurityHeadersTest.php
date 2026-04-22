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
}
