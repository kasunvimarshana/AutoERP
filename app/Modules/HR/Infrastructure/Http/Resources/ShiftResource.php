<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\HR\Domain\Entities\Shift;

class ShiftResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var Shift $entity */
        $entity = $this->resource;

        return [
            'id' => $entity->getId(),
            'tenant_id' => $entity->getTenantId(),
            'name' => $entity->getName(),
            'code' => $entity->getCode(),
            'shift_type' => $entity->getShiftType()->value,
            'start_time' => $entity->getStartTime(),
            'end_time' => $entity->getEndTime(),
            'break_duration' => $entity->getBreakDuration(),
            'work_days' => $entity->getWorkDays(),
            'grace_minutes' => $entity->getGraceMinutes(),
            'overtime_threshold' => $entity->getOvertimeThreshold(),
            'is_night_shift' => $entity->isNightShift(),
            'is_active' => $entity->isActive(),
            'metadata' => $entity->getMetadata(),
            'created_at' => $entity->getCreatedAt()->format('c'),
            'updated_at' => $entity->getUpdatedAt()->format('c'),
        ];
    }
}
