<?php

declare(strict_types=1);

namespace Modules\Pricing\Tests\Unit;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\Pricing\Application\Services\PricingService;
use Modules\Pricing\Domain\Contracts\PricingRepositoryContract;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for PricingService CRUD methods (show, update, delete for price
 * lists and full CRUD for discount rules).
 *
 * The repository is stubbed — no database or Laravel bootstrap required.
 * These tests verify method existence, correct delegation, and return types.
 */
class PricingServiceCrudTest extends TestCase
{
    private function makeService(?PricingRepositoryContract $repo = null): PricingService
    {
        return new PricingService(
            $repo ?? $this->createMock(PricingRepositoryContract::class)
        );
    }

    // -------------------------------------------------------------------------
    // showPriceList
    // -------------------------------------------------------------------------

    public function test_pricing_service_has_show_price_list_method(): void
    {
        $this->assertTrue(
            method_exists(PricingService::class, 'showPriceList'),
            'PricingService must expose a public showPriceList() method.'
        );
    }

    public function test_show_price_list_delegates_to_repository_find_or_fail(): void
    {
        $model = $this->createMock(Model::class);

        $repo = $this->createMock(PricingRepositoryContract::class);
        $repo->expects($this->once())
            ->method('findOrFail')
            ->with(42)
            ->willReturn($model);

        $result = $this->makeService($repo)->showPriceList(42);

        $this->assertSame($model, $result);
    }

    // -------------------------------------------------------------------------
    // updatePriceList
    // -------------------------------------------------------------------------

    public function test_pricing_service_has_update_price_list_method(): void
    {
        $this->assertTrue(
            method_exists(PricingService::class, 'updatePriceList'),
            'PricingService must expose a public updatePriceList() method.'
        );
    }

    public function test_update_price_list_accepts_id_and_data_array(): void
    {
        $reflection = new \ReflectionMethod(PricingService::class, 'updatePriceList');
        $params     = $reflection->getParameters();

        $this->assertCount(2, $params);
        $this->assertSame('id', $params[0]->getName());
        $this->assertSame('data', $params[1]->getName());
    }

    public function test_update_price_list_return_type_is_model(): void
    {
        $reflection = new \ReflectionMethod(PricingService::class, 'updatePriceList');
        $returnType = (string) $reflection->getReturnType();

        $this->assertSame('Illuminate\Database\Eloquent\Model', $returnType);
    }

    // -------------------------------------------------------------------------
    // deletePriceList
    // -------------------------------------------------------------------------

    public function test_pricing_service_has_delete_price_list_method(): void
    {
        $this->assertTrue(
            method_exists(PricingService::class, 'deletePriceList'),
            'PricingService must expose a public deletePriceList() method.'
        );
    }

    public function test_delete_price_list_accepts_id_and_returns_bool(): void
    {
        $reflection = new \ReflectionMethod(PricingService::class, 'deletePriceList');
        $params     = $reflection->getParameters();
        $returnType = (string) $reflection->getReturnType();

        $this->assertCount(1, $params);
        $this->assertSame('id', $params[0]->getName());
        $this->assertSame('bool', $returnType);
    }

    // -------------------------------------------------------------------------
    // listDiscountRules
    // -------------------------------------------------------------------------

    public function test_pricing_service_has_list_discount_rules_method(): void
    {
        $this->assertTrue(
            method_exists(PricingService::class, 'listDiscountRules'),
            'PricingService must expose a public listDiscountRules() method.'
        );
    }

    public function test_list_discount_rules_delegates_to_repository_all_discount_rules(): void
    {
        $expected = new Collection();

        $repo = $this->createMock(PricingRepositoryContract::class);
        $repo->expects($this->once())
            ->method('allDiscountRules')
            ->willReturn($expected);

        $result = $this->makeService($repo)->listDiscountRules();

        $this->assertSame($expected, $result);
    }

    public function test_list_discount_rules_returns_collection(): void
    {
        $repo = $this->createMock(PricingRepositoryContract::class);
        $repo->method('allDiscountRules')->willReturn(new Collection());

        $result = $this->makeService($repo)->listDiscountRules();

        $this->assertInstanceOf(Collection::class, $result);
    }

    // -------------------------------------------------------------------------
    // createDiscountRule
    // -------------------------------------------------------------------------

    public function test_pricing_service_has_create_discount_rule_method(): void
    {
        $this->assertTrue(
            method_exists(PricingService::class, 'createDiscountRule'),
            'PricingService must expose a public createDiscountRule() method.'
        );
    }

    public function test_create_discount_rule_accepts_data_array(): void
    {
        $reflection = new \ReflectionMethod(PricingService::class, 'createDiscountRule');
        $params     = $reflection->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('data', $params[0]->getName());
    }

    public function test_create_discount_rule_return_type_is_model(): void
    {
        $reflection = new \ReflectionMethod(PricingService::class, 'createDiscountRule');
        $returnType = (string) $reflection->getReturnType();

        $this->assertSame('Illuminate\Database\Eloquent\Model', $returnType);
    }

    // -------------------------------------------------------------------------
    // showDiscountRule
    // -------------------------------------------------------------------------

    public function test_pricing_service_has_show_discount_rule_method(): void
    {
        $this->assertTrue(
            method_exists(PricingService::class, 'showDiscountRule'),
            'PricingService must expose a public showDiscountRule() method.'
        );
    }

    public function test_show_discount_rule_accepts_id(): void
    {
        $reflection = new \ReflectionMethod(PricingService::class, 'showDiscountRule');
        $params     = $reflection->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('id', $params[0]->getName());
    }

    // -------------------------------------------------------------------------
    // updateDiscountRule
    // -------------------------------------------------------------------------

    public function test_pricing_service_has_update_discount_rule_method(): void
    {
        $this->assertTrue(
            method_exists(PricingService::class, 'updateDiscountRule'),
            'PricingService must expose a public updateDiscountRule() method.'
        );
    }

    public function test_update_discount_rule_accepts_id_and_data_array(): void
    {
        $reflection = new \ReflectionMethod(PricingService::class, 'updateDiscountRule');
        $params     = $reflection->getParameters();

        $this->assertCount(2, $params);
        $this->assertSame('id', $params[0]->getName());
        $this->assertSame('data', $params[1]->getName());
    }

    // -------------------------------------------------------------------------
    // deleteDiscountRule
    // -------------------------------------------------------------------------

    public function test_pricing_service_has_delete_discount_rule_method(): void
    {
        $this->assertTrue(
            method_exists(PricingService::class, 'deleteDiscountRule'),
            'PricingService must expose a public deleteDiscountRule() method.'
        );
    }

    public function test_delete_discount_rule_accepts_id_and_returns_bool(): void
    {
        $reflection = new \ReflectionMethod(PricingService::class, 'deleteDiscountRule');
        $params     = $reflection->getParameters();
        $returnType = (string) $reflection->getReturnType();

        $this->assertCount(1, $params);
        $this->assertSame('id', $params[0]->getName());
        $this->assertSame('bool', $returnType);
    }
}
