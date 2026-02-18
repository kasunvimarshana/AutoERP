<?php

namespace Modules\Core\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Modules\Core\Models\AuditLog;

class AuditService
{
    protected TenantContext $tenantContext;

    public function __construct(TenantContext $tenantContext)
    {
        $this->tenantContext = $tenantContext;
    }

    public function log(
        Model $model,
        string $event,
        array $oldValues = [],
        array $newValues = [],
        ?array $tags = null
    ): AuditLog {
        $auditableOldValues = $this->filterAuditableAttributes($model, $oldValues);
        $auditableNewValues = $this->filterAuditableAttributes($model, $newValues);

        return AuditLog::create([
            'tenant_id' => $this->tenantContext->getTenantId(),
            'user_id' => Auth::id(),
            'user_type' => Auth::user() ? get_class(Auth::user()) : null,
            'auditable_type' => get_class($model),
            'auditable_id' => $model->getKey(),
            'event' => $event,
            'old_values' => $auditableOldValues,
            'new_values' => $auditableNewValues,
            'url' => Request::fullUrl(),
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'tags' => $tags,
        ]);
    }

    public function logCustomEvent(
        string $event,
        ?Model $model = null,
        array $properties = [],
        ?array $tags = null
    ): AuditLog {
        return AuditLog::create([
            'tenant_id' => $this->tenantContext->getTenantId(),
            'user_id' => Auth::id(),
            'user_type' => Auth::user() ? get_class(Auth::user()) : null,
            'auditable_type' => $model ? get_class($model) : null,
            'auditable_id' => $model?->getKey(),
            'event' => $event,
            'old_values' => [],
            'new_values' => $properties,
            'url' => Request::fullUrl(),
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'tags' => $tags,
        ]);
    }

    public function getAuditTrail(Model $model, int $limit = 50)
    {
        return AuditLog::where('auditable_type', get_class($model))
            ->where('auditable_id', $model->getKey())
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    protected function filterAuditableAttributes(Model $model, array $attributes): array
    {
        if (method_exists($model, 'getAuditExclude')) {
            $exclude = $model->getAuditExclude();

            return array_diff_key($attributes, array_flip($exclude));
        }

        return $attributes;
    }
}
