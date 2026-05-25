<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\User;

use Modules\User\Domain\Constants\UserStatus;
use Modules\User\Domain\Services\UserDomainService;
use PHPUnit\Framework\TestCase;

final class UserDomainServiceTest extends TestCase
{
    public function testItNormalizesStatusAndEmail(): void
    {
        $service = new UserDomainService();

        self::assertSame(UserStatus::ACTIVE, $service->normalizeStatus(null));
        self::assertSame(UserStatus::SUSPENDED, $service->normalizeStatus(' SUSPENDED '));
        self::assertSame('demo@example.com', $service->normalizeEmail(' Demo@Example.com '));
        self::assertSame('Jane', $service->normalizeRequiredString(' Jane ', 'First name'));
    }
}
