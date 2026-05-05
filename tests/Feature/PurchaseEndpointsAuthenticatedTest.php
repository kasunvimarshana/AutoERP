<?php

declare(strict_types=1);

namespace Tests\Feature;

use DateTimeImmutable;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\PresenceVerifierInterface;
use Modules\Auth\Application\Contracts\AuthorizationServiceInterface;
use Modules\Purchase\Application\Contracts\ApprovePurchaseInvoiceServiceInterface;
use Modules\Purchase\Application\Contracts\CancelPurchaseOrderServiceInterface;
use Modules\Purchase\Application\Contracts\ConfirmPurchaseOrderServiceInterface;
use Modules\Purchase\Application\Contracts\CreateGrnServiceInterface;
use Modules\Purchase\Application\Contracts\CreatePurchaseInvoiceServiceInterface;
use Modules\Purchase\Application\Contracts\CreatePurchaseOrderServiceInterface;
use Modules\Purchase\Application\Contracts\CreatePurchaseReturnServiceInterface;
use Modules\Purchase\Application\Contracts\DeleteGrnServiceInterface;
use Modules\Purchase\Application\Contracts\DeletePurchaseInvoiceServiceInterface;
use Modules\Purchase\Application\Contracts\DeletePurchaseOrderServiceInterface;
use Modules\Purchase\Application\Contracts\DeletePurchaseReturnServiceInterface;
use Modules\Purchase\Application\Contracts\FindGrnServiceInterface;
use Modules\Purchase\Application\Contracts\FindPurchaseInvoiceServiceInterface;
use Modules\Purchase\Application\Contracts\FindPurchaseOrderServiceInterface;
use Modules\Purchase\Application\Contracts\FindPurchaseReturnServiceInterface;
use Modules\Purchase\Application\Contracts\PostGrnServiceInterface;
use Modules\Purchase\Application\Contracts\PostPurchaseReturnServiceInterface;
use Modules\Purchase\Application\Contracts\RecordPurchasePaymentServiceInterface;
use Modules\Purchase\Application\Contracts\RecordPurchaseRefundServiceInterface;
use Modules\Purchase\Application\Contracts\SendPurchaseOrderServiceInterface;
use Modules\Purchase\Application\Contracts\UpdateGrnServiceInterface;
use Modules\Purchase\Application\Contracts\UpdatePurchaseInvoiceServiceInterface;
use Modules\Purchase\Application\Contracts\UpdatePurchaseOrderServiceInterface;
use Modules\Purchase\Application\Contracts\UpdatePurchaseReturnServiceInterface;
use Modules\Purchase\Domain\Entities\GrnHeader;
use Modules\Purchase\Domain\Entities\PurchaseInvoice;
use Modules\Purchase\Domain\Entities\PurchaseOrder;
use Modules\Purchase\Domain\Entities\PurchaseReturn;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Tests\TestCase;

class PurchaseEndpointsAuthenticatedTest extends TestCase
{
    private UserModel $authUser;

    private PurchaseOrder $purchaseOrder;

    private GrnHeader $grn;

    private PurchaseInvoice $invoice;

