<?php

declare(strict_types=1);

namespace Modules\Product\Tests\Unit;

use Modules\Product\Domain\ValueObjects\SKU;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the SKU value object.
 * Validates format rules: alphanumeric with optional dashes, max 50 chars.
 */
class SKUTest extends TestCase
{
    // ── Construction ─────────────────────────────────────────────────────

    public function test_valid_alphanumeric_sku_is_accepted(): void
    {
        $sku = new SKU('ABC123');

        $this->assertSame('ABC123', $sku->getValue());
    }

    public function test_sku_is_uppercased(): void
    {
        $sku = new SKU('abc-001');

        $this->assertSame('ABC-001', $sku->getValue());
    }

    public function test_sku_with_dashes_is_valid(): void
    {
        $sku = new SKU('PROD-CAT-001');

        $this->assertSame('PROD-CAT-001', $sku->getValue());
    }

    public function test_single_character_sku_is_valid(): void
    {
        $sku = new SKU('A');

        $this->assertSame('A', $sku->getValue());
    }

    public function test_max_length_50_chars_is_accepted(): void
    {
        $value = str_repeat('A', 50);
        $sku   = new SKU($value);

        $this->assertSame($value, $sku->getValue());
    }

    public function test_leading_and_trailing_whitespace_is_stripped(): void
    {
        $sku = new SKU('  SKU-001  ');

        $this->assertSame('SKU-001', $sku->getValue());
    }

    // ── Validation failures ───────────────────────────────────────────────

    public function test_empty_sku_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new SKU('');
    }

    public function test_whitespace_only_sku_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new SKU('   ');
    }

    public function test_sku_with_special_characters_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new SKU('SKU@001');
    }

    public function test_sku_exceeding_50_chars_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new SKU(str_repeat('A', 51));
    }

    public function test_sku_starting_with_dash_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new SKU('-SKU001');
    }

    // ── Equality ─────────────────────────────────────────────────────────

    public function test_equals_returns_true_for_same_value(): void
    {
        $a = new SKU('ABC-001');
        $b = new SKU('abc-001');

        $this->assertTrue($a->equals($b));
    }

    public function test_equals_returns_false_for_different_value(): void
    {
        $a = new SKU('ABC-001');
        $b = new SKU('ABC-002');

        $this->assertFalse($a->equals($b));
    }

    // ── String representation ─────────────────────────────────────────────

    public function test_to_string_returns_uppercase_value(): void
    {
        $sku = new SKU('widget-001');

        $this->assertSame('WIDGET-001', (string) $sku);
    }
}
