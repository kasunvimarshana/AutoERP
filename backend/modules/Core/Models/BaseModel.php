<?php

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

abstract class BaseModel extends Model
{
    use SoftDeletes;

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }

            // Automatically set tenant_id if not already set
            if (array_key_exists('tenant_id', $model->getAttributes()) && empty($model->tenant_id)) {
                $tenantContext = app(\Modules\Core\Services\TenantContext::class);
                $model->tenant_id = $tenantContext->getTenantId();
            }
        });

        // Global scope for tenant isolation
        static::addGlobalScope('tenant', function ($builder) {
            $model = $builder->getModel();
            
            // Only apply tenant scope if model has tenant_id column
            if (in_array('tenant_id', $model->getFillable())) {
                $tenantContext = app(\Modules\Core\Services\TenantContext::class);
                $tenantId = $tenantContext->getTenantId();
                
                if ($tenantId) {
                    $builder->where($model->getTable() . '.tenant_id', $tenantId);
                }
            }
        });
    }

    public function scopeActive($query)
    {
        return $query->whereNull('deleted_at');
    }

    public function scopeOrdered($query, string $column = 'created_at', string $direction = 'desc')
    {
        return $query->orderBy($column, $direction);
    }

    public function getCreatedAtHumanAttribute(): string
    {
        return $this->created_at?->diffForHumans() ?? '';
    }

    public function getUpdatedAtHumanAttribute(): string
    {
        return $this->updated_at?->diffForHumans() ?? '';
    }
}
