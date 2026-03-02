<?php

declare(strict_types=1);

namespace Modules\Accounting\Tests\Unit;

use Modules\Accounting\Application\DTOs\CreateJournalEntryDTO;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CreateJournalEntryDTO.
 *
 * Pure PHP — no database or Laravel bootstrap required.
 */
class CreateJournalEntryDTOTest extends TestCase
{
    public function test_from_array_hydrates_all_fields(): void
    {
        $dto = CreateJournalEntryDTO::fromArray([
            'fiscal_period_id' => 3,
            'reference_number' => 'JE-2026-001',
            'description'      => 'Rent expense journal',
            'entry_date'       => '2026-01-31',
            'lines'            => [
                ['account_id' => 10, 'type' => 'debit',  'amount' => '1500.0000'],
                ['account_id' => 20, 'type' => 'credit', 'amount' => '1500.0000'],
            ],
        ]);

        $this->assertSame(3, $dto->fiscalPeriodId);
        $this->assertSame('JE-2026-001', $dto->referenceNumber);
        $this->assertSame('Rent expense journal', $dto->description);
        $this->assertSame('2026-01-31', $dto->entryDate);
        $this->assertCount(2, $dto->lines);
        $this->assertSame('debit', $dto->lines[0]['type']);
        $this->assertSame('1500.0000', $dto->lines[0]['amount']);
        $this->assertSame('credit', $dto->lines[1]['type']);
    }

    public function test_from_array_description_defaults_to_null(): void
    {
        $dto = CreateJournalEntryDTO::fromArray([
            'fiscal_period_id' => 1,
            'reference_number' => 'JE-001',
            'entry_date'       => '2026-01-01',
            'lines'            => [],
        ]);

        $this->assertNull($dto->description);
    }

    public function test_from_array_casts_fiscal_period_id_to_int(): void
    {
        $dto = CreateJournalEntryDTO::fromArray([
            'fiscal_period_id' => '7',
            'reference_number' => 'JE-007',
            'entry_date'       => '2026-02-01',
            'lines'            => [],
        ]);

        $this->assertSame(7, $dto->fiscalPeriodId);
        $this->assertIsInt($dto->fiscalPeriodId);
    }

    public function test_from_array_casts_entry_date_to_string(): void
    {
        $dto = CreateJournalEntryDTO::fromArray([
            'fiscal_period_id' => 1,
            'reference_number' => 'JE-001',
            'entry_date'       => '2026-03-15',
            'lines'            => [],
        ]);

        $this->assertIsString($dto->entryDate);
        $this->assertSame('2026-03-15', $dto->entryDate);
    }
}
