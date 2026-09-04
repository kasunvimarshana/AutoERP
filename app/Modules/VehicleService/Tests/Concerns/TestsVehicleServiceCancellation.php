<?php

declare(strict_types=1);

namespace Modules\VehicleService\Tests\Concerns;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Modules\Core\Contracts\PermissionCheckerInterface;
use Modules\Finance\Enums\JournalStatus;
use Modules\Finance\Models\FinanceJournalEntry;
use Modules\Inventory\DTOs\StockBalanceData;
use Modules\Inventory\DTOs\StockMovementData;
use Modules\Inventory\Enums\InventoryDirection;
use Modules\Inventory\Enums\InventoryMovementType;
use Modules\Inventory\Enums\InventoryStatus;
use Modules\Inventory\Models\InventoryMovement;
use Modules\Inventory\Services\InventoryFacade;
use Modules\Inventory\Services\StockAvailabilityService;
use Modules\Inventory\Services\StockMovementService;
use Modules\Invoice\Enums\InvoiceStatus;
use Modules\Payment\Enums\PaymentDocumentStatus;
use Modules\Payment\Enums\PaymentPostingStatus;
use Modules\VehicleService\Constants\VehicleServiceFinanceSource;
use Modules\VehicleService\Constants\VehicleServicePermission;
use Modules\VehicleService\DTOs\VehicleServicePaymentData;
use Modules\VehicleService\Enums\VehicleServiceCommissionType;
use Modules\VehicleService\Enums\VehicleServiceJobStatus;
use Modules\VehicleService\Enums\VehicleServiceLineSourceType;
use Modules\VehicleService\Models\VehicleServiceJob;
use Modules\VehicleService\Services\VehicleServiceJobCancellationService;
use Modules\VehicleService\Services\VehicleServiceStatusService;

/** Cancellation scenarios reuse the engine's real stock, job and billing fixtures. */
trait TestsVehicleServiceCancellation
{
    public function test_cancellation_reverses_multiple_issues_and_journals_without_changing_history(): void
    {
        $context = $this->context();
        $actor = $this->cancellationActor($context);
        $this->receiveStock($context, '10.000000');
        $job = $this->createJob($context, VehicleServiceCommissionType::Fixed, '25.000000');
        $this->line($job, VehicleServiceLineSourceType::InventoryItem, $context['stock'], '2.000000', '100.000000');
        $this->line($job, VehicleServiceLineSourceType::InventoryItem, $context['stock'], '3.000000', '100.000000');
        $issues = $this->issueInventory($job, $context['warehouse_id'], $context['warehouse_location_id']);
        $this->changeStatus($job, VehicleServiceJobStatus::InProgress);
        $version = $this->currentJobVersion($job);

        $this->withTenantExecutionContext($context['tenant_id'], function () use ($context, $job, $actor, $issues, $version): void {
            $preview = app(VehicleServiceJobCancellationService::class)->preview($job->fresh(), $actor);
            $this->assertTrue($preview['can_cancel']);
            $this->assertCount(2, $preview['stock_returns']);
            $this->assertSame('50.000000', $preview['inventory_value']);
            $this->assertSame('25.000000', $preview['commission_amount']);

            $cancelled = app(VehicleServiceStatusService::class)->change($job, VehicleServiceJobStatus::Cancelled, $actor, 'Items returned', $version);
            $this->assertSame(VehicleServiceJobStatus::Cancelled, $cancelled->status);
            $this->assertGreaterThan($version, $cancelled->row_version);
            $this->assertSame('25.000000', $cancelled->commission_cost_total);
            $this->assertSame('active', $cancelled->vehicle->status->value);
            $this->assertSame('10.000000', $this->cancellationStock($context));
            foreach ($issues as $issue) {
                $issue->refresh();
                $this->assertSame(InventoryStatus::Reversed, $issue->status);
                $reverse = $issue->reversals()->sole();
                $this->assertSame(InventoryStatus::Posted, $reverse->status);
                $this->assertSame(InventoryDirection::In, $reverse->direction);
                $this->assertSame($issue->quantity, $reverse->quantity);
                $this->assertSame($issue->total_cost, $reverse->total_cost);
                $this->assertSame($issue->warehouse_location_id, $reverse->warehouse_location_id);
                $this->assertSame((int) $issue->id, (int) $cancelled->lines()->findOrFail($issue->source_line_id)->inventory_movement_id);
                $journal = FinanceJournalEntry::query()->where('source_type', VehicleServiceFinanceSource::INVENTORY_ISSUE)->where('source_id', $issue->id)->whereNull('reversal_of_id')->sole();
                $this->assertSame(JournalStatus::Reversed, $journal->status);
                $journalReverse = $journal->reversals()->sole();
                $this->assertSame(JournalStatus::Posted, $journalReverse->status);
                $this->assertSame($journal->total_debit, $journalReverse->total_credit);
                foreach ($journal->lines as $originalLine) {
                    $reverseLine = $journalReverse->lines()->where('account_id', $originalLine->account_id)->sole();
                    $this->assertSame($originalLine->debit, $reverseLine->credit);
                    $this->assertSame($originalLine->credit, $reverseLine->debit);
                }
            }
            $history = $cancelled->statusHistories()->where('new_status', VehicleServiceJobStatus::Cancelled->value)->sole();
            $this->assertSame('Items returned', $history->reason);
            $this->assertSame($actor, (int) $history->changed_by);
        });
    }

