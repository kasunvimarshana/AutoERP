<?php

declare(strict_types=1);

namespace Modules\Finance\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class PostingProfileRuleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'line_key' => (string) $this->line_key,
            'account_role_id' => (int) $this->account_role_id,
            'description' => $this->description,
            'role' => $this->whenLoaded('role', fn (): array => [
                'id' => (int) $this->role->getKey(),
                'code' => (string) $this->role->code,
                'name' => (string) $this->role->name,
                'is_active' => (bool) $this->role->is_active,
            ]),
        ];
    }
}
