<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Product;

use InvalidArgumentException;
use Modules\Product\Domain\ValueObjects\SKU;
use Tests\TestCase;

class SKUTest extends TestCase
{
    public function test_can_create_valid_sku(): void
    {
        $sku = new SKU('WIDGET-001');

        $this->assertSame('WIDGET-001', $sku->value);
    }

    public function test_sku_is_normalized_to_uppercase(): void
    {
        $sku = new SKU('widget-001');

        $this->assertSame('WIDGET-001', $sku->value);
    }

    public function test_sku_trims_whitespace(): void
    {
        $sku = new SKU('  SKU-123  ');

        $this->assertSame('SKU-123', $sku->value);
    }

    public function test_throws_on_empty_sku(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new SKU('');
    }

    public function test_throws_on_sku_exceeding_100_chars(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new SKU(str_repeat('A', 101));
    }

    public function test_throws_on_invalid_characters(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new SKU('SKU WITH SPACES');
    }

    public function test_allows_dots_underscores_hyphens(): void
    {
        $sku = new SKU('SKU.A_B-C');

        $this->assertSame('SKU.A_B-C', $sku->value);
    }

    public function test_equals_returns_true_for_same_value(): void
    {
        $sku1 = new SKU('WIDGET-001');
        $sku2 = new SKU('widget-001');

        $this->assertTrue($sku1->equals($sku2));
    }

    public function test_equals_returns_false_for_different_value(): void
    {
        $sku1 = new SKU('WIDGET-001');
        $sku2 = new SKU('WIDGET-002');

        $this->assertFalse($sku1->equals($sku2));
    }

    public function test_to_string_returns_value(): void
    {
        $sku = new SKU('PROD-XYZ');

        $this->assertSame('PROD-XYZ', (string) $sku);
    }
}
