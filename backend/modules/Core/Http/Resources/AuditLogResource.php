<?php

namespace Modules\Core\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuditLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'audit_event' => $this->event,
            'entity_reference' => $this->buildEntityReference(),
            'actor_information' => $this->buildActorInfo(),
            'change_summary' => $this->buildChangeSummary(),
            'request_context' => $this->buildRequestContext(),
            'categorization' => $this->tags ?? [],
            'audit_timestamp' => $this->created_at->toIso8601String(),
            'human_readable_time' => $this->created_at->diffForHumans(),
        ];
    }

    private function buildEntityReference(): array
    {
        return [
            'entity_type' => $this->getEntityTypeName(),
            'entity_id' => $this->auditable_id,
            'entity_class' => $this->auditable_type,
        ];
    }

    private function buildActorInfo(): array
    {
        return [
            'user_id' => $this->user_id,
            'user_type' => $this->user_type,
            'tenant_id' => $this->tenant_id,
        ];
    }

    private function buildChangeSummary(): array
    {
        $hasChanges = !empty($this->old_values) || !empty($this->new_values);
        
        $summary = [
            'has_modifications' => $hasChanges,
            'fields_changed' => $hasChanges ? array_keys($this->new_values ?? []) : [],
        ];

        if ($hasChanges) {
            $summary['modifications'] = $this->formatChanges();
        }

        return $summary;
    }

    private function buildRequestContext(): array
    {
        return [
            'request_url' => $this->url,
            'origin_ip' => $this->maskSensitiveIp($this->ip_address),
            'client_agent' => $this->truncateUserAgent($this->user_agent),
        ];
    }

    private function formatChanges(): array
    {
        $formatted = [];
        $oldVals = $this->old_values ?? [];
        $newVals = $this->new_values ?? [];

        foreach ($newVals as $field => $newValue) {
            $formatted[] = [
                'field_name' => $field,
                'previous_value' => $oldVals[$field] ?? null,
                'updated_value' => $newValue,
                'value_changed' => ($oldVals[$field] ?? null) !== $newValue,
            ];
        }

        return $formatted;
    }

    private function getEntityTypeName(): string
    {
        if (!$this->auditable_type) {
            return 'unknown';
        }

        $parts = explode('\\', $this->auditable_type);
        return strtolower(end($parts));
    }

    private function maskSensitiveIp(?string $ip): ?string
    {
        if (!$ip) {
            return null;
        }

        // Handle IPv4
        if (strpos($ip, '.') !== false) {
            $segments = explode('.', $ip);
            if (count($segments) === 4) {
                return "{$segments[0]}.{$segments[1]}.**.***";
            }
        }

        // Handle IPv6
        if (strpos($ip, ':') !== false) {
            $segments = explode(':', $ip);
            if (count($segments) >= 3) {
                return "{$segments[0]}:{$segments[1]}:****:****";
            }
        }

        return '***.***.***';
    }

    private function truncateUserAgent(?string $agent): ?string
    {
        if (!$agent || strlen($agent) <= 100) {
            return $agent;
        }

        return substr($agent, 0, 97) . '...';
    }
}
