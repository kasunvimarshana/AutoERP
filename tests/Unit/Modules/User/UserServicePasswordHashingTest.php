<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\User;

use Modules\Core\Application\Contracts\PasswordHasherInterface;
use Modules\Core\Application\Contracts\CurrentOrganizationUnitContextAccessorInterface;
use Modules\Core\Application\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\Application\Contracts\ErrorNormalizerInterface;
use Modules\Core\Application\Contracts\TransactionManagerInterface;
use Modules\Core\Application\DTO\DataRecord;
use Modules\OrganizationUnit\Application\Repositories\OrganizationUnitRepositoryInterface;
use Modules\User\Application\Repositories\UserRepositoryInterface;
use Modules\User\Application\Repositories\UserTenantRepositoryInterface;
use Modules\User\Application\UseCases\UserService;
use Modules\User\Domain\Contracts\UserDomainServiceInterface;
use PHPUnit\Framework\TestCase;

final class UserServicePasswordHashingTest extends TestCase
{
    public function testItHashesPasswordBeforePersisting(): void
    {
        $repository = $this->createMock(UserRepositoryInterface::class);
        $userTenants = $this->createMock(UserTenantRepositoryInterface::class);
        $organizationUnits = $this->createMock(OrganizationUnitRepositoryInterface::class);
        $domain = $this->createMock(UserDomainServiceInterface::class);
        $passwordHasher = $this->createMock(PasswordHasherInterface::class);
        $currentTenant = $this->createMock(CurrentTenantContextAccessorInterface::class);
        $currentOrganizationUnit = $this->createMock(CurrentOrganizationUnitContextAccessorInterface::class);
        $transactions = $this->createMock(TransactionManagerInterface::class);
        $errorNormalizer = $this->createMock(ErrorNormalizerInterface::class);

        $currentTenant->method('currentTenantId')->willReturn(4);
        $transactions->method('runInTransaction')->willReturnCallback(static fn (callable $callback): mixed => $callback());
        $domain->method('normalizeEmail')->willReturn('jane@example.com');
        $domain->method('normalizeRequiredString')->with('Jane', 'First name')->willReturn('Jane');
        $domain->method('normalizeMetadata')->willReturn(null);
        $domain->method('normalizeNullableString')->willReturnCallback(static fn ($value) => $value);
        $domain->method('normalizeStatus')->willReturn('active');

        $repository->method('findByTenantAndEmail')->willReturn(null);
        $passwordHasher->method('hash')->with('plain-secret')->willReturn('hashed-secret');

        $repository
            ->expects(self::once())
            ->method('create')
            ->with(self::callback(static function (array $payload): bool {
                return ($payload['password'] ?? null) === 'hashed-secret'
                    && ($payload['first_name'] ?? null) === 'Jane'
                    && ($payload['email'] ?? null) === 'jane@example.com';
            }))
            ->willReturn(new DataRecord(['id' => 10, 'email' => 'jane@example.com']));

        $service = new UserService(
            $repository,
            $userTenants,
            $organizationUnits,
            $domain,
            $passwordHasher,
            $currentTenant,
            $currentOrganizationUnit,
            $transactions,
            $errorNormalizer,
        );

        $result = $service->create([
            'tenant_id' => 4,
            'first_name' => 'Jane',
            'email' => 'jane@example.com',
            'password' => 'plain-secret',
        ]);

        self::assertTrue($result->isSuccess());
    }
}
