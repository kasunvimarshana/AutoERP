<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TenantConfig extends Model
{
    protected $fillable = ['tenant_id', 'key', 'value', 'group', 'type'];

    protected $casts = [
        'value' => 'string',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function getCastedValue()
    {
        return match($this->type) {
            'boolean' => (bool) $this->value,
            'integer' => (int) $this->value,
            'float' => (float) $this->value,
            'array', 'json' => json_decode($this->value, true),
            default => $this->value,
        };
    }
}