    private PurchaseReturn $purchaseReturn;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authUser = new UserModel([
            'name'     => 'Purchase Admin',
            'email'    => 'purchase-admin@example.com',
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

        $this->purchaseOrder = new PurchaseOrder(
            tenantId: 1,
            supplierId: 2,
            warehouseId: 3,
            poNumber: 'PO-001',
            status: 'draft',
            currencyId: 1,
            exchangeRate: '1.000000',
            orderDate: $now,
            createdBy: 99,
            id: 1,
        );

        $this->grn = new GrnHeader(
            tenantId: 1,
            supplierId: 2,
            warehouseId: 3,
            grnNumber: 'GRN-001',
            status: 'draft',
            receivedDate: $now,
            currencyId: 1,
            exchangeRate: '1.000000',
            createdBy: 99,
            purchaseOrderId: 1,
            id: 1,
        );

        $this->invoice = new PurchaseInvoice(
            tenantId: 1,
            supplierId: 2,
            invoiceNumber: 'PINV-001',
            status: 'draft',
            invoiceDate: $now,
            dueDate: $now,
            currencyId: 1,
            exchangeRate: '1.000000',
            id: 1,
        );

        $this->purchaseReturn = new PurchaseReturn(
            tenantId: 1,
            supplierId: 2,
            returnNumber: 'PR-001',
            status: 'draft',
            returnDate: $now,
            currencyId: 1,
            exchangeRate: '1.000000',
            id: 1,
        );
    }

    // -------------------------------------------------------------------------
    // PurchaseOrder
    // -------------------------------------------------------------------------

    public function test_purchase_order_index_returns_paginated_list(): void
    {
        $paginator = $this->makePaginator([$this->purchaseOrder]);

        $findService = $this->createMock(FindPurchaseOrderServiceInterface::class);
        $findService->method('list')->willReturn($paginator);
        $this->app->instance(FindPurchaseOrderServiceInterface::class, $findService);

        $response = $this->actingAsUser()
            ->getJson('/api/purchase-orders?tenant_id=1');

        $response->assertOk()->assertJsonPath('data.0.id', 1);
    }

    public function test_purchase_order_store_returns_created(): void
    {
        $createService = $this->createMock(CreatePurchaseOrderServiceInterface::class);
        $createService->method('execute')->willReturn($this->purchaseOrder);
        $this->app->instance(CreatePurchaseOrderServiceInterface::class, $createService);

        $findService = $this->createMock(FindPurchaseOrderServiceInterface::class);
        $this->app->instance(FindPurchaseOrderServiceInterface::class, $findService);

        $response = $this->actingAsUser()
            ->postJson('/api/purchase-orders', [
                'tenant_id'   => 1,
                'supplier_id' => 2,
                'warehouse_id' => 3,
                'currency_id' => 1,
                'po_number'   => 'PO-001',
                'order_date'  => '2026-01-15',
                'created_by'  => 99,
            ]);

        $response->assertStatus(201)->assertJsonPath('data.id', 1);
    }

    public function test_purchase_order_show_returns_entity(): void
    {
        $findService = $this->createMock(FindPurchaseOrderServiceInterface::class);
        $findService->method('find')->with(1)->willReturn($this->purchaseOrder);
        $this->app->instance(FindPurchaseOrderServiceInterface::class, $findService);

        $response = $this->actingAsUser()
            ->getJson('/api/purchase-orders/1');

        $response->assertOk()->assertJsonPath('data.id', 1);
    }

    public function test_purchase_order_update_returns_entity(): void
    {
        $findService = $this->createMock(FindPurchaseOrderServiceInterface::class);
        $findService->method('find')->with(1)->willReturn($this->purchaseOrder);
        $this->app->instance(FindPurchaseOrderServiceInterface::class, $findService);

        $updateService = $this->createMock(UpdatePurchaseOrderServiceInterface::class);
        $updateService->method('execute')->willReturn($this->purchaseOrder);
        $this->app->instance(UpdatePurchaseOrderServiceInterface::class, $updateService);

        $response = $this->actingAsUser()
            ->putJson('/api/purchase-orders/1', [
                'tenant_id'    => 1,
                'supplier_id'  => 2,
                'warehouse_id' => 3,
                'currency_id'  => 1,
                'po_number'    => 'PO-001',
                'order_date'   => '2026-01-15',
                'created_by'   => 99,
            ]);

        $response->assertOk()->assertJsonPath('data.id', 1);
    }

    public function test_purchase_order_destroy_returns_message(): void
    {
        $findService = $this->createMock(FindPurchaseOrderServiceInterface::class);
        $findService->method('find')->with(1)->willReturn($this->purchaseOrder);
        $this->app->instance(FindPurchaseOrderServiceInterface::class, $findService);

        $deleteService = $this->createMock(DeletePurchaseOrderServiceInterface::class);
        $deleteService->method('execute')->willReturn(null);
        $this->app->instance(DeletePurchaseOrderServiceInterface::class, $deleteService);

        $response = $this->actingAsUser()
            ->deleteJson('/api/purchase-orders/1');

        $response->assertOk()->assertJsonPath('message', 'Purchase order deleted successfully');
    }

    public function test_purchase_order_confirm_returns_entity(): void
    {
        $findService = $this->createMock(FindPurchaseOrderServiceInterface::class);
        $findService->method('find')->with(1)->willReturn($this->purchaseOrder);
        $this->app->instance(FindPurchaseOrderServiceInterface::class, $findService);

        $confirmService = $this->createMock(ConfirmPurchaseOrderServiceInterface::class);
        $confirmService->method('execute')->willReturn($this->purchaseOrder);
        $this->app->instance(ConfirmPurchaseOrderServiceInterface::class, $confirmService);

        $response = $this->actingAsUser()
            ->postJson('/api/purchase-orders/1/confirm');

        $response->assertOk()->assertJsonPath('data.id', 1);
    }

    public function test_purchase_order_send_returns_entity(): void
    {
        $findService = $this->createMock(FindPurchaseOrderServiceInterface::class);
        $findService->method('find')->with(1)->willReturn($this->purchaseOrder);
        $this->app->instance(FindPurchaseOrderServiceInterface::class, $findService);

        $sendService = $this->createMock(SendPurchaseOrderServiceInterface::class);
        $sendService->method('execute')->willReturn($this->purchaseOrder);
        $this->app->instance(SendPurchaseOrderServiceInterface::class, $sendService);

        $response = $this->actingAsUser()
            ->postJson('/api/purchase-orders/1/send');

        $response->assertOk()->assertJsonPath('data.id', 1);
    }

    public function test_purchase_order_cancel_returns_entity(): void
    {
        $findService = $this->createMock(FindPurchaseOrderServiceInterface::class);
        $findService->method('find')->with(1)->willReturn($this->purchaseOrder);
        $this->app->instance(FindPurchaseOrderServiceInterface::class, $findService);

        $cancelService = $this->createMock(CancelPurchaseOrderServiceInterface::class);
        $cancelService->method('execute')->willReturn($this->purchaseOrder);
        $this->app->instance(CancelPurchaseOrderServiceInterface::class, $cancelService);

        $response = $this->actingAsUser()
            ->postJson('/api/purchase-orders/1/cancel');

        $response->assertOk()->assertJsonPath('data.id', 1);
    }

    // -------------------------------------------------------------------------
    // GRN
    // -------------------------------------------------------------------------

    public function test_grn_index_returns_paginated_list(): void
    {
        $paginator = $this->makePaginator([$this->grn]);

        $findService = $this->createMock(FindGrnServiceInterface::class);
        $findService->method('list')->willReturn($paginator);
        $this->app->instance(FindGrnServiceInterface::class, $findService);

        $response = $this->actingAsUser()
            ->getJson('/api/grns?tenant_id=1');

        $response->assertOk()->assertJsonPath('data.0.id', 1);
    }

    public function test_grn_store_returns_created(): void
    {
        $createService = $this->createMock(CreateGrnServiceInterface::class);
        $createService->method('execute')->willReturn($this->grn);
        $this->app->instance(CreateGrnServiceInterface::class, $createService);

        $findService = $this->createMock(FindGrnServiceInterface::class);
        $this->app->instance(FindGrnServiceInterface::class, $findService);

        $response = $this->actingAsUser()
            ->postJson('/api/grns', [
                'tenant_id'     => 1,
                'supplier_id'   => 2,
                'warehouse_id'  => 3,
                'grn_number'    => 'GRN-001',
                'received_date' => '2026-01-15',
                'currency_id'   => 1,
                'created_by'    => 99,
            ]);

        $response->assertStatus(201)->assertJsonPath('data.id', 1);
    }

    public function test_grn_show_returns_entity(): void
    {
        $findService = $this->createMock(FindGrnServiceInterface::class);
        $findService->method('find')->with(1)->willReturn($this->grn);
        $this->app->instance(FindGrnServiceInterface::class, $findService);

        $response = $this->actingAsUser()
            ->getJson('/api/grns/1');

        $response->assertOk()->assertJsonPath('data.id', 1);
    }

    public function test_grn_update_returns_entity(): void
    {
        $findService = $this->createMock(FindGrnServiceInterface::class);
        $findService->method('find')->with(1)->willReturn($this->grn);
        $this->app->instance(FindGrnServiceInterface::class, $findService);

        $updateService = $this->createMock(UpdateGrnServiceInterface::class);
        $updateService->method('execute')->willReturn($this->grn);
        $this->app->instance(UpdateGrnServiceInterface::class, $updateService);

        $response = $this->actingAsUser()
            ->putJson('/api/grns/1', [
                'tenant_id'     => 1,
                'supplier_id'   => 2,
                'warehouse_id'  => 3,
                'grn_number'    => 'GRN-001',
                'received_date' => '2026-01-15',
                'currency_id'   => 1,
                'created_by'    => 99,
            ]);

        $response->assertOk()->assertJsonPath('data.id', 1);
    }

    public function test_grn_destroy_returns_message(): void
    {
        $findService = $this->createMock(FindGrnServiceInterface::class);
        $findService->method('find')->with(1)->willReturn($this->grn);
        $this->app->instance(FindGrnServiceInterface::class, $findService);

        $deleteService = $this->createMock(DeleteGrnServiceInterface::class);
        $deleteService->method('execute')->willReturn(null);
        $this->app->instance(DeleteGrnServiceInterface::class, $deleteService);

        $response = $this->actingAsUser()
            ->deleteJson('/api/grns/1');

        $response->assertOk()->assertJsonPath('message', 'GRN deleted successfully');
    }

    public function test_grn_post_returns_entity(): void
    {
        $findService = $this->createMock(FindGrnServiceInterface::class);
        $findService->method('find')->with(1)->willReturn($this->grn);
        $this->app->instance(FindGrnServiceInterface::class, $findService);

        $postService = $this->createMock(PostGrnServiceInterface::class);
        $postService->method('execute')->willReturn($this->grn);
        $this->app->instance(PostGrnServiceInterface::class, $postService);

        $response = $this->actingAsUser()
            ->postJson('/api/grns/1/post');

        $response->assertOk()->assertJsonPath('data.id', 1);
    }

    // -------------------------------------------------------------------------
    // PurchaseInvoice
    // -------------------------------------------------------------------------

    public function test_purchase_invoice_index_returns_paginated_list(): void
    {
        $paginator = $this->makePaginator([$this->invoice]);

        $findService = $this->createMock(FindPurchaseInvoiceServiceInterface::class);
        $findService->method('list')->willReturn($paginator);
        $this->app->instance(FindPurchaseInvoiceServiceInterface::class, $findService);

        $response = $this->actingAsUser()
            ->getJson('/api/purchase-invoices?tenant_id=1');

        $response->assertOk()->assertJsonPath('data.0.id', 1);
    }

    public function test_purchase_invoice_store_returns_created(): void
    {
        $createService = $this->createMock(CreatePurchaseInvoiceServiceInterface::class);
        $createService->method('execute')->willReturn($this->invoice);
        $this->app->instance(CreatePurchaseInvoiceServiceInterface::class, $createService);

        $findService = $this->createMock(FindPurchaseInvoiceServiceInterface::class);
        $this->app->instance(FindPurchaseInvoiceServiceInterface::class, $findService);

        $response = $this->actingAsUser()
            ->postJson('/api/purchase-invoices', [
                'tenant_id'      => 1,
                'supplier_id'    => 2,
                'invoice_number' => 'PINV-001',
                'invoice_date'   => '2026-01-15',
                'due_date'       => '2026-02-15',
                'currency_id'    => 1,
            ]);

        $response->assertStatus(201)->assertJsonPath('data.id', 1);
    }

    public function test_purchase_invoice_show_returns_entity(): void
    {
        $findService = $this->createMock(FindPurchaseInvoiceServiceInterface::class);
        $findService->method('find')->with(1)->willReturn($this->invoice);
        $this->app->instance(FindPurchaseInvoiceServiceInterface::class, $findService);

        $response = $this->actingAsUser()
            ->getJson('/api/purchase-invoices/1');

        $response->assertOk()->assertJsonPath('data.id', 1);
    }

    public function test_purchase_invoice_update_returns_entity(): void
    {
        $findService = $this->createMock(FindPurchaseInvoiceServiceInterface::class);
        $findService->method('find')->with(1)->willReturn($this->invoice);
        $this->app->instance(FindPurchaseInvoiceServiceInterface::class, $findService);

        $updateService = $this->createMock(UpdatePurchaseInvoiceServiceInterface::class);
        $updateService->method('execute')->willReturn($this->invoice);
        $this->app->instance(UpdatePurchaseInvoiceServiceInterface::class, $updateService);

        $response = $this->actingAsUser()
            ->putJson('/api/purchase-invoices/1', [
                'tenant_id'      => 1,
                'supplier_id'    => 2,
                'invoice_number' => 'PINV-001',
                'invoice_date'   => '2026-01-15',
                'due_date'       => '2026-02-15',
                'currency_id'    => 1,
            ]);

        $response->assertOk()->assertJsonPath('data.id', 1);
    }

    public function test_purchase_invoice_destroy_returns_message(): void
    {
        $findService = $this->createMock(FindPurchaseInvoiceServiceInterface::class);
        $findService->method('find')->with(1)->willReturn($this->invoice);
        $this->app->instance(FindPurchaseInvoiceServiceInterface::class, $findService);

        $deleteService = $this->createMock(DeletePurchaseInvoiceServiceInterface::class);
        $deleteService->method('execute')->willReturn(null);
        $this->app->instance(DeletePurchaseInvoiceServiceInterface::class, $deleteService);

        $response = $this->actingAsUser()
            ->deleteJson('/api/purchase-invoices/1');

        $response->assertOk()->assertJsonPath('message', 'Purchase invoice deleted successfully');
    }

    public function test_purchase_invoice_approve_returns_entity(): void
    {
        $findService = $this->createMock(FindPurchaseInvoiceServiceInterface::class);
        $findService->method('find')->with(1)->willReturn($this->invoice);
        $this->app->instance(FindPurchaseInvoiceServiceInterface::class, $findService);

        $approveService = $this->createMock(ApprovePurchaseInvoiceServiceInterface::class);
        $approveService->method('execute')->willReturn($this->invoice);
        $this->app->instance(ApprovePurchaseInvoiceServiceInterface::class, $approveService);

        $response = $this->actingAsUser()
            ->postJson('/api/purchase-invoices/1/approve');

        $response->assertOk()->assertJsonPath('data.id', 1);
    }

    public function test_purchase_invoice_record_payment_returns_entity(): void
    {
        $findService = $this->createMock(FindPurchaseInvoiceServiceInterface::class);
        $findService->method('find')->with(1)->willReturn($this->invoice);
        $this->app->instance(FindPurchaseInvoiceServiceInterface::class, $findService);

        $paymentService = $this->createMock(RecordPurchasePaymentServiceInterface::class);
        $paymentService->method('execute')->willReturn($this->invoice);
        $this->app->instance(RecordPurchasePaymentServiceInterface::class, $paymentService);

        $response = $this->actingAsUser()
            ->postJson('/api/purchase-invoices/1/payment', [
                'payment_number'    => 'PAY-001',
                'payment_method_id' => 1,
                'account_id'        => 1,
                'amount'            => 100.00,
                'currency_id'       => 1,
                'payment_date'      => '2026-01-20',
            ]);

        $response->assertOk()->assertJsonPath('data.id', 1);
    }

    public function test_purchase_invoice_record_refund_returns_entity(): void
    {
        $findService = $this->createMock(FindPurchaseInvoiceServiceInterface::class);
        $findService->method('find')->with(1)->willReturn($this->invoice);
        $this->app->instance(FindPurchaseInvoiceServiceInterface::class, $findService);

        $refundService = $this->createMock(RecordPurchaseRefundServiceInterface::class);
        $refundService->method('execute')->willReturn($this->invoice);
        $this->app->instance(RecordPurchaseRefundServiceInterface::class, $refundService);

        $response = $this->actingAsUser()
            ->postJson('/api/purchase-invoices/1/refund', [
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
    // PurchaseReturn
    // -------------------------------------------------------------------------

    public function test_purchase_return_index_returns_paginated_list(): void
    {
        $paginator = $this->makePaginator([$this->purchaseReturn]);

        $findService = $this->createMock(FindPurchaseReturnServiceInterface::class);
        $findService->method('list')->willReturn($paginator);
        $this->app->instance(FindPurchaseReturnServiceInterface::class, $findService);

        $response = $this->actingAsUser()
            ->getJson('/api/purchase-returns?tenant_id=1');

        $response->assertOk()->assertJsonPath('data.0.id', 1);
    }

    public function test_purchase_return_store_returns_created(): void
    {
        $createService = $this->createMock(CreatePurchaseReturnServiceInterface::class);
        $createService->method('execute')->willReturn($this->purchaseReturn);
        $this->app->instance(CreatePurchaseReturnServiceInterface::class, $createService);

        $findService = $this->createMock(FindPurchaseReturnServiceInterface::class);
        $this->app->instance(FindPurchaseReturnServiceInterface::class, $findService);

        $response = $this->actingAsUser()
            ->postJson('/api/purchase-returns', [
                'tenant_id'     => 1,
                'supplier_id'   => 2,
                'return_number' => 'PR-001',
                'return_date'   => '2026-01-15',
                'currency_id'   => 1,
            ]);

        $response->assertStatus(201)->assertJsonPath('data.id', 1);
    }

    public function test_purchase_return_show_returns_entity(): void
    {
        $findService = $this->createMock(FindPurchaseReturnServiceInterface::class);
        $findService->method('find')->with(1)->willReturn($this->purchaseReturn);
        $this->app->instance(FindPurchaseReturnServiceInterface::class, $findService);

        $response = $this->actingAsUser()
            ->getJson('/api/purchase-returns/1');

        $response->assertOk()->assertJsonPath('data.id', 1);
    }

    public function test_purchase_return_update_returns_entity(): void
    {
        $findService = $this->createMock(FindPurchaseReturnServiceInterface::class);
        $findService->method('find')->with(1)->willReturn($this->purchaseReturn);
        $this->app->instance(FindPurchaseReturnServiceInterface::class, $findService);

        $updateService = $this->createMock(UpdatePurchaseReturnServiceInterface::class);
        $updateService->method('execute')->willReturn($this->purchaseReturn);
        $this->app->instance(UpdatePurchaseReturnServiceInterface::class, $updateService);

        $response = $this->actingAsUser()
            ->putJson('/api/purchase-returns/1', [
                'tenant_id'     => 1,
                'supplier_id'   => 2,
                'return_number' => 'PR-001',
                'return_date'   => '2026-01-15',
                'currency_id'   => 1,
            ]);

        $response->assertOk()->assertJsonPath('data.id', 1);
    }

    public function test_purchase_return_destroy_returns_message(): void
    {
        $findService = $this->createMock(FindPurchaseReturnServiceInterface::class);
        $findService->method('find')->with(1)->willReturn($this->purchaseReturn);
        $this->app->instance(FindPurchaseReturnServiceInterface::class, $findService);

        $deleteService = $this->createMock(DeletePurchaseReturnServiceInterface::class);
        $deleteService->method('execute')->willReturn(null);
        $this->app->instance(DeletePurchaseReturnServiceInterface::class, $deleteService);

        $response = $this->actingAsUser()
            ->deleteJson('/api/purchase-returns/1');

        $response->assertOk()->assertJsonPath('message', 'Purchase return deleted successfully');
    }

    public function test_purchase_return_post_returns_entity(): void
    {
        $findService = $this->createMock(FindPurchaseReturnServiceInterface::class);
        $findService->method('find')->with(1)->willReturn($this->purchaseReturn);
        $this->app->instance(FindPurchaseReturnServiceInterface::class, $findService);

        $postService = $this->createMock(PostPurchaseReturnServiceInterface::class);
        $postService->method('execute')->willReturn($this->purchaseReturn);
        $this->app->instance(PostPurchaseReturnServiceInterface::class, $postService);

        $response = $this->actingAsUser()
            ->postJson('/api/purchase-returns/1/post');

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