    public function test_cancellation_zero_cost_issue_returns_quantity_without_a_finance_journal(): void
    {
        $context = $this->context();
        $actor = $this->cancellationActor($context);
        $this->withTenantExecutionContext($context['tenant_id'], fn () => app(StockMovementService::class)->record(new StockMovementData(
            tenantId: $context['tenant_id'], movementDate: '2026-06-07', movementType: InventoryMovementType::Receipt,
            direction: InventoryDirection::In, itemId: (int) $context['stock']->id,
            warehouseId: $context['warehouse_id'], warehouseLocationId: $context['warehouse_location_id'],
            quantity: '5.000000', unitCost: '0.000000',
        )));
        $job = $this->createJob($context);
        $this->line($job, VehicleServiceLineSourceType::InventoryItem, $context['stock'], '2.000000', '100.000000');
        $issue = $this->issueInventory($job, $context['warehouse_id'], $context['warehouse_location_id'])[0];
        $this->assertSame('0.000000', $issue->total_cost);
        $this->cancelJob($job, $actor);
        $this->withTenantExecutionContext($context['tenant_id'], function () use ($context, $issue): void {
            $this->assertSame('5.000000', $this->cancellationStock($context));
            $this->assertSame(InventoryStatus::Reversed, $issue->fresh()->status);
            $this->assertSame(0, FinanceJournalEntry::query()->where('source_type', VehicleServiceFinanceSource::INVENTORY_ISSUE)->count());
        });
    }

    public function test_cancellation_missing_second_issue_journal_rolls_back_all_reversals(): void
    {
        $context = $this->context();
        $actor = $this->cancellationActor($context);
        $this->receiveStock($context, '10.000000');
        $job = $this->createJob($context);
        $this->line($job, VehicleServiceLineSourceType::InventoryItem, $context['stock'], '2.000000', '100.000000');
        $this->line($job, VehicleServiceLineSourceType::InventoryItem, $context['stock'], '3.000000', '100.000000');
        $issues = $this->issueInventory($job, $context['warehouse_id'], $context['warehouse_location_id']);
        $version = $this->currentJobVersion($job);
        // Deliberately corrupt only this isolated test database to simulate a missing source posting.
        DB::table('finance_journal_entries')->where('source_type', VehicleServiceFinanceSource::INVENTORY_ISSUE)
            ->where('source_id', $issues[1]->id)->update(['source_id' => null]);
        try {
            $this->cancelJob($job, $actor);
            $this->fail('A non-zero issue without its posted journal must fail.');
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString('No posted journal exists', $exception->getMessage());
        }
        $this->withTenantExecutionContext($context['tenant_id'], function () use ($context, $job, $issues, $version): void {
            $this->assertSame(VehicleServiceJobStatus::Draft, $job->fresh()->status);
            $this->assertSame($version, $job->fresh()->row_version);
            $this->assertSame('5.000000', $this->cancellationStock($context));
            $this->assertSame(0, InventoryMovement::query()->whereNotNull('reversal_of_id')->count());
            $this->assertSame(0, FinanceJournalEntry::query()->whereNotNull('reversal_of_id')->count());
            foreach ($issues as $issue) {
                $this->assertSame(InventoryStatus::Posted, $issue->fresh()->status);
            }
            $this->assertSame(0, $job->statusHistories()->where('new_status', 'cancelled')->count());
        });
    }

