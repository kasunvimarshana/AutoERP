<?php

namespace Tests\Feature;

use Tests\TestCase;

class ForceHttpsTest extends TestCase
{
    public function test_https_not_enforced_by_default(): void
    {
        // FORCE_HTTPS defaults to false; plain HTTP should return 200
        config(['app.force_https' => false]);

        $this->get('/')->assertStatus(200);
    }

    public function test_http_redirected_to_https_when_force_https_enabled(): void
    {
        config(['app.force_https' => true]);

        $response = $this->get('http://localhost/');

        // 301 redirect to HTTPS
        $response->assertStatus(301);
        $this->assertStringStartsWith('https://', $response->headers->get('Location'));
    }

    public function test_https_request_not_redirected_when_force_https_enabled(): void
    {
        config(['app.force_https' => true]);

        // Simulate an HTTPS request
        $response = $this->get('https://localhost/');

        $response->assertStatus(200);
    }

    public function test_hsts_header_added_on_https_response(): void
    {
        config(['app.force_https' => true]);

        $response = $this->get('https://localhost/');

        $response->assertHeader('Strict-Transport-Security');
    }

    public function test_no_hsts_header_on_plain_http_when_force_https_disabled(): void
    {
        config(['app.force_https' => false]);

        $response = $this->get('http://localhost/');

        $this->assertFalse($response->headers->has('Strict-Transport-Security'));
    }
}
