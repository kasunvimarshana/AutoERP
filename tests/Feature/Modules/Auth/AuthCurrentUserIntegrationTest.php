<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Auth;

use Modules\Auth\Application\Contracts\UseCases\AuthorizeClientServiceInterface;
use Modules\Auth\Application\Contracts\UseCases\ExchangeAuthorizationCodeServiceInterface;
use Modules\Auth\Application\Contracts\UseCases\IssueTokenServiceInterface;
use Modules\Auth\Application\Contracts\UseCases\ListSessionsServiceInterface;
use Modules\Auth\Application\Contracts\UseCases\LoginServiceInterface;
use Modules\Auth\Application\Contracts\UseCases\LogoutServiceInterface;
use Modules\Auth\Application\Contracts\UseCases\RefreshTokenServiceInterface;
use Modules\Auth\Application\Contracts\UseCases\RegisterServiceInterface;
use Modules\Auth\Application\Contracts\UseCases\RequestVerificationChallengeServiceInterface;
use Modules\Auth\Application\Contracts\UseCases\RevokeSessionServiceInterface;
use Modules\Auth\Application\Contracts\UseCases\ValidateTokenServiceInterface;
use Modules\Auth\Application\Contracts\UseCases\VerifyChallengeServiceInterface;
use Modules\Auth\Application\DTOs\AuthorizeClientData;
use Modules\Auth\Application\DTOs\TokenIssueData;
use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Results\Result;
use Modules\Tenant\Application\Repositories\TenantDomainRepositoryInterface;
use Modules\Tenant\Application\Repositories\TenantRepositoryInterface;
use Modules\User\Application\Repositories\UserTenantRepositoryInterface;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Tests\TestCase;

final class AuthCurrentUserIntegrationTest extends TestCase
{
    public function testListSessionsUsesResolvedCurrentUserContext(): void
    {
        config()->set('auth.guards.auth-api.driver', 'session');

        $user = new UserModel();
        $user->id = 42;
        $user->tenant_id = 7;
        $user->organization_unit_id = 3;

        $service = $this->createMock(ListSessionsServiceInterface::class);
        $service->expects(self::once())
            ->method('listSessions')
            ->with(42, 7)
            ->willReturn(Result::success(['items' => []]));

        $this->bindProtectedAuthServices(listSessions: $service);

        $response = $this->actingAs($user, 'auth-api')
            ->getJson('/api/auth/sessions');

        $response->assertOk()
            ->assertJson(['data' => ['items' => []]]);
    }

    public function testIssueTokenOverridesClientSuppliedIdentityWithCurrentUserContext(): void
    {
        config()->set('auth.guards.auth-api.driver', 'session');

        $user = new UserModel();
        $user->id = 42;
        $user->tenant_id = 7;
        $user->organization_unit_id = 3;

        $service = $this->createMock(IssueTokenServiceInterface::class);
        $service->expects(self::once())
            ->method('issueToken')
            ->with(self::callback(function (TokenIssueData $data): bool {
                self::assertSame(42, $data->userId);
                self::assertSame(7, $data->tenantId);
                self::assertSame(3, $data->organizationUnitId);
                self::assertSame('client_credentials', $data->grantType);

                return true;
            }))
            ->willReturn(Result::success(['access_token' => 'issued-token']));

        $this->bindProtectedAuthServices(issueToken: $service, tenants: $this->tenantRepositoryWithRecords(7));

        $response = $this->actingAs($user, 'auth-api')
            ->postJson('/api/auth/token', [
                'grant_type' => 'client_credentials',
                'user_id' => 999,
                'organization_unit_id' => 777,
            ]);

        $response->assertCreated()
            ->assertJson(['data' => ['access_token' => 'issued-token']]);
    }

    public function testAuthorizeClientOverridesClientSuppliedIdentityWithCurrentUserContext(): void
    {
        config()->set('auth.guards.auth-api.driver', 'session');

        $user = new UserModel();
        $user->id = 42;
        $user->tenant_id = 7;
        $user->organization_unit_id = 3;

        $service = $this->createMock(AuthorizeClientServiceInterface::class);
        $service->expects(self::once())
            ->method('authorizeClient')
            ->with(self::callback(function (AuthorizeClientData $data): bool {
                self::assertSame(42, $data->userId);
                self::assertSame(7, $data->tenantId);
                self::assertSame(3, $data->organizationUnitId);
                self::assertSame('portal-web', $data->clientKey);

                return true;
            }))
            ->willReturn(Result::success(['authorization_code' => 'auth-code']));

        $this->bindProtectedAuthServices(authorizeClient: $service, tenants: $this->tenantRepositoryWithRecords(7));

        $response = $this->actingAs($user, 'auth-api')
            ->postJson('/api/auth/authorize-client', [
                'client_key' => 'portal-web',
                'user_id' => 999,
                'organization_unit_id' => 777,
            ]);

        $response->assertCreated()
            ->assertJson(['data' => ['authorization_code' => 'auth-code']]);
    }

