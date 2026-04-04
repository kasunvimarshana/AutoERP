<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Modules\Core\Domain\Entities\BaseEntity;
use Modules\Core\Application\DTOs\BaseDTO;

class CoreModuleTest extends TestCase
{
    // --------------- BaseEntity ---------------

    public function test_base_entity_id_defaults_to_null(): void
    {
        $entity = new class extends BaseEntity {};
        $this->assertNull($entity->id);
    }

    public function test_base_entity_stores_positive_id(): void
    {
        $entity = new class(42) extends BaseEntity {};
        $this->assertSame(42, $entity->id);
    }

    public function test_base_entity_stores_zero_id(): void
    {
        $entity = new class(0) extends BaseEntity {};
        $this->assertSame(0, $entity->id);
    }

    public function test_base_entity_id_is_readonly(): void
    {
        $entity = new class(5) extends BaseEntity {};
        $this->assertTrue((new \ReflectionProperty(BaseEntity::class, 'id'))->isReadOnly());
    }

    public function test_base_entity_is_abstract(): void
    {
        $this->assertTrue((new \ReflectionClass(BaseEntity::class))->isAbstract());
    }

    // --------------- BaseDTO ---------------

    public function test_base_dto_to_array_returns_array(): void
    {
        $dto = new class extends BaseDTO {
            public string $name = 'test';
            public int $qty = 10;
        };
        $this->assertIsArray($dto->toArray());
    }

    public function test_base_dto_to_array_contains_all_properties(): void
    {
        $dto = new class extends BaseDTO {
            public string $name = 'widget';
            public int $qty = 5;
        };
        $arr = $dto->toArray();
        $this->assertArrayHasKey('name', $arr);
        $this->assertArrayHasKey('qty', $arr);
    }

    public function test_base_dto_to_array_values_match_properties(): void
    {
        $dto = new class extends BaseDTO {
            public string $foo = 'bar';
        };
        $this->assertSame('bar', $dto->toArray()['foo']);
    }

    public function test_base_dto_is_abstract(): void
    {
        $this->assertTrue((new \ReflectionClass(BaseDTO::class))->isAbstract());
    }

    public function test_base_dto_to_array_empty_when_no_properties(): void
    {
        $dto = new class extends BaseDTO {};
        $this->assertSame([], $dto->toArray());
    }
}
