<?php

declare(strict_types=1);

namespace Modules\Finance\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class PostingProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'code' => (string) $this->code,
            'name' => (string) $this->name,
            'description' => $this->description,
            'is_active' => (bool) $this->is_active,
            'rules' => PostingProfileRuleResource::collection($this->whenLoaded('rules')),
        ];
    }
}
