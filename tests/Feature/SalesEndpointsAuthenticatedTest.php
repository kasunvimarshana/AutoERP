<?php

declare(strict_types=1);

namespace Tests\Feature;

use DateTimeImmutable;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\PresenceVerifierInterface;
use Modules\Auth\Application\Contracts\AuthorizationServiceInterface;
use Modules\Sales\Application\Contracts\ApproveSalesReturnServiceInterface;
use Modules\Sales\Application\Contracts\CancelSalesOrderServiceInterface;
use Modules\Sales\Application\Contracts\ConfirmSalesOrderServiceInterface;
use Modules\Sales\Application\Contracts\CreateSalesInvoiceServiceInterface;
use Modules\Sales\Application\Contracts\CreateSalesOrderServiceInterface;
use Modules\Sales\Application\Contracts\CreateSalesReturnServiceInterface;
use Modules\Sales\Application\Contracts\CreateShipmentServiceInterface;
use Modules\Sales\Application\Contracts\DeleteSalesInvoiceServiceInterface;
use Modules\Sales\Application\Contracts\DeleteSalesOrderServiceInterface;
use Modules\Sales\Application\Contracts\DeleteSalesReturnServiceInterface;
use Modules\Sales\Application\Contracts\DeleteShipmentServiceInterface;
use Modules\Sales\Application\Contracts\FindSalesInvoiceServiceInterface;
use Modules\Sales\Application\Contracts\FindSalesOrderServiceInterface;
use Modules\Sales\Application\Contracts\FindSalesReturnServiceInterface;
use Modules\Sales\Application\Contracts\FindShipmentServiceInterface;
use Modules\Sales\Application\Contracts\PostSalesInvoiceServiceInterface;
use Modules\Sales\Application\Contracts\ProcessShipmentServiceInterface;
use Modules\Sales\Application\Contracts\ReceiveSalesReturnServiceInterface;
use Modules\Sales\Application\Contracts\RecordSalesPaymentServiceInterface;
use Modules\Sales\Application\Contracts\RecordSalesRefundServiceInterface;
use Modules\Sales\Application\Contracts\UpdateSalesInvoiceServiceInterface;
use Modules\Sales\Application\Contracts\UpdateSalesOrderServiceInterface;
use Modules\Sales\Application\Contracts\UpdateSalesReturnServiceInterface;
use Modules\Sales\Application\Contracts\UpdateShipmentServiceInterface;
use Modules\Sales\Domain\Entities\SalesInvoice;
use Modules\Sales\Domain\Entities\SalesOrder;
use Modules\Sales\Domain\Entities\SalesReturn;
use Modules\Sales\Domain\Entities\Shipment;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Tests\TestCase;

class SalesEndpointsAuthenticatedTest extends TestCase
{
    private UserModel $authUser;

    private SalesOrder $salesOrder;

    private Shipment $shipment;

    private SalesInvoice $salesInvoice;

    private SalesReturn $salesReturn;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authUser = new UserModel([
            'name'     => 'Sales Admin',
            'email'    => 'sales-admin@example.com',
            'password' => 'hashed',
        ]);
        $this->authUser->setAttribute('id', 99);
        $this->authUser->setAttribute('tenant_id', 1);

        $authorizationService = $this->createMock(AuthorizationServiceInterface::class);
        $authorizationService->method('can')->willReturn(true);
        $this->app->instance(AuthorizationServiceInterface::class, $authorizationService);

        $presenceVerifier = $this->createMock(PresenceVerifierInterface::class);
        $presenceVerifier->method('getCount')->willReturn(1);
        $presenceVerifier->method('getMultiCount')->willReturn(1);
        $this->app['validator']->setPresenceVerifier($presenceVerifier);

        $now = new DateTimeImmutable('2026-01-15 10:00:00');

        $this->salesOrder = new SalesOrder(
            tenantId: 1,
            customerId: 2,
            warehouseId: 3,
            currencyId: 1,
            orderDate: $now,
            id: 1,
        );

        $this->shipment = new Shipment(
            tenantId: 1,
            customerId: 2,
            warehouseId: 3,
            currencyId: 1,
            salesOrderId: 1,
            id: 1,
        );

