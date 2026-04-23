<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\HR\Domain\Entities\AttendanceLog;

class AttendanceLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var AttendanceLog $entity */
        $entity = $this->resource;

        return [
            'id' => $entity->getId(),
            'tenant_id' => $entity->getTenantId(),
            'employee_id' => $entity->getEmployeeId(),
            'biometric_device_id' => $entity->getBiometricDeviceId(),
            'punch_time' => $entity->getPunchTime()->format('c'),
            'punch_type' => $entity->getPunchType(),
            'source' => $entity->getSource(),
            'raw_data' => $entity->getRawData(),
            'processed_at' => $entity->getProcessedAt()?->format('c'),
            'created_at' => $entity->getCreatedAt()->format('c'),
            'updated_at' => $entity->getUpdatedAt()->format('c'),
        ];
    }
}
