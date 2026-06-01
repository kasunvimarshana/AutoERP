<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Sales\Presentation\Http\Controllers\GdnHeaderController;
use Modules\Sales\Presentation\Http\Controllers\GdnLineController;
use Modules\Sales\Presentation\Http\Controllers\SalesIntegrationController;
use Modules\Sales\Presentation\Http\Controllers\SalesInvoiceController;
use Modules\Sales\Presentation\Http\Controllers\SalesLedgerNoteController;
use Modules\Sales\Presentation\Http\Controllers\SalesManagementController;
use Modules\Sales\Presentation\Http\Controllers\SalesOrderController;
use Modules\Sales\Presentation\Http\Controllers\SalesOrderLineController;
use Modules\Sales\Presentation\Http\Controllers\SalesPaymentController;
use Modules\Sales\Presentation\Http\Controllers\SalesReturnController;
use Modules\Sales\Presentation\Http\Controllers\SalesReturnLineController;
use Modules\Sales\Presentation\Http\Controllers\SalesWorkflowController;

$protectedGuard = (string) config('module-auth.protected_route_guard', 'auth-api');
$currentUserMiddleware = (string) config('core.current_user.middleware_alias', 'current.user');
$currentTenantMiddleware = (string) config('core.current_tenant.middleware_alias', 'current.tenant');
$currentOrganizationUnitMiddleware = (string) config(
    'core.current_organization_unit.middleware_alias',
    'current.organization-unit',
);

