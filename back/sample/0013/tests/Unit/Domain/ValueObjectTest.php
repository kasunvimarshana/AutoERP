<?php

declare(strict_types=1);

namespace Archify\DddArchitect\Tests\Unit\Domain;

use App\Domain\Catalog\ValueObjects\Money;
use App\Domain\Catalog\ValueObjects\ProductName;
use App\Shared\Domain\ValueObjects\Uuid;
use Archify\DddArchitect\Tests\TestCase;

/**
 * Unit tests for Shared Kernel and example value objects.
 * These tests are pure — no DB, no Laravel container required.
 */
final class ValueObjectTest extends TestCase
{
    // ── Uuid ─────────────────────────────────────────────────────────────────

    /** @test */
    public function uuid_generate_produces_valid_v4(): void
    {
        $uuid = Uuid::generate();

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $uuid->value()
        );
    }

    /** @test */
    public function uuid_generates_unique_values(): void
    {
        $a = Uuid::generate();
        $b = Uuid::generate();

        $this->assertFalse($a->equals($b));
    }

    /** @test */
    public function uuid_from_string_accepts_valid_uuid(): void
    {
        $value = '550e8400-e29b-41d4-a716-446655440000';
        $uuid  = Uuid::fromString($value);

        $this->assertSame($value, $uuid->value());
    }

    /** @test */
    public function uuid_from_string_rejects_invalid_value(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Uuid::fromString('not-a-uuid');
    }

    /** @test */
    public function uuid_equality_is_case_insensitive(): void
    {
        $a = Uuid::fromString('550E8400-E29B-41D4-A716-446655440000');
        $b = Uuid::fromString('550e8400-e29b-41d4-a716-446655440000');

        $this->assertTrue($a->equals($b));
    }

    // ── Money ────────────────────────────────────────────────────────────────

    /** @test */
    public function money_stores_amount_in_minor_units(): void
    {
        $money = Money::of(1999, 'USD');

        $this->assertSame(1999, $money->amount());
        $this->assertSame('USD', $money->currency());
    }

    /** @test */
    public function money_converts_to_decimal_correctly(): void
    {
        $money = Money::of(1999, 'USD');

        $this->assertEqualsWithDelta(19.99, $money->toDecimal(), 0.001);
    }

    /** @test */
    public function money_from_decimal_rounds_correctly(): void
    {
        $money = Money::fromDecimal('19.999', 'USD');

        $this->assertSame(2000, $money->amount());
    }

    /** @test */
    public function money_rejects_negative_amounts(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Money::of(-1, 'USD');
    }

    /** @test */
    public function money_equality_requires_same_currency(): void
    {
        $usd = Money::of(100, 'USD');
        $eur = Money::of(100, 'EUR');

        $this->assertFalse($usd->equals($eur));
    }

    /** @test */
    public function money_is_zero_detection(): void
    {
        $this->assertTrue(Money::of(0, 'USD')->isZero());
        $this->assertFalse(Money::of(1, 'USD')->isZero());
    }

    /** @test */
    public function money_formats_correctly(): void
    {
        $money = Money::of(1999, 'USD');

        $this->assertSame('USD 19.99', $money->formatted());
    }

    // ── ProductName ──────────────────────────────────────────────────────────

    /** @test */
    public function product_name_trims_whitespace(): void
    {
        $name = ProductName::fromString('  Widget  ');

        $this->assertSame('Widget', $name->value());
    }

    /** @test */
    public function product_name_rejects_empty_string(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ProductName::fromString('   ');
    }

    /** @test */
    public function product_name_rejects_names_over_255_chars(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ProductName::fromString(str_repeat('a', 256));
    }

    /** @test */
    public function product_name_equality(): void
    {
        $a = ProductName::fromString('Widget');
        $b = ProductName::fromString('Widget');
        $c = ProductName::fromString('Gadget');

        $this->assertTrue($a->equals($b));
        $this->assertFalse($a->equals($c));
    }
}
