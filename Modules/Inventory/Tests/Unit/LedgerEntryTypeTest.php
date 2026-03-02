<?php

declare(strict_types=1);

namespace Modules\Inventory\Tests\Unit;

use Modules\Inventory\Domain\Enums\LedgerEntryType;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the LedgerEntryType enum including manufacturing-specific cases
 * added to support the Manufacturing module (from stock-manager-pro-with-pos reference).
 */
class LedgerEntryTypeTest extends TestCase
{
    public function test_manufacturing_consumption_case_exists(): void
    {
        $type = LedgerEntryType::MANUFACTURING_CONSUMPTION;

        $this->assertSame('MANUFACTURING_CONSUMPTION', $type->value);
    }

    public function test_manufacturing_output_case_exists(): void
    {
        $type = LedgerEntryType::MANUFACTURING_OUTPUT;

        $this->assertSame('MANUFACTURING_OUTPUT', $type->value);
    }

    public function test_manufacturing_output_is_inbound(): void
    {
        $this->assertTrue(LedgerEntryType::MANUFACTURING_OUTPUT->isInbound());
    }

    public function test_manufacturing_consumption_is_outbound(): void
    {
        $this->assertTrue(LedgerEntryType::MANUFACTURING_CONSUMPTION->isOutbound());
        $this->assertFalse(LedgerEntryType::MANUFACTURING_CONSUMPTION->isInbound());
    }

    public function test_manufacturing_output_label(): void
    {
        $this->assertSame('Manufacturing Output', LedgerEntryType::MANUFACTURING_OUTPUT->label());
    }

    public function test_manufacturing_consumption_label(): void
    {
        $this->assertSame('Manufacturing Consumption', LedgerEntryType::MANUFACTURING_CONSUMPTION->label());
    }

    public function test_standard_inbound_types(): void
    {
        $this->assertTrue(LedgerEntryType::IN->isInbound());
        $this->assertTrue(LedgerEntryType::ADJUSTMENT_ADD->isInbound());
        $this->assertTrue(LedgerEntryType::TRANSFER_IN->isInbound());
        $this->assertTrue(LedgerEntryType::PURCHASE_RECEIPT->isInbound());
        $this->assertTrue(LedgerEntryType::RETURN->isInbound());
    }

    public function test_standard_outbound_types(): void
    {
        $this->assertTrue(LedgerEntryType::OUT->isOutbound());
        $this->assertTrue(LedgerEntryType::ADJUSTMENT_REMOVE->isOutbound());
        $this->assertTrue(LedgerEntryType::TRANSFER_OUT->isOutbound());
        $this->assertTrue(LedgerEntryType::SALE->isOutbound());
    }

    public function test_from_string_round_trips(): void
    {
        foreach (LedgerEntryType::cases() as $case) {
            $this->assertSame($case, LedgerEntryType::from($case->value));
        }
    }
}