Route::prefix('api/sales')
    ->middleware([
        'api',
        'auth:'.$protectedGuard,
        $currentUserMiddleware,
        $currentTenantMiddleware,
        $currentOrganizationUnitMiddleware,
    ])
    ->name('sales.')
    ->group(function (): void {
        Route::apiResource('sales-orders', SalesOrderController::class);
        Route::apiResource('sales-order-lines', SalesOrderLineController::class);
        Route::apiResource('gdn-headers', GdnHeaderController::class);
        Route::apiResource('gdn-lines', GdnLineController::class);
        Route::apiResource('sales-returns', SalesReturnController::class);
        Route::apiResource('sales-return-lines', SalesReturnLineController::class);
        Route::apiResource('ledger-notes', SalesLedgerNoteController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::get('sales-invoices', [SalesInvoiceController::class, 'index'])
            ->name('sales-invoices.index');
        Route::get('sales-invoices/{id}', [SalesInvoiceController::class, 'show'])
            ->whereNumber('id')
            ->name('sales-invoices.show');
        Route::post('sales-invoices', [SalesInvoiceController::class, 'store'])
            ->name('sales-invoices.store');
        Route::post('sales-invoices/from-so', [SalesInvoiceController::class, 'storeFromSo'])
            ->name('sales-invoices.from-so');
        Route::post('sales-invoices/from-gdn', [SalesInvoiceController::class, 'storeFromGdn'])
            ->name('sales-invoices.from-gdn');
        Route::post('sales-invoices/from-multiple-gdns', [SalesInvoiceController::class, 'storeFromMultipleGdns'])
            ->name('sales-invoices.from-multiple-gdns');
        Route::match(['put', 'patch'], 'sales-invoices/{id}', [SalesInvoiceController::class, 'update'])
            ->whereNumber('id')
            ->name('sales-invoices.update');
        Route::delete('sales-invoices/{id}', [SalesInvoiceController::class, 'destroy'])
            ->whereNumber('id')
            ->name('sales-invoices.destroy');
        Route::post('sales-invoices/{id}/post', [SalesInvoiceController::class, 'post'])
            ->whereNumber('id')
            ->name('sales-invoices.post');
        Route::post('sales-invoices/{id}/cancel', [SalesInvoiceController::class, 'cancel'])
            ->whereNumber('id')
            ->name('sales-invoices.cancel');
        Route::post('sales-invoices/{id}/reverse', [SalesInvoiceController::class, 'reverse'])
            ->whereNumber('id')
            ->name('sales-invoices.reverse');
        Route::get('sales-invoices/{id}/lines', [SalesInvoiceController::class, 'lines'])
            ->whereNumber('id')
            ->name('sales-invoices.lines.index');
        Route::post('sales-invoices/{id}/lines', [SalesInvoiceController::class, 'createLine'])
            ->whereNumber('id')
            ->name('sales-invoices.lines.store');
        Route::patch('sales-invoice-lines/{id}', [SalesInvoiceController::class, 'updateLine'])
            ->whereNumber('id')
            ->name('sales-invoice-lines.update');
        Route::delete('sales-invoice-lines/{id}', [SalesInvoiceController::class, 'deleteLine'])
            ->whereNumber('id')
            ->name('sales-invoice-lines.destroy');

        Route::get('sales-payments', [SalesPaymentController::class, 'index'])
            ->name('sales-payments.index');
        Route::get('sales-payments/{id}', [SalesPaymentController::class, 'show'])
            ->whereNumber('id')
            ->name('sales-payments.show');
        Route::post('sales-payments', [SalesPaymentController::class, 'store'])
            ->name('sales-payments.store');
        Route::patch('sales-payments/{id}', [SalesPaymentController::class, 'update'])
            ->whereNumber('id')
            ->name('sales-payments.update');
        Route::delete('sales-payments/{id}', [SalesPaymentController::class, 'destroy'])
            ->whereNumber('id')
            ->name('sales-payments.destroy');
        Route::post('sales-payments/{id}/post', [SalesPaymentController::class, 'post'])
            ->whereNumber('id')
            ->name('sales-payments.post');
        Route::post('sales-payments/{id}/void', [SalesPaymentController::class, 'void'])
            ->whereNumber('id')
            ->name('sales-payments.void');
        Route::post('sales-payments/{id}/reverse', [SalesPaymentController::class, 'reverse'])
            ->whereNumber('id')
            ->name('sales-payments.reverse');
        Route::post('sales-payments/{id}/allocate', [SalesPaymentController::class, 'allocate'])
            ->whereNumber('id')
            ->name('sales-payments.allocate');
        Route::get('sales-payments/{id}/allocations', [SalesPaymentController::class, 'allocations'])
            ->whereNumber('id')
            ->name('sales-payments.allocations');

        Route::post('sales-advances', [SalesPaymentController::class, 'createAdvance'])
            ->name('sales-advances.store');
        Route::post('sales-advances/{id}/allocate', [SalesPaymentController::class, 'allocateAdvance'])
            ->whereNumber('id')
            ->name('sales-advances.allocate');
        Route::post('sales-refunds', [SalesPaymentController::class, 'refund'])
            ->name('sales-refunds.store');
        Route::post('sales-write-offs', [SalesPaymentController::class, 'writeOff'])
            ->name('sales-write-offs.store');

        Route::get('customer-outstanding', [SalesPaymentController::class, 'customerOutstanding'])
            ->name('customer-outstanding');
        Route::get('invoice-payment-status', [SalesPaymentController::class, 'invoicePaymentStatus'])
            ->name('invoice-payment-status');
        Route::get('available-so-lines-for-invoice', [SalesInvoiceController::class, 'availableSoLinesForInvoice'])
            ->name('available-so-lines-for-invoice');
        Route::get('available-gdn-lines-for-invoice', [SalesInvoiceController::class, 'availableGdnLinesForInvoice'])
            ->name('available-gdn-lines-for-invoice');
        Route::post('calculate-invoice', [SalesInvoiceController::class, 'calculateInvoice'])
            ->name('calculate-invoice');
        Route::post('validate-uom', [SalesInvoiceController::class, 'validateUom'])
            ->name('validate-uom');
        Route::post('preview-payment-allocation', [SalesPaymentController::class, 'previewPaymentAllocation'])
            ->name('preview-payment-allocation');
        Route::get('stock-availability', [SalesManagementController::class, 'stockAvailability'])
            ->name('stock-availability');

        Route::post('sales-orders/with-lines', [SalesManagementController::class, 'upsertSalesOrderWithLines'])
            ->name('sales-orders.with-lines.store');
        Route::put('sales-orders/{id}/with-lines', [
            SalesManagementController::class,
            'updateSalesOrderWithLines',
        ])
            ->name('sales-orders.with-lines.update');
        Route::put('sales-orders/{id}/lines/sync', [SalesManagementController::class, 'syncSalesOrderLines'])
            ->name('sales-orders.lines.sync');

        Route::post('gdn-headers/with-lines', [SalesManagementController::class, 'upsertGdnWithLines'])
            ->name('gdn-headers.with-lines.store');
        Route::put('gdn-headers/{id}/with-lines', [SalesManagementController::class, 'updateGdnWithLines'])
            ->name('gdn-headers.with-lines.update');
        Route::put('gdn-headers/{id}/lines/sync', [SalesManagementController::class, 'syncGdnLines'])
            ->name('gdn-headers.lines.sync');

        Route::post('sales-returns/with-lines', [
            SalesManagementController::class,
            'upsertSalesReturnWithLines',
        ])
            ->name('sales-returns.with-lines.store');
        Route::put('sales-returns/{id}/with-lines', [
            SalesManagementController::class,
            'updateSalesReturnWithLines',
        ])
            ->name('sales-returns.with-lines.update');
        Route::put('sales-returns/{id}/lines/sync', [SalesManagementController::class, 'syncSalesReturnLines'])
            ->name('sales-returns.lines.sync');

        Route::get('settings', [SalesManagementController::class, 'showSettings'])
            ->name('settings.show');
        Route::put('settings', [SalesManagementController::class, 'upsertSettings'])
            ->name('settings.upsert');
        Route::post('settings/initialize', [SalesManagementController::class, 'initializeSettings'])
            ->name('settings.initialize');

        Route::get('lookups/sales-orders/{salesOrderId}/available-lines', [
            SalesManagementController::class,
            'availableSalesOrderLinesForGdn',
        ])->name('lookups.sales-order-lines.available-for-gdn');
        Route::get('lookups/gdn-headers/{gdnHeaderId}/available-document-lines', [
            SalesManagementController::class,
            'availableGdnLinesForDocument',
        ])->name('lookups.gdn-lines.available-for-document');
        Route::get('lookups/returnable-lines', [SalesManagementController::class, 'returnableLines'])
            ->name('lookups.returnable-lines');
        Route::get('lookups/receivable-documents', [SalesManagementController::class, 'receivableDocuments'])
            ->name('lookups.receivable-documents');

        Route::prefix('integrations')
            ->group(function (): void {
                Route::prefix('workflows/{entityType}/{id}')
                    ->whereIn('entityType', ['sales_order', 'gdn_header', 'sales_return'])
                    ->group(function (): void {
                        Route::get('documents', [SalesIntegrationController::class, 'listDocuments'])
                            ->name('integrations.documents.index');
                        Route::post('documents', [SalesIntegrationController::class, 'createDocument'])
                            ->name('integrations.documents.store');
                        Route::get('documents/{documentId}', [SalesIntegrationController::class, 'showDocument'])
                            ->name('integrations.documents.show');
                        Route::post(
                            'documents/{documentId}/status',
                            [SalesIntegrationController::class, 'changeDocumentStatus']
                        )->name('integrations.documents.status');
                        Route::post(
                            'documents/{documentId}/lines/match',
                            [SalesIntegrationController::class, 'matchDocumentLine']
                        )->name('integrations.documents.lines.match');
                        Route::post(
                            'documents/{documentId}/lines/unmatch',
                            [SalesIntegrationController::class, 'unmatchDocumentLine']
                        )->name('integrations.documents.lines.unmatch');

                        Route::post('payments', [SalesIntegrationController::class, 'createPayment'])
                            ->name('integrations.payments.store');
                        Route::post('advances', [SalesIntegrationController::class, 'createAdvance'])
                            ->name('integrations.advances.store');
                        Route::post('payments/allocate', [SalesIntegrationController::class, 'allocatePayment'])
                            ->name('integrations.payments.allocate');
                        Route::post('advances/apply', [SalesIntegrationController::class, 'applyAdvance'])
                            ->name('integrations.advances.apply');
                        Route::get('payments/allocations', [
                            SalesIntegrationController::class,
                            'listPaymentAllocations',
                        ])->name('integrations.payments.allocations');
                        Route::get('payments/summary', [SalesIntegrationController::class, 'sourcePaymentSummary'])
                            ->name('integrations.payments.summary');
                    });

                Route::get('customers/receivables', [SalesIntegrationController::class, 'customerReceivables'])
                    ->name('integrations.customers.receivables');
                Route::get('customers/advances', [SalesIntegrationController::class, 'customerAdvanceBalances'])
                    ->name('integrations.customers.advances');

                Route::post('payments/{paymentId}/post', [SalesIntegrationController::class, 'postPayment'])
                    ->name('integrations.payments.post');
                Route::post('payments/{paymentId}/reverse', [SalesIntegrationController::class, 'reversePayment'])
                    ->name('integrations.payments.reverse');
                Route::post('payments/{paymentId}/refund', [SalesIntegrationController::class, 'refundPayment'])
                    ->name('integrations.payments.refund');
            });

        Route::prefix('workflows/{entityType}/{id}')
            ->whereIn('entityType', ['sales_order', 'gdn_header', 'sales_return'])
            ->group(function (): void {
                Route::post('transition', [SalesWorkflowController::class, 'transition'])
                    ->name('workflows.transition');
                Route::post('document', [SalesWorkflowController::class, 'createDocument'])
                    ->name('workflows.document');
                Route::post('payment/allocate', [SalesWorkflowController::class, 'allocatePayment'])
                    ->name('workflows.payment.allocate');
                Route::post('inventory/post', [SalesWorkflowController::class, 'postInventory'])
                    ->name('workflows.inventory.post');
                Route::post('finance/post', [SalesWorkflowController::class, 'postFinance'])
                    ->name('workflows.finance.post');
                Route::post('finance/preview', [SalesWorkflowController::class, 'previewFinance'])
                    ->name('workflows.finance.preview');
                Route::post('finance/reverse', [SalesWorkflowController::class, 'reverseFinance'])
                    ->name('workflows.finance.reverse');
                Route::get('history', [SalesManagementController::class, 'statusHistory'])
                    ->name('workflows.history');
            });
    });
