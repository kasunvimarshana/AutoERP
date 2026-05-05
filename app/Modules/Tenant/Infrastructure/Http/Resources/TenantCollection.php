<?php

declare(strict_types=1);

namespace Modules\Tenant\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class TenantCollection extends ResourceCollection
{
    public $collects = TenantResource::class;

    public function toArray(Request $request): array
    {
        return $this->collection
            ->map(static function (mixed $tenant) use ($request): array {
                if ($tenant instanceof TenantResource) {
                    return $tenant->toArray($request);
                }

                return (new TenantResource($tenant))->toArray($request);
            })
            ->all();
    }
}