    public function test_cancellation_requires_current_version_and_cannot_be_repeated(): void
    {
        $context = $this->context();
        $actor = $this->cancellationActor($context);
        $job = $this->createJob($context);
        $version = $this->currentJobVersion($job);
        $this->line($job, VehicleServiceLineSourceType::ServiceItem, $context['service'], '1.000000', '100.000000');
        try {
            $this->cancelJob($job, $actor, $version);
            $this->fail('Stale cancellation should fail.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('expected_version', $exception->errors());
        }
        $this->assertSame(VehicleServiceJobStatus::Draft, $this->refreshJob($job)->status);
        $this->cancelJob($job, $actor);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('This job status does not allow cancellation.');
        $this->cancelJob($job, $actor);
    }

    public function test_cancellation_completed_job_requires_elevated_permission_even_with_stale_model(): void
    {
        $context = $this->context();
        $actor = $this->cancellationActor($context, false);
        $job = $this->createJob($context);
        $this->changeStatus($job, VehicleServiceJobStatus::InProgress);
        $this->changeStatus($job, VehicleServiceJobStatus::Completed);
        try {
            $this->cancelJob($job, $actor);
            $this->fail('The current locked status must require elevated permission.');
        } catch (AuthorizationException $exception) {
            $this->assertStringContainsString('completed-job cancellation permission', $exception->getMessage());
        }
        $completedAt = $this->refreshJob($job)->completed_at;
        $this->allowCancellationPermissions(true);
        $cancelled = $this->cancelJob($job, $actor);
        $this->assertSame(VehicleServiceJobStatus::Cancelled, $cancelled->status);
        $this->assertEquals($completedAt, $cancelled->completed_at);
    }

    public function test_cancellation_blocks_active_partial_invoice_even_for_elevated_user(): void
    {
        $context = $this->context();
        $actor = $this->cancellationActor($context);
        $job = $this->createJob($context);
        $line = $this->line($job, VehicleServiceLineSourceType::ServiceItem, $context['service'], '2.000000', '100.000000');
        $this->changeStatus($job, VehicleServiceJobStatus::InProgress);
        $this->changeStatus($job, VehicleServiceJobStatus::Completed);
        $invoice = $this->createServiceInvoice($job, '2026-06-07', [(int) $line->id => '1.000000']);
        $this->assertSame(VehicleServiceJobStatus::Completed, $this->refreshJob($job)->status);
        try {
            $this->cancelJob($job, $actor);
            $this->fail('A partial invoice must block cancellation.');
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString('linked invoices', $exception->getMessage());
        }
        // Simulate the document's terminal state; cancellation does not own invoice reversal.
        DB::table('invoices')->where('id', $invoice->id)->update(['status' => InvoiceStatus::Reversed->value]);
        $this->assertSame(VehicleServiceJobStatus::Cancelled, $this->cancelJob($job, $actor)->status);
    }

    public function test_cancellation_blocks_payment_even_when_invoice_is_reversed(): void
    {
        $context = $this->context();
        $actor = $this->cancellationActor($context);
        $job = $this->createJob($context);
        $line = $this->line($job, VehicleServiceLineSourceType::ServiceItem, $context['service'], '2.000000', '100.000000');
        $this->changeStatus($job, VehicleServiceJobStatus::InProgress);
        $this->changeStatus($job, VehicleServiceJobStatus::Completed);
        $invoice = $this->createServiceInvoice($job, '2026-06-07', [(int) $line->id => '1.000000']);
        $this->paymentFinanceContext($context['tenant_id']);
        $payment = $this->createServicePayment($job, new VehicleServicePaymentData(
            expectedVersion: $this->currentJobVersion($job), invoiceId: (int) $invoice->id,
            paymentDate: '2026-06-07', amount: '50.000000', paymentMethodId: (int) $this->paymentMethod($context)->id,
        ));
        DB::table('invoices')->where('id', $invoice->id)->update(['status' => InvoiceStatus::Reversed->value]);
        // Isolate the payment guard from the independent terminal job-status guard.
        DB::table('vehicle_service_jobs')->where('id', $job->id)->update(['status' => VehicleServiceJobStatus::Completed->value]);
        try {
            $this->cancelJob($job, $actor);
            $this->fail('An active payment must block cancellation.');
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString('linked payments', $exception->getMessage());
        }
        DB::table('payments')->where('id', $payment->id)->update([
            'document_status' => PaymentDocumentStatus::Reversed->value,
            'posting_status' => PaymentPostingStatus::Reversed->value,
        ]);
        $this->assertSame(VehicleServiceJobStatus::Cancelled, $this->cancelJob($job, $actor)->status);
    }

    public function test_cancellation_rejects_already_reversed_stock_without_double_return(): void
    {
        $context = $this->context();
        $actor = $this->cancellationActor($context);
        $this->receiveStock($context, '5.000000');
        $job = $this->createJob($context);
        $this->line($job, VehicleServiceLineSourceType::InventoryItem, $context['stock'], '2.000000', '100.000000');
        $issue = $this->issueInventory($job, $context['warehouse_id'], $context['warehouse_location_id'])[0];
        $this->withTenantExecutionContext($context['tenant_id'], fn () => app(InventoryFacade::class)->reverse($issue, $actor));
        try {
            $this->cancelJob($job, $actor);
            $this->fail('An independently reversed movement must not be silently skipped.');
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString('already reversed', $exception->getMessage());
        }
        $this->assertSame(VehicleServiceJobStatus::Draft, $this->refreshJob($job)->status);
        $this->withTenantExecutionContext($context['tenant_id'], function () use ($context, $issue): void {
            $this->assertSame('5.000000', $this->cancellationStock($context));
            $this->assertSame(1, $issue->reversals()->count());
        });
    }

    public function test_cancellation_api_requires_reason_and_version_and_returns_preview(): void
    {
        $this->withoutMiddleware();
        $context = $this->context();
        $this->cancellationActor($context);
        $job = $this->createJob($context);
        $this->tenantGetJson($context['tenant_id'], '/api/v1/vehicle-service/jobs/'.$job->id.'/cancellation-preview?tenant_id='.$context['tenant_id'])
            ->assertOk()->assertJsonPath('data.can_cancel', true)->assertJsonPath('data.stock_returns', []);
        $this->tenantPatchJson($context['tenant_id'], '/api/v1/vehicle-service/jobs/'.$job->id.'/cancel', ['tenant_id' => $context['tenant_id']])
            ->assertUnprocessable()->assertJsonValidationErrors(['reason', 'expected_version']);
        $this->tenantPatchJson($context['tenant_id'], '/api/v1/vehicle-service/jobs/'.$job->id.'/cancel', [
            'tenant_id' => $context['tenant_id'], 'expected_version' => $this->currentJobVersion($job), 'reason' => 'Customer withdrew',
        ])->assertOk()->assertJsonPath('data.status', VehicleServiceJobStatus::Cancelled->value);
    }

    private function cancellationActor(array $context, bool $completed = true): int
    {
        $this->actingAsTenantUser($context['tenant_id']);
        $this->allowCancellationPermissions($completed);

        return (int) auth()->id();
    }

    private function allowCancellationPermissions(bool $completed): void
    {
        $this->mock(PermissionCheckerInterface::class, function ($mock) use ($completed): void {
            $mock->shouldReceive('allows')->andReturnUsing(fn (int $userId, int $tenantId, string $permission): bool => $permission === VehicleServicePermission::JOBS_TRANSITION
                || ($completed && $permission === VehicleServicePermission::JOBS_CANCEL_COMPLETED));
        });
    }

    private function cancelJob(VehicleServiceJob $job, int $actor, ?int $version = null): VehicleServiceJob
    {
        $version ??= $this->currentJobVersion($job);

        return $this->withTenantExecutionContext((int) $job->tenant_id, fn () => app(VehicleServiceStatusService::class)->change(
            $job, VehicleServiceJobStatus::Cancelled, $actor, 'Customer cancelled; issued items returned', $version,
        ));
    }

    private function cancellationStock(array $context): string
    {
        return app(StockAvailabilityService::class)->availability(new StockBalanceData(
            $context['tenant_id'], (int) $context['stock']->id, $context['warehouse_id'],
        ))->quantityAvailable;
    }
}
