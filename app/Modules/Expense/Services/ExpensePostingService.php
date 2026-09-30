<?php

declare(strict_types=1);

namespace Modules\Expense\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;
use Modules\Core\Services\DecimalMath;
use Modules\Expense\Constants\ExpenseIdempotency;
use Modules\Expense\DTOs\CreateExpenseData;
use Modules\Expense\Enums\ExpenseStatus;
use Modules\Expense\Models\Expense;
use Modules\Expense\Models\ExpenseType;
use Modules\Finance\Contracts\FinancePostingInterface;
use Modules\Finance\DTOs\PostingContext;
use Modules\Finance\DTOs\PostingLine;
use Modules\Finance\DTOs\PostingSourceData;
use Modules\Finance\Enums\FinanceAccountRoleCode;
use Modules\Finance\Enums\FinancePostingProfileCode;
use Modules\Idempotency\Enums\IdempotencyStatus;
use Modules\Idempotency\Models\IdempotencyRecord;
use Modules\Idempotency\Services\IdempotencyService;
use Modules\Payment\Contracts\PaymentMethodProviderInterface;
use Modules\Payment\DTOs\PaymentMethodData;
use Modules\Payment\Enums\PaymentMethodType;

final class ExpensePostingService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly ExpenseNumberService $numbers,
        private readonly PaymentMethodProviderInterface $paymentMethods,
        private readonly FinancePostingInterface $finance,
        private readonly IdempotencyService $idempotency,
    ) {}

    public function createAndPost(CreateExpenseData $data): Expense
    {
        return DB::transaction(function () use ($data): Expense {
            $record = $this->acquireIdempotency($data);
            if ($record instanceof IdempotencyRecord && ! $record->wasRecentlyCreated) {
                return $this->replay($record, $data);
            }

            $amount = $this->math->normalize($data->amount);
            if ($this->math->isNegative($amount) || $this->math->isZero($amount)) {
                throw new InvalidArgumentException('Expense amount must be greater than zero.');
            }
            if ($this->math->isNegative($data->exchangeRate) || $this->math->isZero($data->exchangeRate)) {
                throw new InvalidArgumentException('Expense exchange rate must be greater than zero.');
            }

            $type = ExpenseType::query()
                ->where('tenant_id', $data->tenantId)
                ->lockForUpdate()
                ->findOrFail($data->expenseTypeId);
            if (! (bool) $type->is_active) {
                throw new InvalidArgumentException('Expense type is inactive.');
            }

            $method = $this->paymentMethods->resolveOutbound(
                $data->paymentMethodId,
                $data->tenantId,
                $data->organizationUnitId,
                $data->referenceNumber,
                $this->hasInstrumentDetails($data),
            );
            $expense = Expense::query()->create([
                'tenant_id' => $data->tenantId,
                'organization_unit_id' => $data->organizationUnitId,
                'expense_type_id' => $type->getKey(),
                'expense_number' => $this->numbers->next($data->tenantId, $data->organizationUnitId, $data->expenseDate),
                'expense_date' => $data->expenseDate,
                'amount' => $amount,
                'currency_id' => $data->currencyId,
                'exchange_rate' => $this->math->normalize($data->exchangeRate),
                'expense_type_code_snapshot' => $type->code,
                'expense_type_name_snapshot' => $type->name,
                'payment_method_id_snapshot' => $method->id,
                'payment_method_code_snapshot' => $method->code,
                'payment_method_name_snapshot' => $method->name,
                'payment_method_type_snapshot' => $method->type,
                'reference_number' => $data->referenceNumber,
                'instrument_number' => $data->instrumentNumber,
                'instrument_date' => $data->instrumentDate,
                'external_bank_name' => $data->externalBankName,
                'notes' => $data->notes,
                'status' => ExpenseStatus::Posting->value,
                'created_by' => $data->createdBy,
            ]);

            $result = $this->finance->post($this->postingContext($expense, $method), $data->createdBy);
            $expense->forceFill([
                'status' => ExpenseStatus::Posted->value,
                'finance_posting_reference' => $result->journalNumber,
                'posted_by' => $data->createdBy,
                'posted_at' => now(),
                'row_version' => (int) $expense->row_version + 1,
            ])->save();

            if ($record instanceof IdempotencyRecord) {
                $resultData = [ExpenseIdempotency::EXPENSE_ID_KEY => (int) $expense->getKey()];
                $this->idempotency->complete($record, $resultData, $resultData);
            }

            return $this->load($expense);
        }, 3);
    }

    private function postingContext(Expense $expense, PaymentMethodData $method): PostingContext
    {
        $cashRole = $method->type === PaymentMethodType::Cash->value
            ? FinanceAccountRoleCode::Cash
            : FinanceAccountRoleCode::Bank;

        return new PostingContext(
            source: new PostingSourceData(
                sourceType: 'expense',
                sourceId: (int) $expense->getKey(),
                tenantId: (int) $expense->tenant_id,
                organizationUnitId: (int) $expense->organization_unit_id,
                sourceModule: 'expense',
                sourceNumber: (string) $expense->expense_number,
                sourceDate: $expense->expense_date?->toDateString(),
            ),
            postingDate: $expense->expense_date?->toDateString() ?? now()->toDateString(),
            currencyId: $expense->currency_id,
            exchangeRate: (string) $expense->exchange_rate,
            lines: [
                new PostingLine(
                    debit: (string) $expense->amount,
                    credit: '0.000000',
                    description: 'Expense '.$expense->expense_number,
                    profileKey: FinanceAccountRoleCode::OperatingExpense->value,
                    sourceLineType: 'expense',
                    sourceLineId: (int) $expense->getKey(),
                ),
                new PostingLine(
                    debit: '0.000000',
                    credit: (string) $expense->amount,
                    description: $method->name.' payment for '.$expense->expense_number,
                    profileKey: $cashRole->value,
                    sourceLineType: 'expense_payment',
                    sourceLineId: (int) $expense->getKey(),
                    dimensions: [
                        'payment_method_id' => (string) $method->id,
                        'payment_method_code' => $method->code,
                    ],
                ),
            ],
            description: 'Expense '.$expense->expense_number.' - '.$expense->expense_type_name_snapshot,
            postingProfileCode: FinancePostingProfileCode::ExpensePayment->value,
        );
    }

    private function acquireIdempotency(CreateExpenseData $data): ?IdempotencyRecord
    {
        $key = trim((string) $data->idempotencyKey);
        if ($key === '') {
            return null;
        }

        return $this->idempotency->acquire(
            $data->tenantId,
            $data->organizationUnitId,
            ExpenseIdempotency::CREATE_OPERATION,
            hash('sha256', $key),
            hash('sha256', json_encode($data, JSON_THROW_ON_ERROR)),
            createdBy: $data->createdBy,
        );
    }

    private function replay(IdempotencyRecord $record, CreateExpenseData $data): Expense
    {
        if ($record->status !== IdempotencyStatus::Completed) {
            throw new LogicException('Expense creation with this idempotency key is still in progress.');
        }
        $ids = is_array($record->document_ids) ? $record->document_ids : [];
        $expenseId = $ids[ExpenseIdempotency::EXPENSE_ID_KEY] ?? null;
        if (! is_numeric($expenseId) || (int) $expenseId < 1) {
            throw new LogicException('Completed expense idempotency record does not contain a valid expense identifier.');
        }

        return $this->load(Expense::query()
            ->where('tenant_id', $data->tenantId)
            ->where('organization_unit_id', $data->organizationUnitId)
            ->findOrFail((int) $expenseId));
    }

    private function hasInstrumentDetails(CreateExpenseData $data): bool
    {
        return trim((string) $data->instrumentNumber) !== ''
            || trim((string) $data->instrumentDate) !== ''
            || trim((string) $data->externalBankName) !== '';
    }

    private function load(Expense $expense): Expense
    {
        return $expense->refresh()->load(['expenseType', 'organizationUnit', 'currency']);
    }
}
