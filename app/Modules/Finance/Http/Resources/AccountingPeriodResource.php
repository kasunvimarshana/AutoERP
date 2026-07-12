<?php

declare(strict_types=1);

namespace Modules\Finance\Http\Resources;

use BackedEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class AccountingPeriodResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'row_version' => (int) $this->row_version,
            'code' => $this->code,
            'name' => $this->name,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'status' => $this->status instanceof BackedEnum
                ? $this->status->value
                : (string) $this->status,
            'events' => $this->whenLoaded('events', fn (): array => $this->events->map(
                static fn ($event): array => [
                    'id' => (int) $event->getKey(),
                    'event_type' => $event->event_type instanceof BackedEnum
                        ? $event->event_type->value
                        : (string) $event->event_type,
                    'from_status' => $event->from_status instanceof BackedEnum
                        ? $event->from_status->value
                        : $event->from_status,
                    'to_status' => $event->to_status instanceof BackedEnum
                        ? $event->to_status->value
                        : (string) $event->to_status,
                    'reason' => $event->reason,
                    'actor_id' => $event->actor_id === null ? null : (int) $event->actor_id,
                    'occurred_at' => $event->occurred_at?->toISOString(),
                ],
            )->all(), []),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
