<?php

declare(strict_types=1);

namespace Modules\Finance\Http\Resources;

use Illuminate\Http\Request;
use Modules\Core\Http\Resources\ModuleResource;

final class PostingProfileRuleResource extends ModuleResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'line_key' => (string) $this->line_key,
            'description' => $this->description,
            'account_id' => $this->account_id,
            'account' => FinanceAccountSummaryResource::make($this->whenLoaded('account')),
        ];
    }
}
