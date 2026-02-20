<?php

namespace App\Services;

use App\Enums\AuditAction;
use App\Models\Printer;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class PrinterService
{
    public function __construct(
        private readonly AuditService $auditService
    ) {}

    public function paginate(string $tenantId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Printer::where('tenant_id', $tenantId)
            ->with('businessLocation');

        if (isset($filters['business_location_id'])) {
            $query->where('business_location_id', $filters['business_location_id']);
        }
        if (isset($filters['search'])) {
            $query->where('name', 'like', '%'.$filters['search'].'%');
        }

        return $query->orderBy('name')->paginate($perPage);
    }

    public function create(array $data): Printer
    {
        return DB::transaction(function () use ($data) {
            if (! empty($data['is_default'])) {
                Printer::where('tenant_id', $data['tenant_id'])
                    ->when(isset($data['business_location_id']), function ($q) use ($data) {
                        $q->where('business_location_id', $data['business_location_id']);
                    })
                    ->update(['is_default' => false]);
            }

            $printer = Printer::create($data);

            $this->auditService->log(
                action: AuditAction::Created,
                auditableType: Printer::class,
                auditableId: $printer->id,
                newValues: $data
            );

            return $printer->fresh(['businessLocation']);
        });
    }

    public function update(string $id, array $data): Printer
    {
        return DB::transaction(function () use ($id, $data) {
            $printer = Printer::findOrFail($id);
            $oldValues = $printer->toArray();

            if (! empty($data['is_default'])) {
                Printer::where('tenant_id', $printer->tenant_id)
                    ->where('id', '!=', $id)
                    ->where('business_location_id', $printer->business_location_id)
                    ->update(['is_default' => false]);
            }

            $printer->update($data);

            $this->auditService->log(
                action: AuditAction::Updated,
                auditableType: Printer::class,
                auditableId: $printer->id,
                oldValues: $oldValues,
                newValues: $data
            );

            return $printer->fresh(['businessLocation']);
        });
    }

    public function delete(string $id): void
    {
        Printer::findOrFail($id)->delete();
    }
}
