<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Tests;

use InvalidArgumentException;
use Modules\OrganizationUnit\Support\OrganizationUnitNameKey;
use PHPUnit\Framework\TestCase;

final class OrganizationUnitNameKeyTest extends TestCase
{
    public function test_it_builds_a_stable_case_insensitive_key(): void
    {
        self::assertSame(
            OrganizationUnitNameKey::from('  Regional Office  '),
            OrganizationUnitNameKey::from('regional office'),
        );
        self::assertSame(64, strlen(OrganizationUnitNameKey::from('Regional Office')));
    }

    public function test_it_rejects_an_empty_name(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('non-empty name');

        OrganizationUnitNameKey::from('   ');
    }
}
