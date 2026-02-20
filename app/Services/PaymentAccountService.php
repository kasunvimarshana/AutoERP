<?php

namespace App\Services;

use App\Enums\AuditAction;
use App\Models\PaymentAccount;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class PaymentAccountService
{
    public function __construct(
        private readonly AuditService $auditService
    ) {}

    public function paginate(string $tenantId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = PaymentAccount::where('tenant_id', $tenantId)
            ->with(['businessLocation']);

        if (isset($filters['is_active'])) {
            $query->where('is_active', (bool) $filters['is_active']);
        }
        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        if (isset($filters['business_location_id'])) {
            $query->where('business_location_id', $filters['business_location_id']);
        }

        return $query->orderBy('name')->paginate($perPage);
    }

    public function create(array $data): PaymentAccount
    {
        return DB::transaction(function () use ($data) {
            $account = PaymentAccount::create($data);

            $this->auditService->log(
                action: AuditAction::Created,
                auditableType: PaymentAccount::class,
                auditableId: $account->id,
                newValues: $data
            );

            return $account->fresh(['businessLocation']);
        });
    }

    public function update(string $id, array $data): PaymentAccount
    {
        return DB::transaction(function () use ($id, $data) {
            $account = PaymentAccount::findOrFail($id);
            $oldValues = $account->toArray();
            $account->update($data);

            $this->auditService->log(
                action: AuditAction::Updated,
                auditableType: PaymentAccount::class,
                auditableId: $account->id,
                oldValues: $oldValues,
                newValues: $data
            );

            return $account->fresh(['businessLocation']);
        });
    }

    public function delete(string $id): void
    {
        PaymentAccount::findOrFail($id)->delete();
    }
}
