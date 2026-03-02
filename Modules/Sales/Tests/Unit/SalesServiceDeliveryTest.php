<?php

declare(strict_types=1);

namespace Modules\Sales\Tests\Unit;

use Modules\Sales\Application\Services\SalesService;
use Modules\Sales\Domain\Contracts\SalesRepositoryContract;
use Modules\Sales\Domain\Entities\SalesDelivery;
use Modules\Sales\Domain\Entities\SalesInvoice;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Structural compliance tests for SalesService delivery and invoice methods.
 *
 * Verifies method existence, visibility, parameter signatures, and return types
 * for createDelivery, listDeliveries, showDelivery, createInvoice, listInvoices,
 * and showInvoice. No database or Laravel bootstrap required.
 */
class SalesServiceDeliveryTest extends TestCase
{
    private function makeService(?SalesRepositoryContract $repo = null): SalesService
    {
        return new SalesService(
            $repo ?? $this->createMock(SalesRepositoryContract::class)
        );
    }

    // -------------------------------------------------------------------------
    // createDelivery
    // -------------------------------------------------------------------------

    public function test_sales_service_has_create_delivery_method(): void
    {
        $this->assertTrue(
            method_exists(SalesService::class, 'createDelivery'),
            'SalesService must expose a public createDelivery() method.'
        );
    }

    public function test_create_delivery_is_public(): void
    {
        $this->assertTrue((new ReflectionMethod(SalesService::class, 'createDelivery'))->isPublic());
    }

    public function test_create_delivery_accepts_order_id_and_data(): void
    {
        $ref    = new ReflectionMethod(SalesService::class, 'createDelivery');
        $params = $ref->getParameters();

        $this->assertCount(2, $params);
        $this->assertSame('orderId', $params[0]->getName());
        $this->assertSame('int', (string) $params[0]->getType());
        $this->assertSame('data', $params[1]->getName());
        $this->assertSame('array', (string) $params[1]->getType());
    }

    public function test_create_delivery_return_type_is_sales_delivery(): void
    {
        $ref = new ReflectionMethod(SalesService::class, 'createDelivery');
        $this->assertSame(SalesDelivery::class, (string) $ref->getReturnType());
    }

    // -------------------------------------------------------------------------
    // listDeliveries
    // -------------------------------------------------------------------------

    public function test_sales_service_has_list_deliveries_method(): void
    {
        $this->assertTrue(
            method_exists(SalesService::class, 'listDeliveries'),
            'SalesService must expose a public listDeliveries() method.'
        );
    }

    public function test_list_deliveries_is_public(): void
    {
        $this->assertTrue((new ReflectionMethod(SalesService::class, 'listDeliveries'))->isPublic());
    }

    public function test_list_deliveries_accepts_order_id(): void
    {
        $ref    = new ReflectionMethod(SalesService::class, 'listDeliveries');
        $params = $ref->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('orderId', $params[0]->getName());
        $this->assertSame('int', (string) $params[0]->getType());
    }

    // -------------------------------------------------------------------------
    // showDelivery
    // -------------------------------------------------------------------------

    public function test_sales_service_has_show_delivery_method(): void
    {
        $this->assertTrue(
            method_exists(SalesService::class, 'showDelivery'),
            'SalesService must expose a public showDelivery() method.'
        );
    }

    public function test_show_delivery_is_public(): void
    {
        $this->assertTrue((new ReflectionMethod(SalesService::class, 'showDelivery'))->isPublic());
    }

    public function test_show_delivery_accepts_id(): void
    {
        $ref    = new ReflectionMethod(SalesService::class, 'showDelivery');
        $params = $ref->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('id', $params[0]->getName());
    }

    public function test_show_delivery_return_type_is_sales_delivery(): void
    {
        $ref = new ReflectionMethod(SalesService::class, 'showDelivery');
        $this->assertSame(SalesDelivery::class, (string) $ref->getReturnType());
    }

    // -------------------------------------------------------------------------
    // createInvoice
    // -------------------------------------------------------------------------

    public function test_sales_service_has_create_invoice_method(): void
    {
        $this->assertTrue(
            method_exists(SalesService::class, 'createInvoice'),
            'SalesService must expose a public createInvoice() method.'
        );
    }

    public function test_create_invoice_is_public(): void
    {
        $this->assertTrue((new ReflectionMethod(SalesService::class, 'createInvoice'))->isPublic());
    }

    public function test_create_invoice_accepts_order_id_and_data(): void
    {
        $ref    = new ReflectionMethod(SalesService::class, 'createInvoice');
        $params = $ref->getParameters();

        $this->assertCount(2, $params);
        $this->assertSame('orderId', $params[0]->getName());
        $this->assertSame('int', (string) $params[0]->getType());
        $this->assertSame('data', $params[1]->getName());
        $this->assertSame('array', (string) $params[1]->getType());
    }

    public function test_create_invoice_return_type_is_sales_invoice(): void
    {
        $ref = new ReflectionMethod(SalesService::class, 'createInvoice');
        $this->assertSame(SalesInvoice::class, (string) $ref->getReturnType());
    }

    // -------------------------------------------------------------------------
    // listInvoices
    // -------------------------------------------------------------------------

    public function test_sales_service_has_list_invoices_method(): void
    {
        $this->assertTrue(
            method_exists(SalesService::class, 'listInvoices'),
            'SalesService must expose a public listInvoices() method.'
        );
    }

    public function test_list_invoices_is_public(): void
    {
        $this->assertTrue((new ReflectionMethod(SalesService::class, 'listInvoices'))->isPublic());
    }

    public function test_list_invoices_accepts_order_id(): void
    {
        $ref    = new ReflectionMethod(SalesService::class, 'listInvoices');
        $params = $ref->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('orderId', $params[0]->getName());
        $this->assertSame('int', (string) $params[0]->getType());
    }

    // -------------------------------------------------------------------------
    // showInvoice
    // -------------------------------------------------------------------------

    public function test_sales_service_has_show_invoice_method(): void
    {
        $this->assertTrue(
            method_exists(SalesService::class, 'showInvoice'),
            'SalesService must expose a public showInvoice() method.'
        );
    }

    public function test_show_invoice_is_public(): void
    {
        $this->assertTrue((new ReflectionMethod(SalesService::class, 'showInvoice'))->isPublic());
    }

    public function test_show_invoice_accepts_id(): void
    {
        $ref    = new ReflectionMethod(SalesService::class, 'showInvoice');
        $params = $ref->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('id', $params[0]->getName());
    }

    public function test_show_invoice_return_type_is_sales_invoice(): void
    {
        $ref = new ReflectionMethod(SalesService::class, 'showInvoice');
        $this->assertSame(SalesInvoice::class, (string) $ref->getReturnType());
    }

    // -------------------------------------------------------------------------
    // Service instantiation
    // -------------------------------------------------------------------------

    public function test_sales_service_can_be_instantiated_with_repository_contract(): void
    {
        $repo    = $this->createMock(SalesRepositoryContract::class);
        $service = new SalesService($repo);

        $this->assertInstanceOf(SalesService::class, $service);
    }

    // -------------------------------------------------------------------------
    // Regression guard — existing methods still present
    // -------------------------------------------------------------------------

    public function test_create_order_method_still_present(): void
    {
        $this->assertTrue(method_exists(SalesService::class, 'createOrder'));
    }

    public function test_confirm_order_method_still_present(): void
    {
        $this->assertTrue(method_exists(SalesService::class, 'confirmOrder'));
    }
}
