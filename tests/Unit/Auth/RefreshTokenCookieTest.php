<?php

declare(strict_types=1);

namespace Tests\Unit\Auth;

use Illuminate\Support\Carbon;
use Modules\Auth\Services\RefreshTokenCookie;
use Tests\TestCase;

final class RefreshTokenCookieTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_attach_adds_configured_secure_http_only_refresh_cookie(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-23 12:00:00 UTC'));

        config()->set('module-auth.refresh_token_ttl_seconds', 3600);
        config()->set('module-auth.web_refresh_cookie', [
            'name' => 'test_refresh_token',
            'path' => '/api',
            'domain' => 'example.test',
            'secure' => true,
            'same_site' => 'strict',
        ]);

        $response = response()->json(['success' => true]);

        (new RefreshTokenCookie())->attach($response, 'refresh-token-value');

        $cookies = $response->headers->getCookies();

        $this->assertCount(1, $cookies);

        $cookie = $cookies[0];
        $this->assertSame('test_refresh_token', $cookie->getName());
        $this->assertSame('refresh-token-value', $cookie->getValue());
        $this->assertSame('/api', $cookie->getPath());
        $this->assertSame('example.test', $cookie->getDomain());
        $this->assertTrue($cookie->isSecure());
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertFalse($cookie->isRaw());
        $this->assertSame('strict', $cookie->getSameSite());
        $this->assertSame(Carbon::now()->addHour()->getTimestamp(), $cookie->getExpiresTime());
    }

    public function test_extract_supports_nested_and_root_token_payloads_without_exposing_empty_values(): void
    {
        $cookie = new RefreshTokenCookie();

        $this->assertSame('nested-token', $cookie->extract([
            'tokens' => ['refresh_token' => ' nested-token '],
        ]));
        $this->assertSame('root-token', $cookie->extract([
            'refresh_token' => ' root-token ',
        ]));
        $this->assertNull($cookie->extract([
            'refresh_token' => '   ',
        ]));
        $this->assertNull($cookie->extract(null));
    }
}
