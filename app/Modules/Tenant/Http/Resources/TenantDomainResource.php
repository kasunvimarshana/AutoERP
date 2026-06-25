<?php

declare(strict_types=1);

namespace Modules\Tenant\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Core\DTOs\DataRecord;

final class TenantDomainResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $values = $this->resource instanceof DataRecord
            ? $this->resource->toArray()
            : (is_array($this->resource) ? $this->resource : []);

        return array_intersect_key($values, array_flip([
            'id',
            'domain',
            'is_primary',
            'status',
            'ownership_status',
            'routing_status',
            'tls_status',
            'reachability_status',
            'operational_status',
            'operational_error_code',
            'operational_error_message',
            'last_operational_check_at',
            'operational_retry_at',
            'tls_expires_at',
            'verification_method',
            'verification_error_code',
            'verification_error_message',
            'last_verification_attempt_at',
            'last_verified_at',
            'revalidation_due_at',
            'verification_expires_at',
            'verified_at',
            'row_version',
            'created_at',
            'updated_at',
        ]));
    }
}
