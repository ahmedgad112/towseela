<?php

namespace Tests\Feature;

use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    public function test_responses_include_security_headers_by_default(): void
    {
        $this->seedRoles();

        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Cross-Origin-Opener-Policy', 'same-origin');
        $this->assertNotEmpty($response->headers->get('Permissions-Policy'));
    }

    public function test_security_headers_can_be_disabled_via_server_config(): void
    {
        $this->seedRoles();

        config([
            'towseela.security.headers_enabled' => false,
        ]);

        $response = $this->get(route('login'));

        $response->assertOk();
        $this->assertNull($response->headers->get('X-Frame-Options'));
        $this->assertNull($response->headers->get('X-Content-Type-Options'));
    }
}
