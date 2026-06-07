<?php

declare(strict_types=1);

namespace Modules\Core\Http\Requests;

use Illuminate\Validation\ValidationException;

abstract class TenantScopedRequest extends QueryRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function tenantId(): int
    {
        $value = $this->attributes->get(
            (string) config('core.current_tenant.id_attribute', 'current_tenant_id'),
            $this->input('tenant_id'),
        );

        if (! is_numeric($value) || (int) $value < 1) {
            throw ValidationException::withMessages([
                'tenant_id' => ['A valid tenant context is required.'],
            ]);
        }

        return (int) $value;
    }

    public function organizationUnitId(): ?int
    {
        $value = $this->attributes->get(
            (string) config('core.current_organization_unit.id_attribute', 'current_organization_unit_id'),
            $this->input('organization_unit_id'),
        );

        return is_numeric($value) && (int) $value > 0 ? (int) $value : null;
    }

    public function currentUserId(): ?int
    {
        $value = $this->attributes->get(
            (string) config('core.current_user.id_attribute', 'current_user_id'),
            $this->user()?->getAuthIdentifier(),
        );

        return is_numeric($value) && (int) $value > 0 ? (int) $value : null;
    }

    public function perPage(): int
    {
        return min(max((int) $this->input('per_page', 25), 1), 100);
    }

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        $tenant = $this->attributes->get(
            (string) config('core.current_tenant.id_attribute', 'current_tenant_id'),
        );
        $organizationUnit = $this->attributes->get(
            (string) config('core.current_organization_unit.id_attribute', 'current_organization_unit_id'),
        );

        $scope = [];
        if ($this->attributes->has((string) config('core.current_tenant.id_attribute', 'current_tenant_id'))) {
            $scope['tenant_id'] = (int) $tenant;
            $scope['organization_unit_id'] = is_numeric($organizationUnit)
                ? (int) $organizationUnit
                : null;
        }

        if ($scope !== []) {
            $this->merge($scope);
        }
    }
}
