<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

/**
 * Base Model
 * 
 * Provides common functionality for all models including:
 * - Tenant awareness
 * - Automatic timestamps
 * - Soft deletes
 * - Activity logging hooks
 */
abstract class BaseModel extends EloquentModel
{
    use HasFactory, SoftDeletes;

    /**
     * Indicates if the model should automatically set tenant_id
     */
    protected bool $tenantAware = true;

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Boot the model
     *
     * @return void
     */
    protected static function boot(): void
    {
        parent::boot();

        // Automatically set tenant_id on create if tenant aware
        static::creating(function ($model) {
            if ($model->tenantAware && in_array('tenant_id', $model->getFillable())) {
                if (!isset($model->tenant_id) && Auth::check()) {
                    $user = Auth::user();
                    if (method_exists($user, 'getTenantId')) {
                        $model->tenant_id = $user->getTenantId();
                    }
                }
            }
        });

        // Global scope for tenant isolation
        static::addGlobalScope('tenant', function ($builder) {
            if ((new static)->tenantAware && in_array('tenant_id', (new static)->getFillable())) {
                if (Auth::check()) {
                    $user = Auth::user();
                    if (method_exists($user, 'getTenantId')) {
                        $builder->where('tenant_id', $user->getTenantId());
                    }
                }
            }
        });
    }

    /**
     * Disable tenant awareness for this model instance
     *
     * @return self
     */
    public function withoutTenantAwareness(): self
    {
        $this->tenantAware = false;
        return $this;
    }

    /**
     * Enable tenant awareness for this model instance
     *
     * @return self
     */
    public function withTenantAwareness(): self
    {
        $this->tenantAware = true;
        return $this;
    }
}
