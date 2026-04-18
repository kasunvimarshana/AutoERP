<?php

declare(strict_types=1);

namespace Modules\Audit\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Audit\Domain\Entities\AuditLog;

class AuditLogResource extends JsonResource
{
    /**
     * @param  AuditLog  $resource
     */
    public function __construct(AuditLog $resource)
    {
        parent::__construct($resource);
    }

    public function toArray(Request $request): array
    {
        /** @var AuditLog $log */
        $log = $this->resource;

        return [
            'id'             => $log->getId(),
            'tenant_id'      => $log->getTenantId(),
            'user_id'        => $log->getUserId(),
            'event'          => $log->getEvent()->value(),
            'auditable_type' => $log->getAuditableType(),
            'auditable_id'   => $log->getAuditableId(),
            'old_values'     => $log->getOldValues(),
            'new_values'     => $log->getNewValues(),
            'diff'           => $log->getDiff(),
            'url'            => $log->getUrl(),
            'ip_address'     => $log->getIpAddress(),
            'user_agent'     => $log->getUserAgent(),
            'tags'           => $log->getTags(),
            'metadata'       => $log->getMetadata(),
            'created_at'     => $log->getCreatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }
}
