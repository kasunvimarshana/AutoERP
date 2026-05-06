<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Validation\PresenceVerifierInterface;
use Illuminate\Support\Facades\Http;
use Modules\Auth\Application\Contracts\SsoServiceInterface;
use Modules\Auth\Application\UseCases\ForgotPassword;
use Modules\Auth\Application\UseCases\GetAuthenticatedUser;
use Modules\Auth\Application\UseCases\LoginUser;
use Modules\Auth\Application\UseCases\LogoutUser;
use Modules\Auth\Application\UseCases\RefreshToken;
use Modules\Auth\Application\UseCases\RegisterUser;
use Modules\Auth\Application\UseCases\ResetPassword;
use Modules\Auth\Domain\Entities\AccessToken;
use Modules\Auth\Domain\Exceptions\AuthenticationException;
use Modules\Auth\Domain\Exceptions\InvalidCredentialsException;
use Modules\Tenant\Application\Contracts\TenantConfigClientInterface;
use Modules\Tenant\Application\Contracts\TenantConfigManagerInterface;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserModel;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Tests\TestCase;

class AuthEndpointsTest extends TestCase
{
    /** @var LoginUser&MockObject */
    private LoginUser $loginUser;

    /** @var LogoutUser&MockObject */
    private LogoutUser $logoutUser;

    /** @var RegisterUser&MockObject */
    private RegisterUser $registerUser;

    /** @var SsoServiceInterface&MockObject */
    private SsoServiceInterface $ssoService;

    /** @var GetAuthenticatedUser&MockObject */
    private GetAuthenticatedUser $getAuthenticatedUser;

    /** @var RefreshToken&MockObject */
    private RefreshToken $refreshToken;

    /** @var ForgotPassword&MockObject */
    private ForgotPassword $forgotPassword;

    /** @var ResetPassword&MockObject */
    private ResetPassword $resetPassword;

    private UserModel $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loginUser = $this->createMock(LoginUser::class);
        $this->app->instance(LoginUser::class, $this->loginUser);

        $this->logoutUser = $this->createMock(LogoutUser::class);
        $this->app->instance(LogoutUser::class, $this->logoutUser);

        $this->registerUser = $this->createMock(RegisterUser::class);
        $this->app->instance(RegisterUser::class, $this->registerUser);

        $this->ssoService = $this->createMock(SsoServiceInterface::class);
        $this->app->instance(SsoServiceInterface::class, $this->ssoService);

        $this->getAuthenticatedUser = $this->createMock(GetAuthenticatedUser::class);
        $this->app->instance(GetAuthenticatedUser::class, $this->getAuthenticatedUser);

        $this->refreshToken = $this->createMock(RefreshToken::class);
        $this->app->instance(RefreshToken::class, $this->refreshToken);

        $this->forgotPassword = $this->createMock(ForgotPassword::class);
        $this->app->instance(ForgotPassword::class, $this->forgotPassword);

        $this->resetPassword = $this->createMock(ResetPassword::class);
        $this->app->instance(ResetPassword::class, $this->resetPassword);

        $tenantConfigClient = $this->createMock(TenantConfigClientInterface::class);
        $tenantConfigClient->method('getConfig')->willReturn(null);
        $this->app->instance(TenantConfigClientInterface::class, $tenantConfigClient);

        $tenantConfigManager = $this->createMock(TenantConfigManagerInterface::class);
        $this->app->instance(TenantConfigManagerInterface::class, $tenantConfigManager);

        // Prevent real HTTP calls to HaveIBeenPwned for the uncompromised() password rule.
        Http::fake([
            'api.pwnedpasswords.com/*' => Http::response('', 404),
        ]);

        // Mock presence verifier so exists/unique rules don't query the in-memory DB.
        $presenceVerifier = $this->createMock(PresenceVerifierInterface::class);
        $presenceVerifier->method('getCount')->willReturnCallback(
            static function (string $collection, string $column): int {
                // unique:users,email — treat as unique (count = 0 means no duplicate)
                if ($collection === 'users' && $column === 'email') {
                    return 0;
                }

                // exists:tenants,id — treat tenant as existing (count >= 1)
                return 1;
            }
        );
        $presenceVerifier->method('getMultiCount')->willReturn(1);
        $this->app->instance(PresenceVerifierInterface::class, $presenceVerifier);
        $this->app['validator']->setPresenceVerifier($presenceVerifier);

