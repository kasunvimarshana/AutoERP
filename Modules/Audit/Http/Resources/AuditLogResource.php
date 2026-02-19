<?php

declare(strict_types=1);

namespace Modules\Audit\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Audit Log Resource
 *
 * Transforms audit log data for API responses
 *
 * @mixin \Modules\Audit\Models\AuditLog
 */
class AuditLogResource extends JsonResource
{
    /**
     * Transform the resource into an array
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'event' => $this->event,
            'auditable_type' => $this->auditable_type,
            'auditable_id' => $this->auditable_id,
            'auditable' => $this->whenLoaded('auditable', function () {
                return [
                    'type' => $this->auditable_type,
                    'id' => $this->auditable_id,
                    'data' => $this->auditable,
                ];
            }),
            'user' => $this->whenLoaded('user', function () {
                return $this->user ? [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                ] : null;
            }),
            'user_id' => $this->user_id,
            'organization_id' => $this->organization_id,
            'old_values' => $this->old_values,
            'new_values' => $this->new_values,
            'changes' => $this->getChanges(),
            'metadata' => $this->metadata,
            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,
            'created_at' => $this->created_at?->toIso8601String(),
            'created_at_human' => $this->created_at?->diffForHumans(),
        ];
    }

    /**
     * Get formatted changes between old and new values
     */
    protected function getChanges(): array
    {
        if (! $this->old_values || ! $this->new_values) {
            return [];
        }

        $changes = [];
        $allKeys = array_unique(array_merge(
            array_keys($this->old_values),
            array_keys($this->new_values)
        ));

        foreach ($allKeys as $key) {
            $oldValue = $this->old_values[$key] ?? null;
            $newValue = $this->new_values[$key] ?? null;

            if ($oldValue !== $newValue) {
                $changes[$key] = [
                    'from' => $oldValue,
                    'to' => $newValue,
                ];
            }
        }

        return $changes;
    }
}
