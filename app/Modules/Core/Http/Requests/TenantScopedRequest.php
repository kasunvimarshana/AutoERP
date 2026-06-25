<?php

declare(strict_types=1);

namespace Modules\Core\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\Unique;
use Illuminate\Validation\ValidationException;

abstract class TenantScopedRequest extends QueryRequest
{
    public function authorize(): bool
    {
        return $this->currentUserId() !== null;
    }

    public function tenantId(): int
    {
        $value = $this->attributes->get($this->tenantIdAttribute());

        if (! is_numeric($value) || (int) $value < 1) {
            throw ValidationException::withMessages([
                'tenant_id' => ['A valid tenant context is required.'],
            ]);
        }

        return (int) $value;
    }

    public function organizationUnitId(): ?int
    {
        $value = $this->attributes->get($this->organizationUnitIdAttribute());

        return is_numeric($value) && (int) $value > 0 ? (int) $value : null;
    }

    public function currentUserId(): ?int
    {
        $value = $this->attributes->get($this->currentUserIdAttribute());

        return is_numeric($value) && (int) $value > 0 ? (int) $value : null;
    }

    protected function tenantExists(string $table, string $column = 'id'): Exists
    {
        return Rule::exists($table, $column)
            ->where('tenant_id', $this->tenantId());
    }

    protected function tenantUnique(string $table, string $column = 'NULL'): Unique
    {
        return Rule::unique($table, $column)
            ->where('tenant_id', $this->tenantId());
    }

    public function page(): int
    {
        $value = $this->input('page', 1);

        return is_numeric($value) ? max((int) $value, 1) : 1;
    }

    public function perPage(): int
    {
        $value = $this->input('per_page', 25);
        $perPage = is_numeric($value) ? (int) $value : 25;

        return min(max($perPage, 1), 100);
    }

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        // Request payload is never an authority for tenant or organization scope.
        // Missing middleware/context must fail validation instead of falling back
        // to client-supplied identifiers.
        $tenantId = $this->attributes->get($this->tenantIdAttribute());
        $organizationUnitId = $this->attributes->get($this->organizationUnitIdAttribute());

        $this->merge([
            'tenant_id' => is_numeric($tenantId) ? (int) $tenantId : null,
            'organization_unit_id' => is_numeric($organizationUnitId) && (int) $organizationUnitId > 0
                ? (int) $organizationUnitId
                : null,
        ]);
    }

    private function tenantIdAttribute(): string
    {
        return (string) config('core.current_tenant.id_attribute', 'current_tenant_id');
    }

    private function organizationUnitIdAttribute(): string
    {
        return (string) config('core.current_organization_unit.id_attribute', 'current_organization_unit_id');
    }

    private function currentUserIdAttribute(): string
    {
        return (string) config('core.current_user.id_attribute', 'current_user_id');
    }
}
