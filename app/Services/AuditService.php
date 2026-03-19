<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\Contracts\AuditLogRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class AuditService
{
    public function __construct(
        private readonly AuditLogRepositoryInterface $auditLogRepository,
    ) {}

    public function log(
        string $eventType,
        string $action,
        array $context = [],
        ?Request $request = null
    ): void {
        $data = [
            'tenant_id'   => $context['tenant_id'] ?? null,
            'user_id'     => $context['user_id'] ?? null,
            'event_type'  => $eventType,
            'action'      => $action,
            'entity_type' => $context['entity_type'] ?? null,
            'entity_id'   => $context['entity_id'] ?? null,
            'ip_address'  => $request?->ip() ?? $context['ip_address'] ?? null,
            'user_agent'  => $request?->userAgent() ?? $context['user_agent'] ?? null,
            'old_values'  => $context['old_values'] ?? null,
            'new_values'  => $context['new_values'] ?? null,
            'metadata'    => $context['metadata'] ?? null,
            'status'      => $context['status'] ?? 'success',
            'trace_id'    => $context['trace_id'] ?? (string) Str::uuid(),
            'occurred_at' => now(),
        ];

        $this->auditLogRepository->create($data);
    }

    public function logAuthEvent(
        string $action,
        int $userId,
        ?string $tenantId,
        Request $request,
        string $status = 'success'
    ): void {
        $this->log(
            eventType: 'auth',
            action: $action,
            context: [
                'user_id'   => $userId,
                'tenant_id' => $tenantId,
                'status'    => $status,
                'trace_id'  => (string) Str::uuid(),
            ],
            request: $request
        );
    }
}
