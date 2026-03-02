<?php

declare(strict_types=1);

namespace Modules\Core\Tests\Unit;

use Modules\Core\Application\DTOs\DataTransferObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the DataTransferObject base class.
 *
 * A concrete anonymous subclass is used to exercise the abstract contract:
 * fromArray() factory and toArray() serialisation.
 */
class DataTransferObjectTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Factory helper
    // -------------------------------------------------------------------------

    private function makeConcreteDTO(array $data): DataTransferObject
    {
        return new class($data) extends DataTransferObject {
            public string $name;

            public int $tenantId;

            public bool $active;

            public function __construct(array $fields)
            {
                $this->name     = (string) ($fields['name'] ?? '');
                $this->tenantId = (int) ($fields['tenant_id'] ?? 0);
                $this->active   = (bool) ($fields['active'] ?? true);
            }

            public static function fromArray(array $data): static
            {
                return new static($data);
            }

            public function toArray(): array
            {
                return [
                    'name'      => $this->name,
                    'tenant_id' => $this->tenantId,
                    'active'    => $this->active,
                ];
            }
        };
    }

    // -------------------------------------------------------------------------
    // fromArray — hydration
    // -------------------------------------------------------------------------

    public function test_from_array_hydrates_string_field(): void
    {
        $dto = $this->makeConcreteDTO(['name' => 'Acme Corp', 'tenant_id' => 3, 'active' => true]);

        $this->assertSame('Acme Corp', $dto->name);
    }

    public function test_from_array_hydrates_int_field(): void
    {
        $dto = $this->makeConcreteDTO(['name' => 'Test', 'tenant_id' => 42]);

        $this->assertSame(42, $dto->tenantId);
    }

    public function test_from_array_hydrates_bool_field(): void
    {
        $dto = $this->makeConcreteDTO(['name' => 'Test', 'active' => false]);

        $this->assertFalse($dto->active);
    }

    public function test_from_array_applies_default_for_missing_field(): void
    {
        // tenant_id omitted → defaults to 0
        $dto = $this->makeConcreteDTO(['name' => 'Only name']);

        $this->assertSame(0, $dto->tenantId);
    }

    // -------------------------------------------------------------------------
    // toArray — serialisation contract
    // -------------------------------------------------------------------------

    public function test_to_array_returns_all_fields(): void
    {
        $dto    = $this->makeConcreteDTO(['name' => 'Beta Ltd', 'tenant_id' => 5, 'active' => true]);
        $result = $dto->toArray();

        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('tenant_id', $result);
        $this->assertArrayHasKey('active', $result);
    }

    public function test_to_array_round_trips_correctly(): void
    {
        $input  = ['name' => 'Gamma Inc', 'tenant_id' => 99, 'active' => false];
        $dto    = $this->makeConcreteDTO($input);
        $output = $dto->toArray();

        $this->assertSame('Gamma Inc', $output['name']);
        $this->assertSame(99, $output['tenant_id']);
        $this->assertFalse($output['active']);
    }
}
