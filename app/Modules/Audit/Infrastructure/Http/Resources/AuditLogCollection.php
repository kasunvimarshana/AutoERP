<?php

declare(strict_types=1);

namespace Modules\Audit\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class AuditLogCollection extends ResourceCollection
{
    /** @var class-string<AuditLogResource> */
    public $collects = AuditLogResource::class;

    public function toArray(Request $request): array
    {
        return $this->collection
            ->map(static function (mixed $auditLog) use ($request): array {
                if ($auditLog instanceof AuditLogResource) {
                    return $auditLog->toArray($request);
                }

                return (new AuditLogResource($auditLog))->toArray($request);
            })
            ->all();
    }
}
