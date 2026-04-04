<?php
namespace Modules\Tenant\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Tenant\Domain\Entities\Tenant;

class TenantResource extends JsonResource
{
    public function __construct(private readonly Tenant $tenant) { parent::__construct($tenant); }

    public function toArray($request): array
    {
        return [
            'id'     => $this->tenant->id,
            'name'   => $this->tenant->name,
            'slug'   => $this->tenant->slug,
            'email'  => $this->tenant->email,
            'status' => $this->tenant->status,
            'plan'   => $this->tenant->plan,
        ];
    }
}
