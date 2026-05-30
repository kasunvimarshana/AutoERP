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

final class UserServiceDuplicateEmailTest extends TestCase
{
    public function testItRejectsDuplicateTenantEmail(): void
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

        $domain->method('normalizeEmail')->willReturn('duplicate@example.com');
        $domain->method('normalizeRequiredString')->willReturn('Jane');
        $transactions->method('runInTransaction')->willReturnCallback(static fn (callable $callback): mixed => $callback());

        $repository
            ->method('findByTenantAndEmail')
            ->with(4, 'duplicate@example.com')
            ->willReturn(new DataRecord(['id' => 99]));

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
            'email' => 'duplicate@example.com',
            'first_name' => 'Jane',
            'password' => 'hashed',
        ]);

        self::assertTrue($result->isFailure());
        self::assertSame('USER_DUPLICATE_EMAIL', $result->errorOrFail()->code);
    }
}
