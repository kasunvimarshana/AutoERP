<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\User;

use Modules\Core\Application\Contracts\PasswordHasherInterface;
use Modules\Core\Application\DTO\DataRecord;
use Modules\User\Application\Repositories\UserRepositoryInterface;
use Modules\User\Application\UseCases\UserService;
use Modules\User\Domain\Contracts\UserDomainServiceInterface;
use PHPUnit\Framework\TestCase;

final class UserServiceDuplicateEmailTest extends TestCase
{
    public function testItRejectsDuplicateTenantEmail(): void
    {
        $repository = $this->createMock(UserRepositoryInterface::class);
        $domain = $this->createMock(UserDomainServiceInterface::class);
        $passwordHasher = $this->createMock(PasswordHasherInterface::class);

        $domain->method('normalizeEmail')->willReturn('duplicate@example.com');

        $repository
            ->method('findByTenantAndEmail')
            ->with(4, 'duplicate@example.com')
            ->willReturn(new DataRecord(['id' => 99]));

        $service = new UserService($repository, $domain, $passwordHasher);

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
