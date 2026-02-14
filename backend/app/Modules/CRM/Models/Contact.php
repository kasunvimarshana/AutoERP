<?php

namespace App\Modules\CRM\Models;

use App\Core\Traits\HasUuid;
use App\Core\Traits\TenantAware;
use App\Modules\Tenancy\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contact extends Model
{
    use HasFactory, SoftDeletes, TenantAware, HasUuid;

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'first_name',
        'last_name',
        'title',
        'position',
        'department',
        'email',
        'phone',
        'mobile',
        'fax',
        'date_of_birth',
        'notes',
        'is_primary',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'is_primary' => 'boolean',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * Get the tenant that owns the contact.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the customer that owns the contact.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Scope to get only active contacts.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get primary contacts.
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    /**
     * Get contact's full name.
     */
    public function getFullNameAttribute(): string
    {
        $name = trim("{$this->first_name} {$this->last_name}");
        
        if ($this->title) {
            return "{$this->title} {$name}";
        }
        
        return $name;
    }

    /**
     * Get contact's display name with position.
     */
    public function getDisplayNameAttribute(): string
    {
        $name = $this->full_name;
        
        if ($this->position) {
            return "{$name} ({$this->position})";
        }
        
        return $name;
    }
}
