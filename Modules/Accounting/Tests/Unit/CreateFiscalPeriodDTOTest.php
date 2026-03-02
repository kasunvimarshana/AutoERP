<?php

declare(strict_types=1);

namespace Modules\Accounting\Tests\Unit;

use Modules\Accounting\Application\DTOs\CreateFiscalPeriodDTO;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CreateFiscalPeriodDTO.
 */
class CreateFiscalPeriodDTOTest extends TestCase
{
    public function test_from_array_hydrates_all_required_fields(): void
    {
        $dto = CreateFiscalPeriodDTO::fromArray([
            'name'       => 'FY2026-Q1',
            'start_date' => '2026-01-01',
            'end_date'   => '2026-03-31',
        ]);

        $this->assertSame('FY2026-Q1', $dto->name);
        $this->assertSame('2026-01-01', $dto->startDate);
        $this->assertSame('2026-03-31', $dto->endDate);
        $this->assertFalse($dto->isClosed);
    }

    public function test_is_closed_defaults_to_false(): void
    {
        $dto = CreateFiscalPeriodDTO::fromArray([
            'name'       => 'FY2026-Q2',
            'start_date' => '2026-04-01',
            'end_date'   => '2026-06-30',
        ]);

        $this->assertFalse($dto->isClosed);
    }

    public function test_from_array_accepts_is_closed_true(): void
    {
        $dto = CreateFiscalPeriodDTO::fromArray([
            'name'       => 'FY2025-Annual',
            'start_date' => '2025-01-01',
            'end_date'   => '2025-12-31',
            'is_closed'  => true,
        ]);

        $this->assertTrue($dto->isClosed);
    }

    public function test_to_array_returns_correct_keys(): void
    {
        $dto = CreateFiscalPeriodDTO::fromArray([
            'name'       => 'FY2026-Q3',
            'start_date' => '2026-07-01',
            'end_date'   => '2026-09-30',
        ]);

        $array = $dto->toArray();

        $this->assertArrayHasKey('name', $array);
        $this->assertArrayHasKey('start_date', $array);
        $this->assertArrayHasKey('end_date', $array);
        $this->assertArrayHasKey('is_closed', $array);
    }

    public function test_to_array_round_trips_correctly(): void
    {
        $data = [
            'name'       => 'FY2026-Annual',
            'start_date' => '2026-01-01',
            'end_date'   => '2026-12-31',
            'is_closed'  => false,
        ];

        $dto   = CreateFiscalPeriodDTO::fromArray($data);
        $array = $dto->toArray();

        $this->assertSame($data['name'], $array['name']);
        $this->assertSame($data['start_date'], $array['start_date']);
        $this->assertSame($data['end_date'], $array['end_date']);
        $this->assertSame($data['is_closed'], $array['is_closed']);
    }
}
