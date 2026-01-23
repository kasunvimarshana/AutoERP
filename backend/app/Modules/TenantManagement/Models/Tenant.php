<?php

namespace App\Modules\TenantManagement\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Tenant extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'uuid',
        'name',
        'slug',
        'domain',
        'database',
        'status',
        'subscription_status',
        'subscription_plan',
        'subscription_started_at',
        'subscription_expires_at',
        'max_users',
        'max_branches',
        'settings',
        'metadata',
    ];

    protected $casts = [
        'subscription_started_at' => 'datetime',
        'subscription_expires_at' => 'datetime',
        'settings' => 'array',
        'metadata' => 'array',
        'max_users' => 'integer',
        'max_branches' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($tenant) {
            if (empty($tenant->uuid)) {
                $tenant->uuid = (string) Str::uuid();
            }
            if (empty($tenant->slug)) {
                $tenant->slug = Str::slug($tenant->name);
            }
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // Relationships
    public function users()
    {
        return $this->hasMany(\App\Models\User::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeWithActiveSubscription($query)
    {
        return $query->where('subscription_status', 'active')
            ->where('subscription_expires_at', '>', now());
    }

    // Helper methods
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function hasActiveSubscription(): bool
    {
        return $this->subscription_status === 'active' 
            && $this->subscription_expires_at 
            && $this->subscription_expires_at->isFuture();
    }

    public function canAddUser(): bool
    {
        return $this->users()->count() < $this->max_users;
    }
}
