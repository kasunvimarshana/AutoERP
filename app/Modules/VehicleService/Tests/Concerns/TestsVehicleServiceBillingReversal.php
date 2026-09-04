<?php

declare(strict_types=1);

namespace Modules\VehicleService\Tests\Concerns;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Modules\Finance\Models\FinanceJournalEntry;
use Modules\Finance\Services\FinanceStatementService;
use Modules\Inventory\Enums\InventoryStatus;
use Modules\Invoice\Contracts\InvoiceSourceRestorationHandlerInterface;
use Modules\Invoice\Data\InvoiceSourceRestorationContext;
use Modules\Invoice\Enums\InvoiceStatus;
use Modules\Invoice\Models\Invoice;
use Modules\Invoice\Services\InvoiceReversalService;
use Modules\Invoice\Services\InvoiceSourceRestorationRegistry;
use Modules\Invoice\Services\InvoiceSourceRestorationService;
use Modules\Payment\DTOs\PaymentReversalData;
use Modules\Payment\Services\PaymentReversalService;
use Modules\VehicleService\DTOs\VehicleServicePaymentData;
use Modules\VehicleService\Enums\VehicleServiceCommissionType;
use Modules\VehicleService\Enums\VehicleServiceJobStatus;
use Modules\VehicleService\Enums\VehicleServiceLineSourceType;
use Modules\VehicleService\Services\Invoice\VehicleServiceInvoiceRestorationHandler;
use Modules\VehicleService\Services\VehicleServiceStatusService;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;

