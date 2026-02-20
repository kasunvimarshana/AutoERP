<?php

namespace App\Services;

use App\Enums\AuditAction;
use App\Models\Currency;
use Illuminate\Support\Facades\DB;

class CurrencyService
{
    public function __construct(
        private readonly AuditService $auditService
    ) {}

    public function all(string $tenantId, bool $activeOnly = false): array
    {
        $query = Currency::where('tenant_id', $tenantId);

        if ($activeOnly) {
            $query->where('is_active', true);
        }

        return $query->orderByDesc('is_default')->orderBy('code')->get()->all();
    }

    public function create(array $data): Currency
    {
        return DB::transaction(function () use ($data) {
            // Only one currency can be default at a time
            if (! empty($data['is_default'])) {
                Currency::where('tenant_id', $data['tenant_id'])->update(['is_default' => false]);
            }

            $currency = Currency::create($data);

            $this->auditService->log(
                action: AuditAction::Created,
                auditableType: Currency::class,
                auditableId: $currency->id,
                newValues: $data
            );

            return $currency;
        });
    }

    public function update(string $id, array $data): Currency
    {
        return DB::transaction(function () use ($id, $data) {
            $currency = Currency::findOrFail($id);
            $oldValues = $currency->toArray();

            if (! empty($data['is_default'])) {
                Currency::where('tenant_id', $currency->tenant_id)
                    ->where('id', '!=', $id)
                    ->update(['is_default' => false]);
            }

            $currency->update($data);

            $this->auditService->log(
                action: AuditAction::Updated,
                auditableType: Currency::class,
                auditableId: $currency->id,
                oldValues: $oldValues,
                newValues: $data
            );

            return $currency->fresh();
        });
    }

    public function delete(string $id): void
    {
        $currency = Currency::findOrFail($id);

        if ($currency->is_default) {
            throw new \RuntimeException('Cannot delete the default currency.');
        }

        $this->auditService->log(
            action: AuditAction::Deleted,
            auditableType: Currency::class,
            auditableId: $currency->id,
            oldValues: $currency->toArray()
        );

        $currency->delete();
    }

    /**
     * Convert amount from one currency to another using exchange rates.
     * Uses BCMath for precision.
     */
    public function convert(string $amount, string $fromCode, string $toCode, string $tenantId): string
    {
        if ($fromCode === $toCode) {
            return $amount;
        }

        $from = Currency::where('tenant_id', $tenantId)->where('code', $fromCode)->firstOrFail();
        $to = Currency::where('tenant_id', $tenantId)->where('code', $toCode)->firstOrFail();

        // Convert via base rate: amount / from_rate * to_rate
        return bcdiv(bcmul($amount, $to->exchange_rate, 8), $from->exchange_rate, 8);
    }
}
