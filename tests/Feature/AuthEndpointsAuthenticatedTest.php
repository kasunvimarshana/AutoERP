<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Modules\Auth\Application\Contracts\AuthorizationServiceInterface;
use Modules\Auth\Application\Contracts\SsoServiceInterface;
use Modules\Auth\Application\UseCases\ForgotPassword;
use Modules\Auth\Application\UseCases\GetAuthenticatedUser;
use Modules\Auth\Application\UseCases\LoginUser;
use Modules\Auth\Application\UseCases\LogoutUser;
use Modules\Auth\Application\UseCases\RefreshToken;
use Modules\Auth\Application\UseCases\RegisterUser;
use Modules\Auth\Application\UseCases\ResetPassword;
use Modules\Auth\Domain\Entities\AccessToken;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserModel;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Tests\TestCase;

class AuthEndpointsAuthenticatedTest extends TestCase
{
    private static bool $passportKeysPrepared = false;

    private UserModel $authUser;

    /** @var GetAuthenticatedUser&MockObject */
    private GetAuthenticatedUser $getAuthenticatedUser;

    /** @var RefreshToken&MockObject */
    private RefreshToken $refreshToken;

    /** @var LogoutUser&MockObject */
    private LogoutUser $logoutUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->preparePassportKeys();

        $this->authUser = new UserModel([
            'name' => 'Auth User',
            'email' => 'auth.user@example.com',
            'password' => 'hashed',
            'first_name' => 'Auth',
            'last_name' => 'User',
            'status' => 'active',
        ]);
        $this->authUser->setAttribute('id', 99);
        $this->authUser->setAttribute('tenant_id', 1);

        $authorizationService = $this->createMock(AuthorizationServiceInterface::class);
        $authorizationService->method('can')->willReturn(true);
        $this->app->instance(AuthorizationServiceInterface::class, $authorizationService);

        $this->app->instance(LoginUser::class, $this->createMock(LoginUser::class));
        $this->logoutUser = $this->createMock(LogoutUser::class);
        $this->app->instance(LogoutUser::class, $this->logoutUser);
        $this->app->instance(RegisterUser::class, $this->createMock(RegisterUser::class));
        $this->app->instance(SsoServiceInterface::class, $this->createMock(SsoServiceInterface::class));

        $this->getAuthenticatedUser = $this->createMock(GetAuthenticatedUser::class);
        $this->getAuthenticatedUser->method('execute')->willReturn($this->authUser);
        $this->app->instance(GetAuthenticatedUser::class, $this->getAuthenticatedUser);

        $this->refreshToken = $this->createMock(RefreshToken::class);
        $this->refreshToken->method('execute')->willReturn(new AccessToken(
            accessToken: 'refreshed-token',
            tokenType: 'Bearer',
            expiresIn: 3600,
            refreshToken: 'new-refresh-token',
            scopes: ['*'],
        ));
        $this->app->instance(RefreshToken::class, $this->refreshToken);

        $this->app->instance(ForgotPassword::class, $this->createMock(ForgotPassword::class));
        $this->app->instance(ResetPassword::class, $this->createMock(ResetPassword::class));
    }

    private function actingAsUser(): static
    {
        return $this->actingAs($this->authUser, (string) config('auth_context.guards.api', config('auth.defaults.guard', 'api')));
    }

    public function test_logout_returns_success_for_authenticated_user(): void
    {
        $response = $this->actingAsUser()->postJson('/api/auth/logout');

        $response->assertStatus(HttpResponse::HTTP_OK);
        $response->assertJsonPath('message', 'Logged out successfully');
    }

    public function test_me_returns_authenticated_user_payload(): void
    {
        $response = $this->actingAsUser()->getJson('/api/auth/me');

        $response->assertStatus(HttpResponse::HTTP_OK);
        $response->assertJsonPath('id', 99);
        $response->assertJsonPath('email', 'auth.user@example.com');
        $response->assertJsonPath('first_name', 'Auth');
        $response->assertJsonPath('last_name', 'User');
        $response->assertJsonPath('status', 'active');
    }

    public function test_refresh_returns_new_access_token_payload(): void
    {
        $response = $this->actingAsUser()->postJson('/api/auth/refresh');

        $response->assertStatus(HttpResponse::HTTP_OK);
        $response->assertJsonPath('access_token', 'refreshed-token');
        $response->assertJsonPath('token_type', 'Bearer');
        $response->assertJsonPath('expires_in', 3600);
        $response->assertJsonPath('refresh_token', 'new-refresh-token');
    }

    private function preparePassportKeys(): void
    {
        if (self::$passportKeysPrepared) {
            return;
        }

        Artisan::call('passport:keys', ['--force' => true]);

        self::$passportKeysPrepared = true;
    }
}
