<?php

declare(strict_types=1);

namespace Modules\Customer\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class CustomerContactResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'contact_name' => $this->contact_name,
            'designation' => $this->designation,
            'department' => $this->department,
            'email' => $this->email,
            'phone' => $this->phone,
            'mobile' => $this->mobile,
            'is_primary' => (bool) $this->is_primary,
            'is_active' => (bool) $this->is_active,
            'notes' => $this->notes,
        ];
    }
}
