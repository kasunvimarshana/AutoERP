<?php

declare(strict_types=1);

namespace Modules\Finance\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class FinanceAccountSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'code' => (string) $this->code,
            'name' => (string) $this->name,
            'normal_balance' => $this->enum($this->normal_balance),
            'is_posting_account' => (bool) $this->is_posting_account,
            'is_active' => (bool) $this->is_active,
        ];
    }

    private function enum(mixed $value): mixed
    {
        return $value instanceof \BackedEnum ? $value->value : $value;
    }
}
