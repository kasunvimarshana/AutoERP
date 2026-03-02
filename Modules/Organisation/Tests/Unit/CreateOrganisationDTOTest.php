<?php

declare(strict_types=1);

namespace Modules\Organisation\Tests\Unit;

use Modules\Organisation\Application\DTOs\CreateOrganisationDTO;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CreateOrganisationDTO.
 *
 * Pure PHP — no database or Laravel bootstrap required.
 */
class CreateOrganisationDTOTest extends TestCase
{
    public function test_from_array_hydrates_required_fields(): void
    {
        $dto = CreateOrganisationDTO::fromArray([
            'name' => 'Acme Corp',
            'code' => 'ACME',
        ]);

        $this->assertSame('Acme Corp', $dto->name);
        $this->assertSame('ACME', $dto->code);
        $this->assertNull($dto->description);
        $this->assertTrue($dto->isActive);
    }

    public function test_from_array_hydrates_optional_fields(): void
    {
        $dto = CreateOrganisationDTO::fromArray([
            'name'        => 'Beta Ltd',
            'code'        => 'BETA',
            'description' => 'A subsidiary company',
            'is_active'   => false,
        ]);

        $this->assertSame('A subsidiary company', $dto->description);
        $this->assertFalse($dto->isActive);
    }

    public function test_is_active_defaults_to_true(): void
    {
        $dto = CreateOrganisationDTO::fromArray([
            'name' => 'Gamma Inc',
            'code' => 'GAMMA',
        ]);

        $this->assertTrue($dto->isActive);
    }

    public function test_description_defaults_to_null(): void
    {
        $dto = CreateOrganisationDTO::fromArray([
            'name' => 'Delta LLC',
            'code' => 'DELTA',
        ]);

        $this->assertNull($dto->description);
    }
}
