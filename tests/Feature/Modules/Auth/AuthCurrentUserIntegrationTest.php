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
use Modules\OrganizationUnit\Application\Repositories\OrganizationUnitRepositoryInterface;
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

        $this->bindProtectedAuthServices(
            listSessions: $service,
            organizationUnits: $this->organizationUnitRepositoryWithRecords([3 => 7]),
        );

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

        $this->bindProtectedAuthServices(
            issueToken: $service,
            tenants: $this->tenantRepositoryWithRecords(7),
            organizationUnits: $this->organizationUnitRepositoryWithRecords([3 => 7]),
        );

        $response = $this->actingAs($user, 'auth-api')
            ->postJson('/api/auth/token', [
                'grant_type' => 'client_credentials',
                'user_id' => 999,
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

        $this->bindProtectedAuthServices(
            authorizeClient: $service,
            tenants: $this->tenantRepositoryWithRecords(7),
            organizationUnits: $this->organizationUnitRepositoryWithRecords([3 => 7]),
        );

        $response = $this->actingAs($user, 'auth-api')
            ->postJson('/api/auth/authorize-client', [
                'client_key' => 'portal-web',
                'user_id' => 999,
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
            organizationUnits: $this->organizationUnitRepositoryWithRecords([3 => 7]),
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
                self::assertSame(8883, $data->organizationUnitId);

                return true;
            }))
            ->willReturn(Result::success(['access_token' => 'issued-token']));

        $userTenants = $this->createMock(UserTenantRepositoryInterface::class);
        $userTenants->expects(self::atLeastOnce())
            ->method('existsForTenantAndUser')
            ->with(888, 42)
            ->willReturn(true);
        $userTenants->expects(self::once())
            ->method('existsForTenantUserAndOrganizationUnit')
            ->with(888, 42, 8883)
            ->willReturn(true);

        $this->bindProtectedAuthServices(
            issueToken: $service,
            userTenants: $userTenants,
            tenants: $this->tenantRepositoryWithRecords(7, 888),
            organizationUnits: $this->organizationUnitRepositoryWithRecords([3 => 7, 8883 => 888]),
        );

        $response = $this->actingAs($user, 'auth-api')
            ->postJson('/api/auth/token', [
                'grant_type' => 'client_credentials',
                'tenant_id' => 888,
                'organization_unit_id' => 8883,
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
        ?OrganizationUnitRepositoryInterface $organizationUnits = null,
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
        $this->app->instance(
            OrganizationUnitRepositoryInterface::class,
            $organizationUnits ?? $this->organizationUnitRepositoryWithRecords([3 => 7]),
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

    /**
     * @param array<int, int> $organizationUnits [organizationUnitId => tenantId]
     */
    private function organizationUnitRepositoryWithRecords(
        array $organizationUnits,
    ): OrganizationUnitRepositoryInterface {
        $repository = $this->createMock(OrganizationUnitRepositoryInterface::class);

        $records = [];
        foreach ($organizationUnits as $organizationUnitId => $tenantId) {
            $records[(int) $organizationUnitId] = $this->organizationUnitRecord(
                (int) $organizationUnitId,
                (int) $tenantId,
            );
        }

        $repository->method('findById')
            ->willReturnCallback(static fn (int|string $id): ?DataRecord => $records[(int) $id] ?? null);

        $repository->method('findByTenantAndCode')
            ->willReturnCallback(static function (int|string $tenantId, string $code) use ($records): ?DataRecord {
                foreach ($records as $record) {
                    $matchesTenant = (int) $record->get('tenant_id') === (int) $tenantId;
                    $matchesCode = (string) $record->get('code') === trim($code);
                    if ($matchesTenant && $matchesCode) {
                        return $record;
                    }
                }

                return null;
            });

        $repository->method('findByTenantAndPath')
            ->willReturnCallback(static function (int|string $tenantId, string $path) use ($records): ?DataRecord {
                foreach ($records as $record) {
                    $matchesTenant = (int) $record->get('tenant_id') === (int) $tenantId;
                    $matchesPath = (string) $record->get('path') === trim($path);
                    if ($matchesTenant && $matchesPath) {
                        return $record;
                    }
                }

                return null;
            });

        $repository->method('findByTenantAndName')
            ->willReturnCallback(static function (int|string $tenantId, string $name) use ($records): ?DataRecord {
                foreach ($records as $record) {
                    $matchesTenant = (int) $record->get('tenant_id') === (int) $tenantId;
                    $matchesName = (string) $record->get('name') === trim($name);
                    if ($matchesTenant && $matchesName) {
                        return $record;
                    }
                }

                return null;
            });

        return $repository;
    }

    private function organizationUnitRecord(int $organizationUnitId, int $tenantId): DataRecord
    {
        return new DataRecord([
            'id' => $organizationUnitId,
            'tenant_id' => $tenantId,
            'name' => 'Organization Unit ' . $organizationUnitId,
            'code' => 'OU-' . $organizationUnitId,
            'path' => '/ou/' . $organizationUnitId,
            'is_active' => true,
        ]);
    }
}
