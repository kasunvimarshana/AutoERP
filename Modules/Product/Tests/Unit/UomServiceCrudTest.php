<?php

declare(strict_types=1);

namespace Modules\Product\Tests\Unit;

use Illuminate\Database\Eloquent\Model;
use Modules\Product\Application\Services\UomService;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for UomService showConversion and deleteConversion methods.
 *
 * Verifies method existence, visibility, parameter signatures, and return types.
 * No database or Laravel bootstrap required — uses reflection only.
 */
class UomServiceCrudTest extends TestCase
{
    // -------------------------------------------------------------------------
    // showConversion
    // -------------------------------------------------------------------------

    public function test_uom_service_has_show_conversion_method(): void
    {
        $this->assertTrue(
            method_exists(UomService::class, 'showConversion'),
            'UomService must expose a public showConversion() method.'
        );
    }

    public function test_show_conversion_is_public(): void
    {
        $reflection = new \ReflectionMethod(UomService::class, 'showConversion');

        $this->assertTrue($reflection->isPublic());
    }

    public function test_show_conversion_accepts_id_parameter(): void
    {
        $reflection = new \ReflectionMethod(UomService::class, 'showConversion');
        $params     = $reflection->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('id', $params[0]->getName());
    }

    public function test_show_conversion_returns_model(): void
    {
        $reflection = new \ReflectionMethod(UomService::class, 'showConversion');
        $returnType = (string) $reflection->getReturnType();

        $this->assertSame(Model::class, $returnType);
    }

    // -------------------------------------------------------------------------
    // deleteConversion
    // -------------------------------------------------------------------------

    public function test_uom_service_has_delete_conversion_method(): void
    {
        $this->assertTrue(
            method_exists(UomService::class, 'deleteConversion'),
            'UomService must expose a public deleteConversion() method.'
        );
    }

    public function test_delete_conversion_is_public(): void
    {
        $reflection = new \ReflectionMethod(UomService::class, 'deleteConversion');

        $this->assertTrue($reflection->isPublic());
    }

    public function test_delete_conversion_accepts_id_parameter(): void
    {
        $reflection = new \ReflectionMethod(UomService::class, 'deleteConversion');
        $params     = $reflection->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('id', $params[0]->getName());
    }

    public function test_delete_conversion_returns_bool(): void
    {
        $reflection = new \ReflectionMethod(UomService::class, 'deleteConversion');
        $returnType = (string) $reflection->getReturnType();

        $this->assertSame('bool', $returnType);
    }

    // -------------------------------------------------------------------------
    // Existing methods still present
    // -------------------------------------------------------------------------

    public function test_uom_service_still_has_list_conversions_method(): void
    {
        $this->assertTrue(method_exists(UomService::class, 'listConversions'));
    }

    public function test_uom_service_still_has_add_conversion_method(): void
    {
        $this->assertTrue(method_exists(UomService::class, 'addConversion'));
    }
}
