<?php

declare(strict_types=1);

namespace Modules\Payment\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Modules\Payment\Enums\PaymentDirection;
use Modules\Payment\Enums\PaymentMethodDirection;
use Modules\Payment\Models\PaymentMethod;

final class PaymentMethodService
{
    public function paginate(array $filters, int $tenantId, ?int $organizationUnitId, int $perPage): LengthAwarePaginator
    {
        $query = $this->scope(PaymentMethod::query(), $tenantId, $organizationUnitId);

        if (($filters['effective'] ?? true) === true) {
            $query = $this->effectiveScope($query, $tenantId, $organizationUnitId);
        }
        if (array_key_exists('is_active', $filters) && $filters['is_active'] !== null) {
            $query->where('is_active', (bool) $filters['is_active']);
        } elseif (($filters['active_only'] ?? false) === true) {
            $query->where('is_active', true);
        }
        if (! empty($filters['direction'])) {
            $direction = (string) $filters['direction'];
            $query->whereIn('direction_allowed', [$direction, PaymentMethodDirection::Both->value]);
        }
        if (! empty($filters['method_type'])) {
            $query->where('method_type', (string) $filters['method_type']);
        }
        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(fn (Builder $scope): Builder => $scope
                ->where('code', 'like', "%{$search}%")
                ->orWhere('name', 'like', "%{$search}%"));
        }

        if (($filters['effective'] ?? true) === true) {
            $rows = $query
                ->orderByRaw('case when organization_unit_id is not null then 0 else 1 end')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get()
                ->unique(fn (PaymentMethod $method): string => strtoupper((string) $method->code))
                ->values();

            return new Paginator(
                $rows->forPage((int) request('page', 1), $perPage)->values(),
                $rows->count(),
                $perPage,
                (int) request('page', 1),
                ['path' => request()->url(), 'query' => request()->query()],
            );
        }

