<?php

declare(strict_types=1);

namespace Modules\Expense\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Modules\Expense\Models\ExpenseType;

final class ExpenseTypeService
{
    public function paginate(
        int $tenantId,
        int $perPage,
        ?string $search = null,
        ?bool $isActive = null,
    ): LengthAwarePaginator {
        $query = ExpenseType::query()->where('tenant_id', $tenantId);
        if ($search !== null && trim($search) !== '') {
            $term = trim($search);
            $query->where(fn (Builder $scope): Builder => $scope
                ->where('code', 'like', "%{$term}%")
                ->orWhere('name', 'like', "%{$term}%"));
        }
        if ($isActive !== null) {
            $query->where('is_active', $isActive);
        }

        return $query->orderBy('sort_order')->orderBy('name')->paginate($perPage);
    }

    public function create(array $payload, int $tenantId, ?int $createdBy): ExpenseType
    {
        return DB::transaction(function () use ($payload, $tenantId, $createdBy): ExpenseType {
            $attributes = $this->attributes($payload);
            $this->assertUniqueCode($tenantId, $attributes['code']);

            return ExpenseType::query()->create([
                'tenant_id' => $tenantId,
                ...$attributes,
                'created_by' => $createdBy,
            ]);
        }, 3);
    }

    public function update(
        ExpenseType $type,
        array $payload,
        int $expectedVersion,
    ): ExpenseType {
        return DB::transaction(function () use ($type, $payload, $expectedVersion): ExpenseType {
            $locked = ExpenseType::query()->lockForUpdate()->findOrFail($type->getKey());
            if ($expectedVersion < 1 || (int) $locked->row_version !== $expectedVersion) {
                throw new InvalidArgumentException('Expense type was changed by another request. Reload it before updating.');
            }

            $attributes = $this->attributes($payload);
            $this->assertUniqueCode((int) $locked->tenant_id, $attributes['code'], (int) $locked->getKey());
            $locked->forceFill([
                ...$attributes,
                'row_version' => (int) $locked->row_version + 1,
            ])->save();

            return $locked->refresh();
        }, 3);
    }

    private function attributes(array $payload): array
    {
        $code = mb_strtoupper(trim((string) ($payload['code'] ?? '')));
        $name = trim((string) ($payload['name'] ?? ''));
        if ($code === '') {
            throw ValidationException::withMessages(['code' => ['Expense type code is required.']]);
        }
        if ($name === '') {
            throw ValidationException::withMessages(['name' => ['Expense type name is required.']]);
        }

        return [
            'code' => $code,
            'name' => $name,
            'description' => isset($payload['description']) && trim((string) $payload['description']) !== ''
                ? trim((string) $payload['description'])
                : null,
            'is_active' => (bool) ($payload['is_active'] ?? true),
            'sort_order' => (int) ($payload['sort_order'] ?? 0),
        ];
    }

    private function assertUniqueCode(int $tenantId, string $code, ?int $exceptId = null): void
    {
        $query = ExpenseType::query()
            ->where('tenant_id', $tenantId)
            ->whereRaw('upper(code) = ?', [$code]);
        if ($exceptId !== null) {
            $query->whereKeyNot($exceptId);
        }
        if ($query->lockForUpdate()->exists()) {
            throw ValidationException::withMessages(['code' => ['Expense type code already exists.']]);
        }
    }
}
