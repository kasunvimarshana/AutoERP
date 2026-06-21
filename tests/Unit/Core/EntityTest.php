<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use InvalidArgumentException;
use Modules\Core\Support\Entity;
use PHPUnit\Framework\TestCase;

final class EntityTest extends TestCase
{
    public function test_identity_requires_the_same_entity_type_and_identifier(): void
    {
        $first = new TestEntity(' 42 ');
        $same = new TestEntity('42');
        $differentType = new OtherTestEntity('42');

        self::assertSame('42', $first->id());
        self::assertTrue($first->sameIdentityAs($same));
        self::assertFalse($first->sameIdentityAs($differentType));
    }

    public function test_empty_identifier_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new TestEntity('   ');
    }
}

final class TestEntity extends Entity {}

final class OtherTestEntity extends Entity {}
