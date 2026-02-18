<?php

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'user_type',
        'auditable_type',
        'auditable_id',
        'event',
        'old_values',
        'new_values',
        'url',
        'ip_address',
        'user_agent',
        'tags',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'tags' => 'array',
    ];

    public $timestamps = true;

    const UPDATED_AT = null;

    public function auditable()
    {
        return $this->morphTo();
    }

    public function user()
    {
        return $this->morphTo();
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function getChanges(): array
    {
        $changes = [];
        $old = $this->old_values ?? [];
        $new = $this->new_values ?? [];

        foreach ($new as $key => $value) {
            $oldValue = $old[$key] ?? null;
            if ($oldValue !== $value) {
                $changes[$key] = [
                    'old' => $oldValue,
                    'new' => $value,
                ];
            }
        }

        return $changes;
    }

    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForModel($query, string $type, $id = null)
    {
        $query->where('auditable_type', $type);

        if ($id !== null) {
            $query->where('auditable_id', $id);
        }

        return $query;
    }

    public function scopeForEvent($query, string $event)
    {
        return $query->where('event', $event);
    }
}
