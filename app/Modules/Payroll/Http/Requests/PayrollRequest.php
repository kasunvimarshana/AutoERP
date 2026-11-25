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
use Modules\Core\Contracts\PermissionCheckerInterface;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Payroll\Services\PayrollAuthorizationService;

class PayrollRequest extends TenantScopedRequest
{
    public function authorize(): bool
    {
        $userId = $this->currentUserId();
        if ($userId === null) {
            return false;
        }

        try {
            $tenantId = $this->tenantId();
        } catch (ValidationException) {
            return false;
        }

        $permissions = app(PermissionCheckerInterface::class);
        foreach (array_keys(PayrollAuthorizationService::descriptions()) as $permission) {
            if ($permissions->allows($userId, $tenantId, $permission)) {
                return true;
            }
        }

        return false;
    }

    public function rules(): array
    {
        return [];
    }
}
