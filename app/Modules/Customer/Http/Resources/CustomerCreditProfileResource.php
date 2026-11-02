<?php

declare(strict_types=1);

namespace Modules\Customer\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class CustomerCreditProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'credit_limit' => (string) $this->credit_limit,
            'credit_period_days' => $this->credit_period_days,
            'warning_threshold_percent' => (string) $this->warning_threshold_percent,
            'allow_over_credit' => (bool) $this->allow_over_credit,
            'allow_partial_payment' => (bool) $this->allow_partial_payment,
            'is_active' => (bool) $this->is_active,
        ];
    }
}