        $this->user = new UserModel([
            'id' => 501,
            'tenant_id' => 7,
            'email' => 'auth.test@example.com',
            'password' => 'secret',
            'first_name' => 'Auth',
            'last_name' => 'Tester',
        ]);
        $this->user->setAttribute('id', 501);
        $this->user->setAttribute('tenant_id', 7);
    }

    // -------------------------------------------------------------------------
    // POST /auth/login
    // -------------------------------------------------------------------------

    public function test_login_returns_access_token_on_valid_credentials(): void
    {
        $token = $this->buildAccessToken();

        $this->loginUser
            ->expects($this->once())
            ->method('execute')
            ->with('auth.test@example.com', 'Password1!')
            ->willReturn($token);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'auth.test@example.com',
            'password' => 'Password1!',
        ]);

        $response->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('access_token', 'fake-token-abc')
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonStructure(['access_token', 'token_type', 'expires_in']);
    }

    public function test_login_returns_401_on_invalid_credentials(): void
    {
        $this->loginUser
            ->expects($this->once())
            ->method('execute')
            ->willThrowException(new InvalidCredentialsException);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'auth.test@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
    }

    public function test_login_returns_422_when_email_missing(): void
    {
        $this->loginUser->expects($this->never())->method('execute');

        $response = $this->postJson('/api/auth/login', ['password' => 'Password1!']);

        $response->assertStatus(HttpResponse::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['email']);
    }

    // -------------------------------------------------------------------------
    // POST /auth/register
    // -------------------------------------------------------------------------

    public function test_register_returns_201_with_access_token(): void
    {
        $token = $this->buildAccessToken();

        $this->registerUser
            ->expects($this->once())
            ->method('execute')
            ->willReturn($token);

        $response = $this->postJson('/api/auth/register', [
            'tenant_id' => 7,
            'email' => 'newuser@example.com',
            'first_name' => 'New',
            'last_name' => 'User',
            'password' => 'Password1!@',
            'password_confirmation' => 'Password1!@',
        ]);

        $response->assertStatus(HttpResponse::HTTP_CREATED)
            ->assertJsonPath('access_token', 'fake-token-abc');
    }

    public function test_register_returns_422_when_password_confirmation_missing(): void
    {
        $this->registerUser->expects($this->never())->method('execute');

        $response = $this->postJson('/api/auth/register', [
            'tenant_id' => 7,
            'email' => 'newuser@example.com',
            'first_name' => 'New',
            'last_name' => 'User',
            'password' => 'Password1!@',
        ]);

        $response->assertStatus(HttpResponse::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['password']);
    }

    // -------------------------------------------------------------------------
    // GET /auth/me
    // -------------------------------------------------------------------------

    public function test_me_returns_authenticated_user_profile(): void
    {
        $this->getAuthenticatedUser
            ->expects($this->once())
            ->method('execute')
            ->willReturn($this->user);

        $this->actingAs($this->user, (string) config('auth_context.guards.api', config('auth.defaults.guard', 'api')));

        $response = $this->getJson('/api/auth/me');

        $response->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('id', 501)
            ->assertJsonPath('email', 'auth.test@example.com')
            ->assertJsonStructure(['id', 'email', 'first_name', 'last_name']);
    }

    public function test_me_returns_401_when_user_not_resolved(): void
    {
        $this->getAuthenticatedUser
            ->expects($this->once())
            ->method('execute')
            ->willReturn(null);

        $this->actingAs($this->user, (string) config('auth_context.guards.api', config('auth.defaults.guard', 'api')));

        $response = $this->getJson('/api/auth/me');

        $response->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
    }

    // -------------------------------------------------------------------------
    // POST /auth/logout
    // -------------------------------------------------------------------------

    public function test_logout_returns_success_message(): void
    {
        $this->getAuthenticatedUser
            ->expects($this->once())
            ->method('execute')
            ->willReturn($this->user);

        $this->logoutUser
            ->expects($this->once())
            ->method('execute')
            ->with(501)
            ->willReturn(true);

        $this->actingAs($this->user, (string) config('auth_context.guards.api', config('auth.defaults.guard', 'api')));

        $response = $this->postJson('/api/auth/logout');

        $response->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('message', 'Logged out successfully');
    }

    public function test_logout_returns_401_when_user_not_resolved(): void
    {
        $this->getAuthenticatedUser
            ->expects($this->once())
            ->method('execute')
            ->willReturn(null);

        $this->logoutUser->expects($this->never())->method('execute');

        $this->actingAs($this->user, (string) config('auth_context.guards.api', config('auth.defaults.guard', 'api')));

        $response = $this->postJson('/api/auth/logout');

        $response->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
    }

    // -------------------------------------------------------------------------
    // POST /auth/refresh
    // -------------------------------------------------------------------------

    public function test_refresh_returns_new_access_token(): void
    {
        $token = $this->buildAccessToken('refreshed-token-xyz');

        $this->getAuthenticatedUser
            ->expects($this->once())
            ->method('execute')
            ->willReturn($this->user);

        $this->refreshToken
            ->expects($this->once())
            ->method('execute')
            ->with(501)
            ->willReturn($token);

        $this->actingAs($this->user, (string) config('auth_context.guards.api', config('auth.defaults.guard', 'api')));

        $response = $this->postJson('/api/auth/refresh');

        $response->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('access_token', 'refreshed-token-xyz');
    }

    public function test_refresh_returns_401_when_user_not_resolved(): void
    {
        $this->getAuthenticatedUser
            ->expects($this->once())
            ->method('execute')
            ->willReturn(null);

        $this->refreshToken->expects($this->never())->method('execute');

        $this->actingAs($this->user, (string) config('auth_context.guards.api', config('auth.defaults.guard', 'api')));

        $response = $this->postJson('/api/auth/refresh');

        $response->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
    }

    // -------------------------------------------------------------------------
    // POST /auth/forgot-password
    // -------------------------------------------------------------------------

    public function test_forgot_password_returns_generic_success_message(): void
    {
        $this->forgotPassword
            ->expects($this->once())
            ->method('execute')
            ->with('auth.test@example.com')
            ->willReturn(true);

        $response = $this->postJson('/api/auth/forgot-password', [
            'email' => 'auth.test@example.com',
        ]);

        $response->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('message', 'If that email address is registered, a password reset link has been sent.');
    }

    public function test_forgot_password_returns_same_message_even_when_email_unknown(): void
    {
        // Even if the service returns false (email not found), the same generic message is returned
        // to prevent account enumeration.
        $this->forgotPassword
            ->expects($this->once())
            ->method('execute')
            ->willReturn(false);

        $response = $this->postJson('/api/auth/forgot-password', [
            'email' => 'nobody@example.com',
        ]);

        $response->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('message', 'If that email address is registered, a password reset link has been sent.');
    }

    public function test_forgot_password_returns_422_when_email_missing(): void
    {
        $this->forgotPassword->expects($this->never())->method('execute');

        $response = $this->postJson('/api/auth/forgot-password', []);

        $response->assertStatus(HttpResponse::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['email']);
    }

    // -------------------------------------------------------------------------
    // POST /auth/reset-password
    // -------------------------------------------------------------------------

    public function test_reset_password_returns_success_message(): void
    {
        $this->resetPassword
            ->expects($this->once())
            ->method('execute')
            ->willReturn(true);

        $response = $this->postJson('/api/auth/reset-password', [
            'token' => 'valid-reset-token',
            'email' => 'auth.test@example.com',
            'password' => 'NewPassword1!',
            'password_confirmation' => 'NewPassword1!',
        ]);

        $response->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('message', 'Password has been reset successfully.');
    }

    public function test_reset_password_returns_422_on_invalid_token(): void
    {
        $this->resetPassword
            ->expects($this->once())
            ->method('execute')
            ->willThrowException(new AuthenticationException('This password reset token is invalid.'));

        $response = $this->postJson('/api/auth/reset-password', [
            'token' => 'bad-token',
            'email' => 'auth.test@example.com',
            'password' => 'NewPassword1!',
            'password_confirmation' => 'NewPassword1!',
        ]);

        $response->assertStatus(HttpResponse::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonPath('message', 'This password reset token is invalid.');
    }

    // -------------------------------------------------------------------------
    // POST /auth/sso/{provider}
    // -------------------------------------------------------------------------

    public function test_sso_exchange_returns_access_token_for_valid_passport_token(): void
    {
        $token = $this->buildAccessToken('sso-token-abc');

        $this->ssoService
            ->expects($this->once())
            ->method('exchangeToken')
            ->with('valid-sso-token', 'passport')
            ->willReturn($token);

        $response = $this->postJson('/api/auth/sso/passport', [
            'token' => 'valid-sso-token',
        ]);

        $response->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('access_token', 'sso-token-abc');
    }

    public function test_sso_exchange_returns_401_for_invalid_provider(): void
    {
        $this->ssoService
            ->expects($this->once())
            ->method('exchangeToken')
            ->willThrowException(new AuthenticationException('Unsupported SSO provider: unknown'));

        $response = $this->postJson('/api/auth/sso/unknown', [
            'token' => 'some-token',
        ]);

        $response->assertStatus(HttpResponse::HTTP_UNAUTHORIZED)
            ->assertJsonPath('message', 'Unsupported SSO provider: unknown');
    }

    public function test_sso_exchange_returns_422_when_token_missing(): void
    {
        $this->ssoService->expects($this->never())->method('exchangeToken');

        $response = $this->postJson('/api/auth/sso/passport', []);

        $response->assertStatus(HttpResponse::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['token']);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function buildAccessToken(string $token = 'fake-token-abc'): AccessToken
    {
        return new AccessToken(
            accessToken: $token,
            tokenType: 'Bearer',
            expiresIn: 1296000,
        );
    }
}
