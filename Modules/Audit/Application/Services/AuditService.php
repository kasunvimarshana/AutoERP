<?php
namespace Modules\Audit\Application\Services;
use Modules\Audit\Infrastructure\Models\AuditModel;
class AuditService
{
    public static function record(
        string $tenantId,
        ?string $userId,
        string $action,
        string $modelType,
        string $modelId,
        array $oldValues = [],
        array $newValues = [],
    ): void {
        AuditModel::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'action' => $action,
            'model_type' => $modelType,
            'model_id' => $modelId,
            'old_values' => $oldValues ?: null,
            'new_values' => $newValues ?: null,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }
}
