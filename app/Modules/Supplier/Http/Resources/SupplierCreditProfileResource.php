<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Supplier\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class SupplierCreditProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'credit_limit' => $this->credit_limit !== null ? (string) $this->credit_limit : null,
            'credit_period_days' => $this->credit_period_days,
            'warning_threshold_percent' => (string) $this->warning_threshold_percent,
            'is_credit_allowed' => (bool) ($this->supplier?->is_credit_allowed ?? true),
            'is_advance_allowed' => (bool) ($this->supplier?->is_advance_allowed ?? true),
            'allow_over_credit' => (bool) $this->allow_over_credit,
            'allow_partial_payment' => (bool) $this->allow_partial_payment,
            'is_active' => (bool) $this->is_active,
        ];
    }
}
