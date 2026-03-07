<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantConfiguration extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'key',
        'value',
        'type',
        'group',
        'description',
        'is_public',
    ];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    // ─── Relationships ─────────────────────────────────────────────────────

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    // ─── Accessors ────────────────────────────────────────────────────────

    public function getValueAttribute(mixed $value): mixed
    {
        return match ($this->type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $value,
            'float'   => (float) $value,
            'json'    => json_decode($value, true),
            'array'   => json_decode($value, true),
            default   => $value,
        };
    }

    public function setValueAttribute(mixed $value): void
    {
        $this->attributes['value'] = match ($this->type) {
            'json', 'array' => json_encode($value),
            'boolean'       => $value ? 'true' : 'false',
            default         => (string) $value,
        };
    }

    // ─── Scopes ───────────────────────────────────────────────────────────

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeByGroup($query, string $group)
    {
        return $query->where('group', $group);
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }
}
