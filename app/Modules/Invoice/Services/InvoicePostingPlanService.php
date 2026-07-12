<?php

declare(strict_types=1);

namespace Modules\Invoice\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Finance\Contracts\FinancePostingInterface;
use Modules\Finance\Contracts\FinanceSourceReversalInterface;
use Modules\Finance\DTOs\PostingContext;
use Modules\Finance\DTOs\PostingLine;
use Modules\Finance\DTOs\PostingResultData;
use Modules\Finance\DTOs\PostingSourceData;
use Modules\Finance\Enums\FinancePostingProfileCode;
use Modules\Invoice\Constants\InvoiceFinanceSource;
use Modules\Invoice\DTOs\InvoicePostingLineData;
use Modules\Invoice\DTOs\InvoicePostingPlanData;
use Modules\Invoice\Enums\InvoicePostingPlanStatus;
use Modules\Invoice\Models\Invoice;
use Modules\Invoice\Models\InvoicePostingPlan;

final class InvoicePostingPlanService
{
    private const ZERO = '0.000000';

    public function __construct(
        private readonly DecimalMath $math,
        private readonly FinancePostingInterface $postings,
        private readonly FinanceSourceReversalInterface $reversals,
    ) {}

    public function create(
        Invoice $invoice,
        InvoicePostingPlanData $data,
        ?int $actorId = null,
    ): InvoicePostingPlan {
        return DB::transaction(function () use ($invoice, $data, $actorId): InvoicePostingPlan {
            $invoice = Invoice::query()->lockForUpdate()->findOrFail($invoice->getKey());
            $this->assertPlan($invoice, $data);

            $existing = InvoicePostingPlan::query()
                ->where('tenant_id', $invoice->tenant_id)
                ->where('invoice_id', $invoice->getKey())
                ->lockForUpdate()
                ->first();
            if ($existing instanceof InvoicePostingPlan) {
                throw new InvalidArgumentException('Invoice posting plan already exists and cannot be replaced.');
            }

            $plan = new InvoicePostingPlan();
            $plan->forceFill([
                'tenant_id' => (int) $invoice->tenant_id,
                'organization_unit_id' => $invoice->organization_unit_id,
                'invoice_id' => (int) $invoice->getKey(),
                'posting_profile_code' => $data->profile->value,
                'posting_date' => $data->postingDate,
                'lines' => array_map(
                    static fn (InvoicePostingLineData $line): array => $line->toArray(),
                    $data->lines,
                ),
                'status' => InvoicePostingPlanStatus::Prepared->value,
                'row_version' => 1,
                'created_by' => $actorId,
            ])->save();

            return $plan->refresh();
        });
    }

    public function post(Invoice $invoice, ?int $actorId = null): PostingResultData
    {
        return DB::transaction(function () use ($invoice, $actorId): PostingResultData {
            $invoice = Invoice::query()->lockForUpdate()->findOrFail($invoice->getKey());
            $plan = $this->lockedPlan($invoice);
            $status = $this->status($plan);
            if ($status === InvoicePostingPlanStatus::Reversed) {
                throw new InvalidArgumentException('Reversed invoice posting plans cannot be posted again.');
            }

            $result = $this->postings->post($this->context($invoice, $plan), $actorId);
            if ($status === InvoicePostingPlanStatus::Prepared) {
                $plan->forceFill([
                    'status' => InvoicePostingPlanStatus::Posted->value,
                    'finance_posting_reference' => $result->journalNumber,
                    'posted_by' => $actorId,
                    'posted_at' => now(),
                    'row_version' => (int) $plan->row_version + 1,
                ])->save();
            }

            return $result;
        });
    }

    public function reverse(
        Invoice $invoice,
        string $reversalDate,
        ?int $actorId,
        string $reason,
    ): PostingResultData {
        return DB::transaction(function () use ($invoice, $reversalDate, $actorId, $reason): PostingResultData {
            $invoice = Invoice::query()->lockForUpdate()->findOrFail($invoice->getKey());
            $plan = $this->lockedPlan($invoice);
            if ($this->status($plan) !== InvoicePostingPlanStatus::Posted) {
                throw new InvalidArgumentException('Only posted invoice posting plans can be reversed.');
            }
            $reason = trim($reason);
            if ($reason === '') {
                throw new InvalidArgumentException('Invoice reversal reason is required.');
            }
            $this->assertDate($reversalDate, 'Invoice reversal date');
            if ($reversalDate < $plan->posting_date->toDateString()) {
                throw new InvalidArgumentException('Invoice reversal date cannot be before the posting date.');
            }

            $result = $this->reversals->reverseSource(
                (int) $invoice->tenant_id,
                $invoice->organization_unit_id,
                InvoiceFinanceSource::MODULE,
                InvoiceFinanceSource::POSTING_TYPE,
                (int) $invoice->getKey(),
                $reversalDate,
                $actorId,
                $reason,
            );
            $plan->forceFill([
                'status' => InvoicePostingPlanStatus::Reversed->value,
                'finance_reversal_reference' => $result->journalNumber,
                'reversed_by' => $actorId,
                'reversed_at' => now(),
                'reversal_reason' => $reason,
                'row_version' => (int) $plan->row_version + 1,
            ])->save();

            return $result;
        });
    }