        $this->salesInvoice = new SalesInvoice(
            tenantId: 1,
            customerId: 2,
            currencyId: 1,
            invoiceDate: $now,
            dueDate: $now,
            salesOrderId: 1,
            id: 1,
        );

        $this->salesReturn = new SalesReturn(
            tenantId: 1,
            customerId: 2,
            currencyId: 1,
            returnDate: $now,
            id: 1,
        );
    }

    // -------------------------------------------------------------------------
    // SalesOrder
    // -------------------------------------------------------------------------

    public function test_sales_order_index_returns_paginated_list(): void
    {
        $paginator = $this->makePaginator([$this->salesOrder]);

        $findService = $this->createMock(FindSalesOrderServiceInterface::class);
        $findService->method('list')->willReturn($paginator);
        $this->app->instance(FindSalesOrderServiceInterface::class, $findService);

        $response = $this->actingAsUser()
            ->getJson('/api/sales-orders?tenant_id=1');

        $response->assertOk()->assertJsonPath('data.0.id', 1);
    }

    public function test_sales_order_store_returns_created(): void
    {
        $createService = $this->createMock(CreateSalesOrderServiceInterface::class);
        $createService->method('execute')->willReturn($this->salesOrder);
        $this->app->instance(CreateSalesOrderServiceInterface::class, $createService);

        $findService = $this->createMock(FindSalesOrderServiceInterface::class);
        $this->app->instance(FindSalesOrderServiceInterface::class, $findService);

        $response = $this->actingAsUser()
            ->postJson('/api/sales-orders', [
                'tenant_id'   => 1,
                'customer_id' => 2,
                'warehouse_id' => 3,
                'currency_id' => 1,
                'order_date'  => '2026-01-15',
            ]);

        $response->assertStatus(201)->assertJsonPath('data.id', 1);
    }

    public function test_sales_order_show_returns_entity(): void
    {
        $findService = $this->createMock(FindSalesOrderServiceInterface::class);
        $findService->method('find')->with(1)->willReturn($this->salesOrder);
        $this->app->instance(FindSalesOrderServiceInterface::class, $findService);

        $response = $this->actingAsUser()
            ->getJson('/api/sales-orders/1');

        $response->assertOk()->assertJsonPath('data.id', 1);
    }

    public function test_sales_order_update_returns_entity(): void
    {
        $findService = $this->createMock(FindSalesOrderServiceInterface::class);
        $findService->method('find')->with(1)->willReturn($this->salesOrder);
        $this->app->instance(FindSalesOrderServiceInterface::class, $findService);

        $updateService = $this->createMock(UpdateSalesOrderServiceInterface::class);
        $updateService->method('execute')->willReturn($this->salesOrder);
        $this->app->instance(UpdateSalesOrderServiceInterface::class, $updateService);

        $response = $this->actingAsUser()
            ->putJson('/api/sales-orders/1', [
                'tenant_id'    => 1,
                'customer_id'  => 2,
                'warehouse_id' => 3,
                'currency_id'  => 1,
                'order_date'   => '2026-01-15',
            ]);

        $response->assertOk()->assertJsonPath('data.id', 1);
    }

    public function test_sales_order_destroy_returns_message(): void
    {
        $findService = $this->createMock(FindSalesOrderServiceInterface::class);
        $findService->method('find')->with(1)->willReturn($this->salesOrder);
        $this->app->instance(FindSalesOrderServiceInterface::class, $findService);

        $deleteService = $this->createMock(DeleteSalesOrderServiceInterface::class);
        $deleteService->method('execute')->willReturn(null);
        $this->app->instance(DeleteSalesOrderServiceInterface::class, $deleteService);

        $response = $this->actingAsUser()
            ->deleteJson('/api/sales-orders/1');

        $response->assertOk()->assertJsonPath('message', 'Sales order deleted successfully');
    }

    public function test_sales_order_confirm_returns_entity(): void
    {
        $findService = $this->createMock(FindSalesOrderServiceInterface::class);
        $findService->method('find')->with(1)->willReturn($this->salesOrder);
        $this->app->instance(FindSalesOrderServiceInterface::class, $findService);

        $confirmService = $this->createMock(ConfirmSalesOrderServiceInterface::class);
        $confirmService->method('execute')->willReturn($this->salesOrder);
        $this->app->instance(ConfirmSalesOrderServiceInterface::class, $confirmService);

        $response = $this->actingAsUser()
            ->postJson('/api/sales-orders/1/confirm');

        $response->assertOk()->assertJsonPath('data.id', 1);
    }

    public function test_sales_order_cancel_returns_entity(): void
    {
        $findService = $this->createMock(FindSalesOrderServiceInterface::class);
        $findService->method('find')->with(1)->willReturn($this->salesOrder);
        $this->app->instance(FindSalesOrderServiceInterface::class, $findService);

        $cancelService = $this->createMock(CancelSalesOrderServiceInterface::class);
        $cancelService->method('execute')->willReturn($this->salesOrder);
        $this->app->instance(CancelSalesOrderServiceInterface::class, $cancelService);

        $response = $this->actingAsUser()
            ->postJson('/api/sales-orders/1/cancel');

        $response->assertOk()->assertJsonPath('data.id', 1);
    }

    // -------------------------------------------------------------------------
    // Shipment
    // -------------------------------------------------------------------------

    public function test_shipment_index_returns_paginated_list(): void
    {
        $paginator = $this->makePaginator([$this->shipment]);

        $findService = $this->createMock(FindShipmentServiceInterface::class);
        $findService->method('list')->willReturn($paginator);
        $this->app->instance(FindShipmentServiceInterface::class, $findService);

        $response = $this->actingAsUser()
            ->getJson('/api/shipments?tenant_id=1');

        $response->assertOk()->assertJsonPath('data.0.id', 1);
    }

    public function test_shipment_store_returns_created(): void
    {
        $createService = $this->createMock(CreateShipmentServiceInterface::class);
        $createService->method('execute')->willReturn($this->shipment);
        $this->app->instance(CreateShipmentServiceInterface::class, $createService);

        $findService = $this->createMock(FindShipmentServiceInterface::class);
        $this->app->instance(FindShipmentServiceInterface::class, $findService);

        $response = $this->actingAsUser()
            ->postJson('/api/shipments', [
                'tenant_id'    => 1,
                'customer_id'  => 2,
                'warehouse_id' => 3,
                'currency_id'  => 1,
            ]);

        $response->assertStatus(201)->assertJsonPath('data.id', 1);
    }

    public function test_shipment_show_returns_entity(): void
    {
        $findService = $this->createMock(FindShipmentServiceInterface::class);
        $findService->method('find')->with(1)->willReturn($this->shipment);
        $this->app->instance(FindShipmentServiceInterface::class, $findService);

        $response = $this->actingAsUser()
            ->getJson('/api/shipments/1');

        $response->assertOk()->assertJsonPath('data.id', 1);
    }

    public function test_shipment_update_returns_entity(): void
    {
        $findService = $this->createMock(FindShipmentServiceInterface::class);
        $findService->method('find')->with(1)->willReturn($this->shipment);
        $this->app->instance(FindShipmentServiceInterface::class, $findService);

        $updateService = $this->createMock(UpdateShipmentServiceInterface::class);
        $updateService->method('execute')->willReturn($this->shipment);
        $this->app->instance(UpdateShipmentServiceInterface::class, $updateService);

        $response = $this->actingAsUser()
            ->putJson('/api/shipments/1', [
                'tenant_id'    => 1,
                'customer_id'  => 2,
                'warehouse_id' => 3,
                'currency_id'  => 1,
            ]);

        $response->assertOk()->assertJsonPath('data.id', 1);
    }

    public function test_shipment_destroy_returns_message(): void
    {
        $findService = $this->createMock(FindShipmentServiceInterface::class);
        $findService->method('find')->with(1)->willReturn($this->shipment);
        $this->app->instance(FindShipmentServiceInterface::class, $findService);

        $deleteService = $this->createMock(DeleteShipmentServiceInterface::class);
        $deleteService->method('execute')->willReturn(null);
        $this->app->instance(DeleteShipmentServiceInterface::class, $deleteService);

        $response = $this->actingAsUser()
            ->deleteJson('/api/shipments/1');

        $response->assertOk()->assertJsonPath('message', 'Shipment deleted successfully');
    }

    public function test_shipment_process_returns_entity(): void
    {
        $findService = $this->createMock(FindShipmentServiceInterface::class);
        $findService->method('find')->with(1)->willReturn($this->shipment);
        $this->app->instance(FindShipmentServiceInterface::class, $findService);

        $processService = $this->createMock(ProcessShipmentServiceInterface::class);
        $processService->method('execute')->willReturn($this->shipment);
        $this->app->instance(ProcessShipmentServiceInterface::class, $processService);

        $response = $this->actingAsUser()
            ->postJson('/api/shipments/1/process');

        $response->assertOk()->assertJsonPath('data.id', 1);
    }

    // -------------------------------------------------------------------------
    // SalesInvoice
    // -------------------------------------------------------------------------

    public function test_sales_invoice_index_returns_paginated_list(): void
    {
        $paginator = $this->makePaginator([$this->salesInvoice]);

        $findService = $this->createMock(FindSalesInvoiceServiceInterface::class);
        $findService->method('list')->willReturn($paginator);
        $this->app->instance(FindSalesInvoiceServiceInterface::class, $findService);

        $response = $this->actingAsUser()
            ->getJson('/api/sales-invoices?tenant_id=1');

        $response->assertOk()->assertJsonPath('data.0.id', 1);
    }

    public function test_sales_invoice_store_returns_created(): void
    {
        $createService = $this->createMock(CreateSalesInvoiceServiceInterface::class);
        $createService->method('execute')->willReturn($this->salesInvoice);
        $this->app->instance(CreateSalesInvoiceServiceInterface::class, $createService);

        $findService = $this->createMock(FindSalesInvoiceServiceInterface::class);
        $this->app->instance(FindSalesInvoiceServiceInterface::class, $findService);

        $response = $this->actingAsUser()
            ->postJson('/api/sales-invoices', [
                'tenant_id'    => 1,
                'customer_id'  => 2,
                'currency_id'  => 1,
                'invoice_date' => '2026-01-15',
                'due_date'     => '2026-02-15',
            ]);

        $response->assertStatus(201)->assertJsonPath('data.id', 1);
    }

    public function test_sales_invoice_show_returns_entity(): void
    {
        $findService = $this->createMock(FindSalesInvoiceServiceInterface::class);
        $findService->method('find')->with(1)->willReturn($this->salesInvoice);
        $this->app->instance(FindSalesInvoiceServiceInterface::class, $findService);

        $response = $this->actingAsUser()
            ->getJson('/api/sales-invoices/1');

        $response->assertOk()->assertJsonPath('data.id', 1);
    }

    public function test_sales_invoice_update_returns_entity(): void
    {
        $findService = $this->createMock(FindSalesInvoiceServiceInterface::class);
        $findService->method('find')->with(1)->willReturn($this->salesInvoice);
        $this->app->instance(FindSalesInvoiceServiceInterface::class, $findService);

        $updateService = $this->createMock(UpdateSalesInvoiceServiceInterface::class);
        $updateService->method('execute')->willReturn($this->salesInvoice);
        $this->app->instance(UpdateSalesInvoiceServiceInterface::class, $updateService);

        $response = $this->actingAsUser()
            ->putJson('/api/sales-invoices/1', [
                'tenant_id'    => 1,
                'customer_id'  => 2,
                'currency_id'  => 1,
                'invoice_date' => '2026-01-15',
                'due_date'     => '2026-02-15',
            ]);

        $response->assertOk()->assertJsonPath('data.id', 1);
    }

    public function test_sales_invoice_destroy_returns_message(): void
    {
        $findService = $this->createMock(FindSalesInvoiceServiceInterface::class);
        $findService->method('find')->with(1)->willReturn($this->salesInvoice);
        $this->app->instance(FindSalesInvoiceServiceInterface::class, $findService);

        $deleteService = $this->createMock(DeleteSalesInvoiceServiceInterface::class);
        $deleteService->method('execute')->willReturn(null);
        $this->app->instance(DeleteSalesInvoiceServiceInterface::class, $deleteService);

        $response = $this->actingAsUser()
            ->deleteJson('/api/sales-invoices/1');

        $response->assertOk()->assertJsonPath('message', 'Sales invoice deleted successfully');
    }

    public function test_sales_invoice_post_returns_entity(): void
    {
        $findService = $this->createMock(FindSalesInvoiceServiceInterface::class);
        $findService->method('find')->with(1)->willReturn($this->salesInvoice);
        $this->app->instance(FindSalesInvoiceServiceInterface::class, $findService);

        $postService = $this->createMock(PostSalesInvoiceServiceInterface::class);
        $postService->method('execute')->willReturn($this->salesInvoice);
        $this->app->instance(PostSalesInvoiceServiceInterface::class, $postService);

        $response = $this->actingAsUser()
            ->postJson('/api/sales-invoices/1/post');

        $response->assertOk()->assertJsonPath('data.id', 1);
    }

    public function test_sales_invoice_record_payment_returns_entity(): void
    {
        $findService = $this->createMock(FindSalesInvoiceServiceInterface::class);
        $findService->method('find')->with(1)->willReturn($this->salesInvoice);
        $this->app->instance(FindSalesInvoiceServiceInterface::class, $findService);

        $paymentService = $this->createMock(RecordSalesPaymentServiceInterface::class);
        $paymentService->method('execute')->willReturn($this->salesInvoice);
        $this->app->instance(RecordSalesPaymentServiceInterface::class, $paymentService);

        $response = $this->actingAsUser()
            ->postJson('/api/sales-invoices/1/record-payment', [
                'payment_number'    => 'PAY-001',
                'payment_method_id' => 1,
                'account_id'        => 1,
                'amount'            => 100.00,
                'currency_id'       => 1,
                'payment_date'      => '2026-01-20',
            ]);

        $response->assertOk()->assertJsonPath('data.id', 1);
    }

    public function test_sales_invoice_record_refund_returns_entity(): void
    {
        $findService = $this->createMock(FindSalesInvoiceServiceInterface::class);
        $findService->method('find')->with(1)->willReturn($this->salesInvoice);
        $this->app->instance(FindSalesInvoiceServiceInterface::class, $findService);

        $refundService = $this->createMock(RecordSalesRefundServiceInterface::class);
        $refundService->method('execute')->willReturn($this->salesInvoice);
        $this->app->instance(RecordSalesRefundServiceInterface::class, $refundService);

        $response = $this->actingAsUser()
            ->postJson('/api/sales-invoices/1/record-refund', [
                'refund_number'     => 'REF-001',
                'payment_method_id' => 1,
                'account_id'        => 1,
                'amount'            => 50.00,
                'currency_id'       => 1,
                'refund_date'       => '2026-01-22',
            ]);

        $response->assertOk()->assertJsonPath('data.id', 1);
    }

    // -------------------------------------------------------------------------
    // SalesReturn
    // -------------------------------------------------------------------------

    public function test_sales_return_index_returns_paginated_list(): void
    {
        $paginator = $this->makePaginator([$this->salesReturn]);

        $findService = $this->createMock(FindSalesReturnServiceInterface::class);
        $findService->method('list')->willReturn($paginator);
        $this->app->instance(FindSalesReturnServiceInterface::class, $findService);

        $response = $this->actingAsUser()
            ->getJson('/api/sales-returns?tenant_id=1');

        $response->assertOk()->assertJsonPath('data.0.id', 1);
    }

    public function test_sales_return_store_returns_created(): void
    {
        $createService = $this->createMock(CreateSalesReturnServiceInterface::class);
        $createService->method('execute')->willReturn($this->salesReturn);
        $this->app->instance(CreateSalesReturnServiceInterface::class, $createService);

        $findService = $this->createMock(FindSalesReturnServiceInterface::class);
        $this->app->instance(FindSalesReturnServiceInterface::class, $findService);

        $response = $this->actingAsUser()
            ->postJson('/api/sales-returns', [
                'tenant_id'   => 1,
                'customer_id' => 2,
                'currency_id' => 1,
                'return_date' => '2026-01-15',
            ]);

        $response->assertStatus(201)->assertJsonPath('data.id', 1);
    }

    public function test_sales_return_show_returns_entity(): void
    {
        $findService = $this->createMock(FindSalesReturnServiceInterface::class);
        $findService->method('find')->with(1)->willReturn($this->salesReturn);
        $this->app->instance(FindSalesReturnServiceInterface::class, $findService);

        $response = $this->actingAsUser()
            ->getJson('/api/sales-returns/1');

        $response->assertOk()->assertJsonPath('data.id', 1);
    }

    public function test_sales_return_update_returns_entity(): void
    {
        $findService = $this->createMock(FindSalesReturnServiceInterface::class);
        $findService->method('find')->with(1)->willReturn($this->salesReturn);
        $this->app->instance(FindSalesReturnServiceInterface::class, $findService);

        $updateService = $this->createMock(UpdateSalesReturnServiceInterface::class);
        $updateService->method('execute')->willReturn($this->salesReturn);
        $this->app->instance(UpdateSalesReturnServiceInterface::class, $updateService);

        $response = $this->actingAsUser()
            ->putJson('/api/sales-returns/1', [
                'tenant_id'   => 1,
                'customer_id' => 2,
                'currency_id' => 1,
                'return_date' => '2026-01-15',
            ]);

        $response->assertOk()->assertJsonPath('data.id', 1);
    }

    public function test_sales_return_destroy_returns_message(): void
    {
        $findService = $this->createMock(FindSalesReturnServiceInterface::class);
        $findService->method('find')->with(1)->willReturn($this->salesReturn);
        $this->app->instance(FindSalesReturnServiceInterface::class, $findService);

        $deleteService = $this->createMock(DeleteSalesReturnServiceInterface::class);
        $deleteService->method('execute')->willReturn(null);
        $this->app->instance(DeleteSalesReturnServiceInterface::class, $deleteService);

        $response = $this->actingAsUser()
            ->deleteJson('/api/sales-returns/1');

        $response->assertOk()->assertJsonPath('message', 'Sales return deleted successfully');
    }

    public function test_sales_return_approve_returns_entity(): void
    {
        $findService = $this->createMock(FindSalesReturnServiceInterface::class);
        $findService->method('find')->with(1)->willReturn($this->salesReturn);
        $this->app->instance(FindSalesReturnServiceInterface::class, $findService);

        $approveService = $this->createMock(ApproveSalesReturnServiceInterface::class);
        $approveService->method('execute')->willReturn($this->salesReturn);
        $this->app->instance(ApproveSalesReturnServiceInterface::class, $approveService);

        $response = $this->actingAsUser()
            ->postJson('/api/sales-returns/1/approve');

        $response->assertOk()->assertJsonPath('data.id', 1);
    }

    public function test_sales_return_receive_returns_entity(): void
    {
        $findService = $this->createMock(FindSalesReturnServiceInterface::class);
        $findService->method('find')->with(1)->willReturn($this->salesReturn);
        $this->app->instance(FindSalesReturnServiceInterface::class, $findService);

        $receiveService = $this->createMock(ReceiveSalesReturnServiceInterface::class);
        $receiveService->method('execute')->willReturn($this->salesReturn);
        $this->app->instance(ReceiveSalesReturnServiceInterface::class, $receiveService);

        $response = $this->actingAsUser()
            ->postJson('/api/sales-returns/1/receive');

        $response->assertOk()->assertJsonPath('data.id', 1);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function actingAsUser(): static
    {
        return $this->withHeader('X-Tenant-ID', '1')->actingAs(
            $this->authUser,
            (string) config('auth_context.guards.api', config('auth.defaults.guard', 'api'))
        );
    }

    private function makePaginator(array $items): LengthAwarePaginator
    {
        return new LengthAwarePaginator($items, count($items), 15, 1);
    }
}