    public function testCurrentUserMiddlewareRejectsCrossTenantProtectedRequests(): void
    {
        config()->set('auth.guards.auth-api.driver', 'session');

        $user = new UserModel();
        $user->id = 42;
        $user->tenant_id = 7;
        $user->organization_unit_id = 3;

        $userTenants = $this->createMock(UserTenantRepositoryInterface::class);
        $userTenants->expects(self::once())
            ->method('existsForTenantAndUser')
            ->with(888, 42)
            ->willReturn(false);

        $this->bindProtectedAuthServices(
            userTenants: $userTenants,
            tenants: $this->tenantRepositoryWithRecords(7, 888),
        );

        $response = $this->actingAs($user, 'auth-api')
            ->postJson('/api/auth/token', [
                'grant_type' => 'client_credentials',
                'tenant_id' => 888,
            ]);

        $response->assertForbidden()
            ->assertJson(['message' => 'Authenticated user cannot access the requested tenant.']);
    }

    public function testIssueTokenUsesResolvedRequestedTenantWhenUserHasAccess(): void
    {
        config()->set('auth.guards.auth-api.driver', 'session');

        $user = new UserModel();
        $user->id = 42;
        $user->tenant_id = 7;
        $user->organization_unit_id = 3;

        $service = $this->createMock(IssueTokenServiceInterface::class);
        $service->expects(self::once())
            ->method('issueToken')
            ->with(self::callback(function (TokenIssueData $data): bool {
                self::assertSame(42, $data->userId);
                self::assertSame(888, $data->tenantId);
                self::assertSame(3, $data->organizationUnitId);

                return true;
            }))
            ->willReturn(Result::success(['access_token' => 'issued-token']));

        $userTenants = $this->createMock(UserTenantRepositoryInterface::class);
        $userTenants->expects(self::atLeastOnce())
            ->method('existsForTenantAndUser')
            ->with(888, 42)
            ->willReturn(true);

        $this->bindProtectedAuthServices(
            issueToken: $service,
            userTenants: $userTenants,
            tenants: $this->tenantRepositoryWithRecords(7, 888),
        );

        $response = $this->actingAs($user, 'auth-api')
            ->postJson('/api/auth/token', [
                'grant_type' => 'client_credentials',
                'tenant_id' => 888,
            ]);

        $response->assertCreated()
            ->assertJson(['data' => ['access_token' => 'issued-token']]);
    }

    private function bindProtectedAuthServices(
        ?IssueTokenServiceInterface $issueToken = null,
        ?ListSessionsServiceInterface $listSessions = null,
        ?AuthorizeClientServiceInterface $authorizeClient = null,
        ?UserTenantRepositoryInterface $userTenants = null,
        ?TenantRepositoryInterface $tenants = null,
        ?TenantDomainRepositoryInterface $tenantDomains = null,
    ): void {
        $this->app->instance(LoginServiceInterface::class, $this->createMock(LoginServiceInterface::class));
        $this->app->instance(LogoutServiceInterface::class, $this->createMock(LogoutServiceInterface::class));
        $this->app->instance(RegisterServiceInterface::class, $this->createMock(RegisterServiceInterface::class));
        $this->app->instance(
            IssueTokenServiceInterface::class,
            $issueToken ?? $this->createMock(IssueTokenServiceInterface::class),
        );
        $this->app->instance(
            RefreshTokenServiceInterface::class,
            $this->createMock(RefreshTokenServiceInterface::class),
        );
        $this->app->instance(
            RevokeSessionServiceInterface::class,
            $this->createMock(RevokeSessionServiceInterface::class),
        );
        $this->app->instance(
            ListSessionsServiceInterface::class,
            $listSessions ?? $this->createMock(ListSessionsServiceInterface::class),
        );
        $this->app->instance(
            ValidateTokenServiceInterface::class,
            $this->createMock(ValidateTokenServiceInterface::class),
        );
        $this->app->instance(
            RequestVerificationChallengeServiceInterface::class,
            $this->createMock(RequestVerificationChallengeServiceInterface::class),
        );
        $this->app->instance(
            VerifyChallengeServiceInterface::class,
            $this->createMock(VerifyChallengeServiceInterface::class),
        );
        $this->app->instance(
            AuthorizeClientServiceInterface::class,
            $authorizeClient ?? $this->createMock(AuthorizeClientServiceInterface::class),
        );
        $this->app->instance(
            ExchangeAuthorizationCodeServiceInterface::class,
            $this->createMock(ExchangeAuthorizationCodeServiceInterface::class),
        );
        $this->app->instance(
            UserTenantRepositoryInterface::class,
            $userTenants ?? $this->createMock(UserTenantRepositoryInterface::class),
        );
        $this->app->instance(
            TenantRepositoryInterface::class,
            $tenants ?? $this->tenantRepositoryWithRecords(7),
        );
        $this->app->instance(
            TenantDomainRepositoryInterface::class,
            $tenantDomains ?? $this->createMock(TenantDomainRepositoryInterface::class),
        );
    }

    private function tenantRepositoryWithRecords(int ...$tenantIds): TenantRepositoryInterface
    {
        $repository = $this->createMock(TenantRepositoryInterface::class);

        $records = [];
        foreach ($tenantIds as $tenantId) {
            $records[$tenantId] = $this->tenantRecord($tenantId);
        }

        $repository->method('findById')
            ->willReturnCallback(static fn (int|string $id): ?DataRecord => $records[(int) $id] ?? null);

        return $repository;
    }

    private function tenantRecord(int $tenantId): DataRecord
    {
        return new DataRecord([
            'id' => $tenantId,
            'code' => 'TENANT-' . $tenantId,
            'uuid' => sprintf('00000000-0000-0000-0000-%012d', $tenantId),
            'isolation_key' => 'iso-' . $tenantId,
            'status' => 'active',
            'is_active' => true,
        ]);
    }
}
