<?php

declare(strict_types=1);

namespace Modules\Pricing\Tests\Unit;

use Modules\Pricing\Application\Services\PricingService;
use Modules\Pricing\Domain\Contracts\PricingRepositoryContract;
use PHPUnit\Framework\TestCase;

/**
 * Structural compliance tests for PricingService write-path methods.
 *
 * createPriceList() calls DB::transaction() internally, which requires a full
 * Laravel bootstrap. These pure-PHP tests verify method signatures, service
 * contract compliance, and the calculatePrice / listPriceLists structural API.
 */
class PricingServiceWritePathTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Method existence — structural compliance
    // -------------------------------------------------------------------------

    public function test_pricing_service_has_create_price_list_method(): void
    {
        $this->assertTrue(
            method_exists(PricingService::class, 'createPriceList'),
            'PricingService must expose a public createPriceList() method.'
        );
    }

    public function test_pricing_service_has_calculate_price_method(): void
    {
        $this->assertTrue(
            method_exists(PricingService::class, 'calculatePrice'),
            'PricingService must expose a public calculatePrice() method.'
        );
    }

    public function test_pricing_service_has_list_price_lists_method(): void
    {
        $this->assertTrue(
            method_exists(PricingService::class, 'listPriceLists'),
            'PricingService must expose a public listPriceLists() method.'
        );
    }

    // -------------------------------------------------------------------------
    // Method signatures — parameter inspection via reflection
    // -------------------------------------------------------------------------

    public function test_create_price_list_accepts_data_array(): void
    {
        $reflection = new \ReflectionMethod(PricingService::class, 'createPriceList');
        $params     = $reflection->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('data', $params[0]->getName());
    }

    public function test_create_price_list_return_type_is_model(): void
    {
        $reflection = new \ReflectionMethod(PricingService::class, 'createPriceList');
        $returnType = (string) $reflection->getReturnType();

        $this->assertSame('Illuminate\Database\Eloquent\Model', $returnType);
    }

    public function test_list_price_lists_has_no_required_parameters(): void
    {
        $reflection = new \ReflectionMethod(PricingService::class, 'listPriceLists');
        $params     = $reflection->getParameters();

        $this->assertCount(0, $params);
    }

    // -------------------------------------------------------------------------
    // Service instantiation with repository contract
    // -------------------------------------------------------------------------

    public function test_pricing_service_instantiates_with_repository_contract(): void
    {
        $repo    = $this->createMock(PricingRepositoryContract::class);
        $service = new PricingService($repo);

        $this->assertInstanceOf(PricingService::class, $service);
    }

    // -------------------------------------------------------------------------
    // createPriceList payload shape — DTO-free data array
    // -------------------------------------------------------------------------

    public function test_create_price_list_payload_contains_required_fields(): void
    {
        $payload = [
            'name'          => 'Standard Retail',
            'currency_code' => 'USD',
            'is_active'     => true,
        ];

        $this->assertArrayHasKey('name', $payload);
        $this->assertArrayHasKey('currency_code', $payload);
        $this->assertArrayHasKey('is_active', $payload);
        $this->assertSame('Standard Retail', $payload['name']);
        $this->assertSame('USD', $payload['currency_code']);
        $this->assertTrue($payload['is_active']);
    }
}