    public function planFor(Invoice $invoice): InvoicePostingPlan
    {
        return InvoicePostingPlan::query()
            ->where('tenant_id', $invoice->tenant_id)
            ->where('invoice_id', $invoice->getKey())
            ->firstOrFail();
    }

    private function lockedPlan(Invoice $invoice): InvoicePostingPlan
    {
        $plan = InvoicePostingPlan::query()
            ->where('tenant_id', $invoice->tenant_id)
            ->where('invoice_id', $invoice->getKey())
            ->lockForUpdate()
            ->first();
        if (! $plan instanceof InvoicePostingPlan) {
            throw new InvalidArgumentException('Invoice posting plan is missing.');
        }
        if ($plan->organization_unit_id !== $invoice->organization_unit_id) {
            throw new InvalidArgumentException('Invoice posting plan scope does not match the invoice.');
        }

        return $plan;
    }

    private function context(Invoice $invoice, InvoicePostingPlan $plan): PostingContext
    {
        $profile = $plan->posting_profile_code instanceof FinancePostingProfileCode
            ? $plan->posting_profile_code
            : FinancePostingProfileCode::from((string) $plan->posting_profile_code);
        $lines = [];
        foreach ($plan->lines as $line) {
            if (! is_array($line)) {
                throw new InvalidArgumentException('Invoice posting plan line data is invalid.');
            }
            $data = InvoicePostingLineData::fromArray($line);
            $lines[] = new PostingLine(
                lineName: $data->description ?? $data->role->value,
                debit: $data->debit,
                credit: $data->credit,
                description: $data->description,
                profileKey: $data->role->value,
                sourceLineType: $data->sourceLineType,
                sourceLineId: $data->sourceLineId,
            );
        }

        return new PostingContext(
            source: new PostingSourceData(
                sourceType: InvoiceFinanceSource::POSTING_TYPE,
                sourceId: (int) $invoice->getKey(),
                tenantId: (int) $invoice->tenant_id,
                organizationUnitId: $invoice->organization_unit_id,
                sourceModule: InvoiceFinanceSource::MODULE,
                sourceNumber: (string) $invoice->invoice_number,
                sourceDate: $invoice->invoice_date->toDateString(),
            ),
            postingDate: $plan->posting_date->toDateString(),
            currencyId: $invoice->currency_id,
            exchangeRate: (string) $invoice->exchange_rate,
            lines: $lines,
            description: 'Invoice posting '.$invoice->invoice_number,
            postingProfileCode: $profile->value,
        );
    }

    private function assertPlan(Invoice $invoice, InvoicePostingPlanData $data): void
    {
        $this->assertDate($data->postingDate, 'Invoice posting date');
        if ($data->postingDate < $invoice->invoice_date->toDateString()) {
            throw new InvalidArgumentException('Invoice posting date cannot be before the invoice date.');
        }
        if ($data->lines === []) {
            throw new InvalidArgumentException('Invoice posting plan requires at least one line.');
        }

        $debit = self::ZERO;
        $credit = self::ZERO;
        foreach ($data->lines as $line) {
            if (! $line instanceof InvoicePostingLineData) {
                throw new InvalidArgumentException('Invoice posting plan lines are invalid.');
            }
            if ($this->math->isNegative($line->debit) || $this->math->isNegative($line->credit)) {
                throw new InvalidArgumentException('Invoice posting plan amounts cannot be negative.');
            }
            $hasDebit = ! $this->math->isZero($line->debit);
            $hasCredit = ! $this->math->isZero($line->credit);
            if ($hasDebit === $hasCredit) {
                throw new InvalidArgumentException('Each invoice posting plan line must have exactly one non-zero side.');
            }
            if (($line->sourceLineType === null) !== ($line->sourceLineId === null)) {
                throw new InvalidArgumentException('Invoice posting plan source line type and ID must be supplied together.');
            }
            $debit = $this->math->add($debit, $line->debit);
            $credit = $this->math->add($credit, $line->credit);
        }

        if ($this->math->compare($debit, $credit) !== 0) {
            throw new InvalidArgumentException('Invoice posting plan must be balanced.');
        }
    }

    private function assertDate(string $date, string $field): void
    {
        try {
            $normalized = CarbonImmutable::parse($date)->toDateString();
        } catch (\Throwable) {
            throw new InvalidArgumentException($field.' is invalid.');
        }
        if ($normalized !== $date) {
            throw new InvalidArgumentException($field.' must use YYYY-MM-DD format.');
        }
    }

    private function status(InvoicePostingPlan $plan): InvoicePostingPlanStatus
    {
        return $plan->status instanceof InvoicePostingPlanStatus
            ? $plan->status
            : InvoicePostingPlanStatus::from((string) $plan->status);
    }
}
