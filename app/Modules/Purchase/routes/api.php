<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Purchase\Presentation\Http\Controllers\PurchaseOrderController;
use Modules\Purchase\Presentation\Http\Controllers\PurchaseOrderLineController;
use Modules\Purchase\Presentation\Http\Controllers\GrnHeaderController;
use Modules\Purchase\Presentation\Http\Controllers\GrnLineController;
use Modules\Purchase\Presentation\Http\Controllers\PurchaseReturnController;
use Modules\Purchase\Presentation\Http\Controllers\PurchaseReturnLineController;
use Modules\Purchase\Presentation\Http\Controllers\PurchaseManagementController;
use Modules\Purchase\Presentation\Http\Controllers\PurchaseIntegrationController;
use Modules\Purchase\Presentation\Http\Controllers\PurchaseWorkflowController;

$protectedGuard = (string) config('module-auth.protected_route_guard', 'auth-api');
$currentUserMiddleware = (string) config('core.current_user.middleware_alias', 'current.user');
$currentTenantMiddleware = (string) config('core.current_tenant.middleware_alias', 'current.tenant');
$currentOrganizationUnitMiddleware = (string) config(
    'core.current_organization_unit.middleware_alias',
    'current.organization-unit',
);

Route::prefix('api/purchase')
    ->middleware([
        'api',
        'auth:' . $protectedGuard,
        $currentUserMiddleware,
        $currentTenantMiddleware,
        $currentOrganizationUnitMiddleware,
    ])
    ->name('purchase.')
    ->group(function (): void {
        Route::apiResource('purchase-orders', PurchaseOrderController::class);
        Route::apiResource('purchase-order-lines', PurchaseOrderLineController::class);
        Route::apiResource('grn-headers', GrnHeaderController::class);
        Route::apiResource('grn-lines', GrnLineController::class);
        Route::apiResource('purchase-returns', PurchaseReturnController::class);
        Route::apiResource('purchase-return-lines', PurchaseReturnLineController::class);

        Route::post('purchase-orders/with-lines', [PurchaseManagementController::class, 'upsertPurchaseOrderWithLines'])
            ->name('purchase-orders.with-lines.store');
        Route::put('purchase-orders/{id}/with-lines', [
            PurchaseManagementController::class,
            'updatePurchaseOrderWithLines',
        ])
            ->name('purchase-orders.with-lines.update');
        Route::put('purchase-orders/{id}/lines/sync', [PurchaseManagementController::class, 'syncPurchaseOrderLines'])
            ->name('purchase-orders.lines.sync');

        Route::post('grn-headers/with-lines', [PurchaseManagementController::class, 'upsertGrnWithLines'])
            ->name('grn-headers.with-lines.store');
        Route::put('grn-headers/{id}/with-lines', [PurchaseManagementController::class, 'updateGrnWithLines'])
            ->name('grn-headers.with-lines.update');
        Route::put('grn-headers/{id}/lines/sync', [PurchaseManagementController::class, 'syncGrnLines'])
            ->name('grn-headers.lines.sync');

        Route::post('purchase-returns/with-lines', [
            PurchaseManagementController::class,
            'upsertPurchaseReturnWithLines',
        ])
            ->name('purchase-returns.with-lines.store');
        Route::put('purchase-returns/{id}/with-lines', [
            PurchaseManagementController::class,
            'updatePurchaseReturnWithLines',
        ])
            ->name('purchase-returns.with-lines.update');
        Route::put('purchase-returns/{id}/lines/sync', [PurchaseManagementController::class, 'syncPurchaseReturnLines'])
            ->name('purchase-returns.lines.sync');

        Route::get('settings', [PurchaseManagementController::class, 'showSettings'])
            ->name('settings.show');
        Route::put('settings', [PurchaseManagementController::class, 'upsertSettings'])
            ->name('settings.upsert');
        Route::post('settings/initialize', [PurchaseManagementController::class, 'initializeSettings'])
            ->name('settings.initialize');

        Route::get('lookups/purchase-orders/{purchaseOrderId}/available-lines', [
            PurchaseManagementController::class,
            'availablePurchaseOrderLinesForGrn',
        ])->name('lookups.purchase-order-lines.available-for-grn');
        Route::get('lookups/grn-headers/{grnHeaderId}/available-document-lines', [
            PurchaseManagementController::class,
            'availableGrnLinesForDocument',
        ])->name('lookups.grn-lines.available-for-document');
        Route::get('lookups/returnable-lines', [PurchaseManagementController::class, 'returnableLines'])
            ->name('lookups.returnable-lines');
        Route::get('lookups/payable-documents', [PurchaseManagementController::class, 'payableDocuments'])
            ->name('lookups.payable-documents');

        Route::prefix('integrations')
            ->group(function (): void {
                Route::prefix('workflows/{entityType}/{id}')
                    ->whereIn('entityType', ['purchase_order', 'grn_header', 'purchase_return'])
                    ->group(function (): void {
                        Route::get('documents', [PurchaseIntegrationController::class, 'listDocuments'])
                            ->name('integrations.documents.index');
                        Route::post('documents', [PurchaseIntegrationController::class, 'createDocument'])
                            ->name('integrations.documents.store');
                        Route::get('documents/{documentId}', [PurchaseIntegrationController::class, 'showDocument'])
                            ->name('integrations.documents.show');
                        Route::post(
                            'documents/{documentId}/status',
                            [PurchaseIntegrationController::class, 'changeDocumentStatus']
                        )->name('integrations.documents.status');
                        Route::post(
                            'documents/{documentId}/lines/match',
                            [PurchaseIntegrationController::class, 'matchDocumentLine']
                        )->name('integrations.documents.lines.match');
                        Route::post(
                            'documents/{documentId}/lines/unmatch',
                            [PurchaseIntegrationController::class, 'unmatchDocumentLine']
                        )->name('integrations.documents.lines.unmatch');

                        Route::post('payments', [PurchaseIntegrationController::class, 'createPayment'])
                            ->name('integrations.payments.store');
                        Route::post('advances', [PurchaseIntegrationController::class, 'createAdvance'])
                            ->name('integrations.advances.store');
                        Route::post('payments/allocate', [PurchaseIntegrationController::class, 'allocatePayment'])
                            ->name('integrations.payments.allocate');
                        Route::post('advances/apply', [PurchaseIntegrationController::class, 'applyAdvance'])
                            ->name('integrations.advances.apply');
                        Route::get('payments/allocations', [
                            PurchaseIntegrationController::class,
                            'listPaymentAllocations',
                        ])->name('integrations.payments.allocations');
                        Route::get('payments/summary', [PurchaseIntegrationController::class, 'sourcePaymentSummary'])
                            ->name('integrations.payments.summary');
                    });

                Route::get('suppliers/payables', [PurchaseIntegrationController::class, 'supplierPayables'])
                    ->name('integrations.suppliers.payables');
                Route::get('suppliers/advances', [PurchaseIntegrationController::class, 'supplierAdvanceBalances'])
                    ->name('integrations.suppliers.advances');

                Route::post('payments/{paymentId}/post', [PurchaseIntegrationController::class, 'postPayment'])
                    ->name('integrations.payments.post');
                Route::post('payments/{paymentId}/reverse', [PurchaseIntegrationController::class, 'reversePayment'])
                    ->name('integrations.payments.reverse');
                Route::post('payments/{paymentId}/refund', [PurchaseIntegrationController::class, 'refundPayment'])
                    ->name('integrations.payments.refund');
            });

        Route::prefix('workflows/{entityType}/{id}')
            ->whereIn('entityType', ['purchase_order', 'grn_header', 'purchase_return'])
            ->group(function (): void {
                Route::post('transition', [PurchaseWorkflowController::class, 'transition'])
                    ->name('workflows.transition');
                Route::post('document', [PurchaseWorkflowController::class, 'createDocument'])
                    ->name('workflows.document');
                Route::post('payment/allocate', [PurchaseWorkflowController::class, 'allocatePayment'])
                    ->name('workflows.payment.allocate');
                Route::post('inventory/post', [PurchaseWorkflowController::class, 'postInventory'])
                    ->name('workflows.inventory.post');
                Route::post('finance/post', [PurchaseWorkflowController::class, 'postFinance'])
                    ->name('workflows.finance.post');
                Route::post('finance/reverse', [PurchaseWorkflowController::class, 'reverseFinance'])
                    ->name('workflows.finance.reverse');
                Route::get('history', [PurchaseManagementController::class, 'statusHistory'])
                    ->name('workflows.history');
            });
    });
