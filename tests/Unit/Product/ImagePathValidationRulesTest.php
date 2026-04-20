<?php

declare(strict_types=1);

namespace Tests\Unit\Product;

use Modules\Product\Infrastructure\Http\Requests\StoreProductBrandRequest;
use Modules\Product\Infrastructure\Http\Requests\StoreProductCategoryRequest;
use Modules\Product\Infrastructure\Http\Requests\StoreProductRequest;
use Modules\Product\Infrastructure\Http\Requests\UpdateProductBrandRequest;
use Modules\Product\Infrastructure\Http\Requests\UpdateProductCategoryRequest;
use Modules\Product\Infrastructure\Http\Requests\UpdateProductRequest;
use PHPUnit\Framework\TestCase;

class ImagePathValidationRulesTest extends TestCase
{
    private const EXPECTED_RULE = 'nullable|file|max:5120|mimes:jpg,jpeg,png,gif,webp,svg';

    public function test_store_product_uses_expected_image_rule(): void
    {
        $request = new StoreProductRequest;

        $this->assertSame(self::EXPECTED_RULE, $request->rules()['image_path']);
    }

    public function test_update_product_uses_expected_image_rule(): void
    {
        $request = new UpdateProductRequest;

        $this->assertSame(self::EXPECTED_RULE, $request->rules()['image_path']);
    }

    public function test_store_product_brand_uses_expected_image_rule(): void
    {
        $request = new StoreProductBrandRequest;

        $this->assertSame(self::EXPECTED_RULE, $request->rules()['image_path']);
    }

    public function test_update_product_brand_uses_expected_image_rule(): void
    {
        $request = new UpdateProductBrandRequest;

        $this->assertSame(self::EXPECTED_RULE, $request->rules()['image_path']);
    }

    public function test_store_product_category_uses_expected_image_rule(): void
    {
        $request = new StoreProductCategoryRequest;

        $this->assertSame(self::EXPECTED_RULE, $request->rules()['image_path']);
    }

    public function test_update_product_category_uses_expected_image_rule(): void
    {
        $request = new UpdateProductCategoryRequest;

        $this->assertSame(self::EXPECTED_RULE, $request->rules()['image_path']);
    }
}