        return $query->orderBy('sort_order')->orderBy('name')->paginate($perPage);
    }

    public function effectiveActiveForDirection(
        int $tenantId,
        ?int $organizationUnitId,
        PaymentDirection|string $direction,
    ): Collection {
        $direction = $direction instanceof PaymentDirection
            ? $direction
            : PaymentDirection::from((string) $direction);

        return $this->effectiveScope(
            $this->scope(PaymentMethod::query(), $tenantId, $organizationUnitId),
            $tenantId,
            $organizationUnitId,
        )
            ->where('is_active', true)
            ->whereIn('direction_allowed', [$direction->value, PaymentMethodDirection::Both->value])
            ->orderByRaw('case when organization_unit_id is not null then 0 else 1 end')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->unique(fn (PaymentMethod $method): string => strtoupper((string) $method->code))
            ->values();
    }

    public function find(int $id, int $tenantId, ?int $organizationUnitId): PaymentMethod
    {
        return $this->scope(PaymentMethod::query(), $tenantId, $organizationUnitId)->findOrFail($id);
    }

    public function create(array $payload, int $tenantId, ?int $organizationUnitId): PaymentMethod
    {
        return DB::transaction(function () use ($payload, $tenantId, $organizationUnitId): PaymentMethod {
            $attributes = $this->attributes($payload, $tenantId, $organizationUnitId);
            $this->assertUniqueCode($attributes['code'], $attributes['tenant_id'], $attributes['organization_unit_id']);

            return PaymentMethod::query()->create($attributes);
        });
    }

    public function update(PaymentMethod $method, array $payload): PaymentMethod
    {
        return DB::transaction(function () use ($method, $payload): PaymentMethod {
            $locked = PaymentMethod::query()->lockForUpdate()->findOrFail($method->getKey());
            $attributes = $this->attributes($payload, (int) $locked->tenant_id, $locked->organization_unit_id);
            $this->assertUniqueCode($attributes['code'], $attributes['tenant_id'], $attributes['organization_unit_id'], (int) $locked->getKey());
            $locked->fill($attributes);
            $locked->row_version = (int) $locked->row_version + 1;
            $locked->save();

            return $locked->refresh();
        });
    }

    public function setActive(PaymentMethod $method, bool $isActive): PaymentMethod
    {
        return DB::transaction(function () use ($method, $isActive): PaymentMethod {
            $locked = PaymentMethod::query()->lockForUpdate()->findOrFail($method->getKey());
            $locked->forceFill([
                'is_active' => $isActive,
                'row_version' => (int) $locked->row_version + 1,
            ])->save();

            return $locked->refresh();
        });
    }

    public function delete(PaymentMethod $method): void
    {
        if ($method->lines()->exists() || $method->refunds()->exists()) {
            throw new InvalidArgumentException('Payment methods used by payments or refunds cannot be deleted; deactivate the method instead.');
        }

        $method->delete();
    }

    public function assertUsable(
        ?PaymentMethod $method,
        PaymentDirection|string $direction,
        ?string $referenceNumber,
        ?int $tenantId = null,
        ?int $organizationUnitId = null,
        bool $hasInstrumentDetails = false,
    ): void {
        if (! $method instanceof PaymentMethod) {
            throw new InvalidArgumentException('Payment method is required.');
        }

        $direction = $direction instanceof PaymentDirection ? $direction : PaymentDirection::from((string) $direction);
        if (! $this->isInScope($method, $tenantId, $organizationUnitId)) {
            throw new InvalidArgumentException('Payment method scope must match payment scope.');
        }
        if (! (bool) $method->is_active) {
            throw new InvalidArgumentException('Payment method is inactive.');
        }

        $allowed = $method->direction_allowed instanceof PaymentMethodDirection
            ? $method->direction_allowed
            : PaymentMethodDirection::from((string) $method->direction_allowed);
        if ($allowed !== PaymentMethodDirection::Both && $allowed->value !== $direction->value) {
            throw new InvalidArgumentException('Payment method is not allowed for this payment direction.');
        }
        if ((bool) $method->requires_reference && trim((string) $referenceNumber) === '') {
            throw new InvalidArgumentException('Payment method requires a reference number.');
        }
        if ((bool) $method->requires_instrument_details && ! $hasInstrumentDetails) {
            throw new InvalidArgumentException('Payment method requires transaction instrument details.');
        }
    }

    private function scope(Builder $query, int $tenantId, ?int $organizationUnitId): Builder
    {
        return $query->where('tenant_id', $tenantId)->where(function (Builder $scope) use ($organizationUnitId): void {
            $scope->whereNull('organization_unit_id');
            if ($organizationUnitId !== null) {
                $scope->orWhere('organization_unit_id', $organizationUnitId);
            }
        });
    }

    private function effectiveScope(Builder $query, int $tenantId, ?int $organizationUnitId): Builder
    {
        return $query->where('tenant_id', $tenantId)->where(function (Builder $scope) use ($organizationUnitId): void {
            $scope->whereNull('organization_unit_id');
            if ($organizationUnitId !== null) {
                $scope->orWhere('organization_unit_id', $organizationUnitId);
            }
        });
    }

    private function isInScope(PaymentMethod $method, ?int $tenantId, ?int $organizationUnitId): bool
    {
        if ($tenantId === null || (int) $method->tenant_id !== $tenantId) {
            return false;
        }
        if ($method->organization_unit_id !== null && (int) $method->organization_unit_id !== (int) $organizationUnitId) {
            return false;
        }

        return true;
    }

    private function attributes(array $payload, int $tenantId, ?int $organizationUnitId): array
    {
        $code = strtoupper(trim((string) ($payload['code'] ?? '')));
        if ($code === '') {
            throw ValidationException::withMessages(['code' => ['Payment method code is required.']]);
        }

        return [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'scope_key' => $this->scopeKey($tenantId, $organizationUnitId),
            'code' => $code,
            'name' => trim((string) ($payload['name'] ?? '')),
            'method_type' => (string) $payload['method_type'],
            'direction_allowed' => (string) ($payload['direction_allowed'] ?? PaymentMethodDirection::Both->value),
            'requires_reference' => (bool) ($payload['requires_reference'] ?? false),
            'requires_instrument_details' => (bool) ($payload['requires_instrument_details'] ?? false),
            'is_active' => (bool) ($payload['is_active'] ?? true),
            'sort_order' => (int) ($payload['sort_order'] ?? 0),
            'metadata' => isset($payload['metadata']) && is_array($payload['metadata']) ? $payload['metadata'] : null,
        ];
    }

    private function scopeKey(int $tenantId, ?int $organizationUnitId): string
    {
        return $organizationUnitId === null ? 'tenant:'.$tenantId : 'org:'.$tenantId.':'.$organizationUnitId;
    }

    private function assertUniqueCode(string $code, int $tenantId, ?int $organizationUnitId, ?int $exceptId = null): void
    {
        $query = PaymentMethod::query()
            ->where('scope_key', $this->scopeKey($tenantId, $organizationUnitId))
            ->whereRaw('upper(code) = ?', [strtoupper($code)]);
        if ($exceptId !== null) {
            $query->whereKeyNot($exceptId);
        }

        if ($query->lockForUpdate()->exists()) {
            throw ValidationException::withMessages(['code' => ['Payment method code already exists in this scope.']]);
        }
    }
}
