<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Promotion\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;

final class PromotionRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return ['tenant_id' => ['required', 'integer', 'min:1']];
    }
}
