<?php

declare(strict_types=1);

namespace Tests\Unit\Auth;

use DateTimeImmutable;
use Modules\Auth\Services\TenantRefreshTokenCookie;
use Tests\TestCase;

final class RefreshTokenCookieTest extends TestCase
{
    public function test_attach_uses_the_issued_refresh_expiry_and_secure_cookie_contract(): void
    {
        config()->set('module-auth.cookies.tenant_refresh', [
            'name' => 'test_refresh_token',
            'path' => '/api/v1/auth',
            'domain' => 'example.test',
            'secure' => true,
            'same_site' => 'strict',
        ]);

        $cookieService = new TenantRefreshTokenCookie();
        $response = response()->json(['success' => true]);
        $expiresAt = new DateTimeImmutable('2026-06-26T13:30:00+00:00');

        $cookieService->attach($response, 'refresh-token-value', $expiresAt);

        $cookies = $response->headers->getCookies();
        self::assertCount(1, $cookies);

        $cookie = $cookies[0];
        self::assertSame('test_refresh_token', $cookie->getName());
        self::assertSame('refresh-token-value', $cookie->getValue());
        self::assertSame('/api/v1/auth', $cookie->getPath());
        self::assertSame('example.test', $cookie->getDomain());
        self::assertTrue($cookie->isSecure());
        self::assertTrue($cookie->isHttpOnly());
        self::assertSame('strict', $cookie->getSameSite());
        self::assertSame($expiresAt->getTimestamp(), $cookie->getExpiresTime());
    }

    public function test_extract_supports_nested_and_root_payloads_without_empty_values(): void
    {
        $cookieService = new TenantRefreshTokenCookie();

        self::assertSame('nested-token', $cookieService->extract([
            'tokens' => ['refresh_token' => ' nested-token '],
        ]));
        self::assertSame('root-token', $cookieService->extract([
            'refresh_token' => ' root-token ',
        ]));
        self::assertNull($cookieService->extract(['refresh_token' => '   ']));
        self::assertNull($cookieService->extract(null));
    }

}