trait TestsVehicleServiceBillingReversal
{
    #[DataProvider('billingReceiptAmounts')]
    public function test_billing_reversal_receipt_then_invoice_then_cancellation(string $amount, VehicleServiceJobStatus $paidStatus): void
    {
        $context = $this->context();
        $actor = $this->cancellationActor($context);
        $this->receiveStock($context, '5.000000');
        $job = $this->createJob($context, VehicleServiceCommissionType::Fixed, '25.000000');
        $this->line($job, VehicleServiceLineSourceType::InventoryItem, $context['stock'], '2.000000', '100.000000');
        $issue = $this->issueInventory($job, $context['warehouse_id'], $context['warehouse_location_id'])[0];
        $this->changeStatus($job, VehicleServiceJobStatus::InProgress);
        $this->changeStatus($job, VehicleServiceJobStatus::Completed);
        $completedAt = $this->refreshJob($job)->completed_at;
        $invoice = $this->createServiceInvoice($job, '2026-06-07');
        $this->paymentFinanceContext($context['tenant_id']);
        $receipt = $this->createServicePayment($job, new VehicleServicePaymentData(
            expectedVersion: $this->currentJobVersion($job), invoiceId: (int) $invoice->id,
            paymentDate: now()->toDateString(), amount: $amount, paymentMethodId: (int) $this->paymentMethod($context)->id,
        ));
        $this->assertSame($paidStatus, $this->refreshJob($job)->status);
        try {
            $this->reverseBillingInvoice($invoice, $actor);
            $this->fail('A settled invoice must not be reversed before its receipt.');
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString('unsettled posted invoice', $exception->getMessage());
        }

        $beforeReversal = $this->profitAndLossForCancellation($context['tenant_id']);
        $this->assertSame('200.000000', $beforeReversal['total_revenue']);
        $this->assertSame('20.000000', $beforeReversal['total_expenses']);
        $this->assertSame('180.000000', $beforeReversal['net_profit']);

        $this->withTenantExecutionContext($context['tenant_id'], function () use ($receipt, $actor): void {
            $receipt->refresh();
            app(PaymentReversalService::class)->reverse(new PaymentReversalData(
                (int) $receipt->id, (int) $receipt->row_version, now()->toDateString(), 'Customer cancellation', $actor,
            ));
        });
        $beforeVersion = $this->currentJobVersion($job);
        $this->travel(1)->minutes();
        $this->reverseBillingInvoice($invoice, $actor);
        $afterInvoiceReversal = $this->profitAndLossForCancellation($context['tenant_id']);
        $this->assertSame('0.000000', $afterInvoiceReversal['total_revenue']);
        $this->assertSame('20.000000', $afterInvoiceReversal['total_expenses']);
        $this->assertSame('-20.000000', $afterInvoiceReversal['net_profit']);
        $restored = $this->refreshJob($job);
        $this->assertSame(VehicleServiceJobStatus::Completed, $restored->status);
        $this->assertEquals($completedAt, $restored->completed_at);
        $this->assertGreaterThan($beforeVersion, $restored->row_version);
        $this->withTenantExecutionContext($context['tenant_id'], function () use ($job, $issue, $actor, $paidStatus): void {
            $this->assertSame(InventoryStatus::Posted, $issue->fresh()->status);
            $event = $job->statusHistories()->where('old_status', $paidStatus->value)->where('new_status', 'completed')->sole();
            $this->assertSame($actor, (int) $event->changed_by);
            $this->assertStringContainsString('Customer cancellation', $event->reason);
        });
        try {
            $this->cancelJob($job, $actor, $beforeVersion);
            $this->fail('Billing restoration must invalidate the old job version.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('expected_version', $exception->errors());
        }
        $cancelled = $this->cancelJob($job, $actor);
        $this->assertSame(VehicleServiceJobStatus::Cancelled, $cancelled->status);
        $this->assertSame('25.000000', $cancelled->commission_cost_total);
        $afterCancellation = $this->profitAndLossForCancellation($context['tenant_id']);
        $this->assertSame('0.000000', $afterCancellation['total_revenue']);
        $this->assertSame('0.000000', $afterCancellation['total_expenses']);
        $this->assertSame('0.000000', $afterCancellation['net_profit']);
        $this->withTenantExecutionContext($context['tenant_id'], function () use ($context, $job, $invoice, $issue, $actor): void {
            $version = (int) $job->fresh()->row_version;
            $historyCount = $job->statusHistories()->count();
            app(InvoiceSourceRestorationService::class)->restore($invoice->fresh(), InvoiceStatus::Reversed, $actor, 'Repeated notification');
            app(VehicleServiceStatusService::class)->restoreCompletedAfterBillingReversal($job, $actor, 'Repeated notification');
            $this->assertSame(VehicleServiceJobStatus::Cancelled, $job->fresh()->status);
            $this->assertSame($version, (int) $job->fresh()->row_version);
            $this->assertSame($historyCount, $job->statusHistories()->count());
            $this->assertSame(InventoryStatus::Reversed, $issue->fresh()->status);
            $this->assertSame(1, $issue->reversals()->count());
            $this->assertSame('5.000000', $this->cancellationStock($context));
        });
    }

    public static function billingReceiptAmounts(): array
    {
        return [
            'fully paid' => ['200.000000', VehicleServiceJobStatus::Paid],
            'partially paid' => ['50.000000', VehicleServiceJobStatus::PartiallyPaid],
        ];
    }

    public function test_billing_reversal_waits_for_every_invoice_and_retains_admin_cancellation_permission(): void
    {
        $context = $this->context();
        $actor = $this->cancellationActor($context, false);
        $job = $this->createJob($context);
        $line = $this->line($job, VehicleServiceLineSourceType::ServiceItem, $context['service'], '2.000000', '100.000000');
        $this->changeStatus($job, VehicleServiceJobStatus::InProgress);
        $this->changeStatus($job, VehicleServiceJobStatus::Completed);
        $first = $this->createServiceInvoice($job, '2026-06-07', [(int) $line->id => '1.000000']);
        $second = $this->createServiceInvoice($job, '2026-06-07', [(int) $line->id => '1.000000']);
        $this->assertSame(VehicleServiceJobStatus::Invoiced, $this->refreshJob($job)->status);
        $this->reverseBillingInvoice($first, $actor);
        $this->assertSame(VehicleServiceJobStatus::Invoiced, $this->refreshJob($job)->status);
        try {
            $this->cancelJob($job, $actor);
            $this->fail('The remaining active invoice must protect the job.');
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString('linked invoices', $exception->getMessage());
        }
        $this->reverseBillingInvoice($second, $actor);
        $this->assertSame(VehicleServiceJobStatus::Completed, $this->refreshJob($job)->status);
        try {
            $this->cancelJob($job, $actor);
            $this->fail('Invoice reversal permission must not grant completed-job cancellation.');
        } catch (AuthorizationException $exception) {
            $this->assertStringContainsString('completed-job cancellation permission', $exception->getMessage());
        }
        $this->allowCancellationPermissions(true);
        $this->assertSame(VehicleServiceJobStatus::Cancelled, $this->cancelJob($job, $actor)->status);
    }

    public function test_billing_reversal_callback_failure_rolls_back_invoice_finance_and_job_restoration(): void
    {
        $context = $this->context();
        $actor = $this->cancellationActor($context);
        $job = $this->createJob($context);
        $this->line($job, VehicleServiceLineSourceType::ServiceItem, $context['service'], '1.000000', '100.000000');
        $this->changeStatus($job, VehicleServiceJobStatus::InProgress);
        $this->changeStatus($job, VehicleServiceJobStatus::Completed);
        $invoice = $this->createServiceInvoice($job, '2026-06-07');
        $version = $this->currentJobVersion($job);
        $this->app->instance(InvoiceSourceRestorationRegistry::class, new InvoiceSourceRestorationRegistry([
            app(VehicleServiceInvoiceRestorationHandler::class),
            new class implements InvoiceSourceRestorationHandlerInterface
            {
                public function supports(InvoiceSourceRestorationContext $context): bool
                {
                    return true;
                }

                public function restore(InvoiceSourceRestorationContext $context): void
                {
                    throw new RuntimeException('Simulated downstream failure');
                }
            },
        ]));
        try {
            $this->reverseBillingInvoice($invoice, $actor);
            $this->fail('The downstream failure must abort the reversal.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulated downstream failure', $exception->getMessage());
        }
        $this->withTenantExecutionContext($context['tenant_id'], function () use ($job, $invoice, $version): void {
            $this->assertSame(InvoiceStatus::Posted, $invoice->fresh()->status);
            $this->assertSame(VehicleServiceJobStatus::Invoiced, $job->fresh()->status);
            $this->assertSame($version, (int) $job->fresh()->row_version);
            $this->assertSame('active', $job->invoiceLinks()->sole()->status);
            $this->assertSame(0, FinanceJournalEntry::query()->whereNotNull('reversal_of_id')->count());
            $this->assertSame(0, $job->statusHistories()->where('old_status', 'invoiced')->where('new_status', 'completed')->count());
        });
    }

    private function reverseBillingInvoice(Invoice $invoice, int $actor): Invoice
    {
        return $this->withTenantExecutionContext((int) $invoice->tenant_id, function () use ($invoice, $actor): Invoice {
            $invoice->refresh();

            return app(InvoiceReversalService::class)->reverse($invoice, (int) $invoice->row_version, now()->toDateString(), 'Customer cancellation', $actor);
        });
    }

    /** @return array<string, mixed> */
    private function profitAndLossForCancellation(int $tenantId): array
    {
        return $this->withTenantExecutionContext(
            $tenantId,
            fn (): array => app(FinanceStatementService::class)->profitAndLoss(
                $tenantId,
                null,
                '2026-06-01',
                now()->toDateString(),
            ),
        );
    }
}
