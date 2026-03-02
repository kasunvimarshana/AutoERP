<?php

declare(strict_types=1);

namespace Modules\Sales\Tests\Unit;

use Illuminate\Http\JsonResponse;
use Modules\Sales\Interfaces\Http\Controllers\SalesController;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Structural tests for SalesController::createReturn().
 *
 * Validates:
 *  - Method exists and is public
 *  - Returns JsonResponse
 *  - Accepts Request + int $id parameters
 */
class SalesControllerReturnTest extends TestCase
{
    public function test_controller_has_create_return_method(): void
    {
        $this->assertTrue(
            method_exists(SalesController::class, 'createReturn'),
            'SalesController must expose a public createReturn() method.'
        );
    }

    public function test_create_return_is_public(): void
    {
        $ref = new ReflectionMethod(SalesController::class, 'createReturn');
        $this->assertTrue($ref->isPublic());
    }

    public function test_create_return_returns_json_response(): void
    {
        $ref = new ReflectionMethod(SalesController::class, 'createReturn');
        $this->assertSame(JsonResponse::class, $ref->getReturnType()?->getName());
    }

    public function test_create_return_accepts_request_and_id(): void
    {
        $ref    = new ReflectionMethod(SalesController::class, 'createReturn');
        $params = $ref->getParameters();

        $this->assertCount(2, $params);
        $this->assertSame('request', $params[0]->getName());
        $this->assertSame('id', $params[1]->getName());
        $this->assertSame('int', $params[1]->getType()?->getName());
    }

    public function test_create_return_is_not_static(): void
    {
        $ref = new ReflectionMethod(SalesController::class, 'createReturn');
        $this->assertFalse($ref->isStatic());
    }

    // -------------------------------------------------------------------------
    // Regression guard — existing public API still present
    // -------------------------------------------------------------------------

    public function test_create_order_still_present(): void
    {
        $this->assertTrue(method_exists(SalesController::class, 'createOrder'));
    }

    public function test_create_delivery_still_present(): void
    {
        $this->assertTrue(method_exists(SalesController::class, 'createDelivery'));
    }

    public function test_create_invoice_still_present(): void
    {
        $this->assertTrue(method_exists(SalesController::class, 'createInvoice'));
    }
}
