<?php

namespace Modules\IAM\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'display_name' => $this->name,
            'email_address' => $this->email,
            'avatar_url' => $this->avatar,
            'contact_phone' => $this->phone,
            'user_timezone' => $this->timezone ?? 'UTC',
            'preferred_locale' => $this->locale ?? 'en',
            'account_status' => $this->buildAccountStatus(),
            'verification_status' => $this->buildVerificationStatus(),
            'mfa_configuration' => $this->buildMfaConfiguration(),
            'last_activity' => $this->buildLastActivity(),
            'tenant_association' => $this->when(
                $this->relationLoaded('tenant'),
                fn() => [
                    'tenant_id' => $this->tenant_id,
                    'tenant_name' => $this->tenant?->name,
                ]
            ),
            'assigned_roles' => RoleResource::collection($this->whenLoaded('roles')),
            'direct_permissions' => PermissionResource::collection($this->whenLoaded('permissions')),
            'timestamps' => [
                'email_verified' => $this->email_verified_at?->toIso8601String(),
                'account_created' => $this->created_at->toIso8601String(),
                'last_updated' => $this->updated_at->toIso8601String(),
            ],
        ];
    }

    private function buildAccountStatus(): string
    {
        if ($this->trashed()) {
            return 'deleted';
        }
        
        return $this->is_active ? 'active' : 'inactive';
    }

    private function buildVerificationStatus(): array
    {
        return [
            'is_verified' => $this->is_verified,
            'email_confirmed' => !is_null($this->email_verified_at),
            'verified_timestamp' => $this->email_verified_at?->toIso8601String(),
        ];
    }

    private function buildMfaConfiguration(): array
    {
        return [
            'enabled' => $this->mfa_enabled,
            'has_backup_codes' => !empty($this->mfa_backup_codes),
        ];
    }

    private function buildLastActivity(): ?array
    {
        if (!$this->last_login_at) {
            return null;
        }

        return [
            'login_timestamp' => $this->last_login_at->toIso8601String(),
            'human_readable' => $this->last_login_at->diffForHumans(),
            'origin_ip' => $this->maskIpAddress($this->last_login_ip),
        ];
    }

    private function maskIpAddress(?string $ip): ?string
    {
        if (!$ip) {
            return null;
        }

        // Handle IPv4
        if (strpos($ip, '.') !== false) {
            $parts = explode('.', $ip);
            if (count($parts) === 4) {
                return "{$parts[0]}.{$parts[1]}.xxx.xxx";
            }
        }

        // Handle IPv6
        if (strpos($ip, ':') !== false) {
            $segments = explode(':', $ip);
            if (count($segments) >= 3) {
                return "{$segments[0]}:{$segments[1]}:****:****";
            }
        }

        return 'xxx.xxx.xxx.xxx';
    }
}
