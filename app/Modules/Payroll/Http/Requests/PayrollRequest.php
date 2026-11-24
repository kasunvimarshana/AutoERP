<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Payroll\Http\Requests;

use Illuminate\Validation\ValidationException;
use Modules\Core\Http\Requests\TenantScopedRequest;

class PayrollRequest extends TenantScopedRequest
{
    public function authorize(): bool
    {
        return $this->currentUserId() !== null;
    }

    public function rules(): array
    {
        return [];
    }

    public function currentUserId(): ?int
    {
        $id = parent::currentUserId();
        if ($id !== null) {
            return $id;
        }

        $user = $this->user();

        return $user?->id !== null ? (int) $user->id : null;
    }

    public function tenantId(): int
    {
        try {
            return parent::tenantId();
        } catch (\Throwable) {
            $id = $this->input('tenant_id') ?? $this->header('X-Tenant-Id') ?? $this->user()?->tenant_id;
            if (is_numeric($id) && (int) $id > 0) {
                return (int) $id;
            }

            throw ValidationException::withMessages([
                'tenant_id' => ['A valid tenant context is required.'],
            ]);
        }
    }
}
