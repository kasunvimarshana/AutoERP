<?php

namespace Tests\Unit\Auth;

use Illuminate\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Mockery;
use Modules\Auth\Application\UseCases\LoginUseCase;
use Modules\Auth\Application\UseCases\LogoutUseCase;
use Modules\Auth\Application\UseCases\RefreshTokenUseCase;
use Modules\Auth\Domain\Contracts\AuthServiceInterface;
use Modules\Auth\Domain\Events\TokenRefreshed;
use Modules\Auth\Domain\Events\UserLoggedIn;
use Modules\Auth\Domain\Events\UserLoggedOut;
use Modules\Auth\Domain\ValueObjects\AuthToken;
use Modules\Auth\Domain\ValueObjects\LoginCredentials;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Auth module use cases.
 *
 * Covers login, logout, and token refresh flows including domain event dispatch.
 *
 * Domain events use the Dispatchable trait which calls app(Dispatcher::class)
 * directly. We register a dispatcher mock in the Container so that call resolves
 * correctly.
 */
class AuthUseCaseTest extends TestCase
{
    private \Mockery\MockInterface $dispatcher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dispatcher = Mockery::mock(Dispatcher::class);
        Container::getInstance()->instance(Dispatcher::class, $this->dispatcher);
        Container::getInstance()->instance('events', $this->dispatcher);
        // LoginUseCase calls request()->ip(); bind a stub request so the helper resolves.
        Container::getInstance()->instance('request', new \Illuminate\Http\Request());
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeToken(): AuthToken
    {
        return new AuthToken(
            accessToken: 'access-token-abc',
            tokenType: 'Bearer',
            expiresIn: 3600,
            refreshToken: 'refresh-token-xyz',
        );
    }

    // -------------------------------------------------------------------------
    // LoginUseCase
    // -------------------------------------------------------------------------

    public function test_login_delegates_to_auth_service_and_dispatches_event(): void
    {
        $token = $this->makeToken();

        $authService = Mockery::mock(AuthServiceInterface::class);
        $authService->shouldReceive('login')
            ->once()
            ->withArgs(fn (LoginCredentials $c) => $c->email === 'user@example.com'
                && $c->deviceName === 'web')
            ->andReturn($token);

        $this->dispatcher->shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($e) => $e instanceof UserLoggedIn
                && $e->userEmail === 'user@example.com');

        $useCase = new LoginUseCase($authService);
        $result = $useCase->execute([
            'email'    => 'user@example.com',
            'password' => 'secret',
        ]);

        $this->assertSame('access-token-abc', $result->accessToken);
        $this->assertSame('Bearer', $result->tokenType);
        $this->assertSame(3600, $result->expiresIn);
    }

    public function test_login_uses_provided_device_name(): void
    {
        $token = $this->makeToken();

        $authService = Mockery::mock(AuthServiceInterface::class);
        $authService->shouldReceive('login')
            ->once()
            ->withArgs(fn (LoginCredentials $c) => $c->deviceName === 'mobile-app')
            ->andReturn($token);

        $this->dispatcher->shouldReceive('dispatch')->once();

        $useCase = new LoginUseCase($authService);
        $result = $useCase->execute([
            'email'       => 'user@example.com',
            'password'    => 'secret',
            'device_name' => 'mobile-app',
        ]);

        $this->assertSame('access-token-abc', $result->accessToken);
    }

    // -------------------------------------------------------------------------
    // LogoutUseCase
    // -------------------------------------------------------------------------

    public function test_logout_delegates_to_auth_service_and_dispatches_event(): void
    {
        $authService = Mockery::mock(AuthServiceInterface::class);
        $authService->shouldReceive('logout')
            ->once()
            ->with('user-uuid-1');

        $this->dispatcher->shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($e) => $e instanceof UserLoggedOut
                && $e->userId === 'user-uuid-1');

        $useCase = new LogoutUseCase($authService);
        $useCase->execute(['user_id' => 'user-uuid-1']);

        $this->assertTrue(true); // explicit assertion to suppress risky-test warning
    }

    // -------------------------------------------------------------------------
    // RefreshTokenUseCase
    // -------------------------------------------------------------------------

    public function test_refresh_token_delegates_to_auth_service_and_dispatches_event(): void
    {
        $newToken = new AuthToken(
            accessToken: 'new-access-token',
            tokenType: 'Bearer',
            expiresIn: 3600,
            refreshToken: 'new-refresh-token',
        );

        $authService = Mockery::mock(AuthServiceInterface::class);
        $authService->shouldReceive('refresh')
            ->once()
            ->with('old-refresh-token')
            ->andReturn($newToken);

        $this->dispatcher->shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($e) => $e instanceof TokenRefreshed
                && $e->userId === 'user-uuid-1');

        $useCase = new RefreshTokenUseCase($authService);
        $result = $useCase->execute([
            'refresh_token' => 'old-refresh-token',
            'user_id'       => 'user-uuid-1',
        ]);

        $this->assertSame('new-access-token', $result->accessToken);
        $this->assertSame('new-refresh-token', $result->refreshToken);
    }

    public function test_auth_token_to_array_returns_expected_keys(): void
    {
        $token = $this->makeToken();
        $array = $token->toArray();

        $this->assertArrayHasKey('access_token', $array);
        $this->assertArrayHasKey('token_type', $array);
        $this->assertArrayHasKey('expires_in', $array);
        $this->assertArrayHasKey('refresh_token', $array);
        $this->assertSame('access-token-abc', $array['access_token']);
        $this->assertSame('Bearer', $array['token_type']);
        $this->assertSame(3600, $array['expires_in']);
    }
}
