<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\ReferenceData\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\ReferenceData\Services\ReferenceDataAuthorizationService;

abstract class ManageReferenceDataRequest extends TenantScopedRequest
{
    public function authorize(): bool
    {
        return parent::authorize()
            && app(ReferenceDataAuthorizationService::class)->canManageCurrent();
    }
}
