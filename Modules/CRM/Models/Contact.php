<?php

declare(strict_types=1);

namespace Modules\CRM\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Audit\Contracts\Auditable;
use Modules\CRM\Enums\ContactType;
use Modules\Tenant\Contracts\TenantScoped;
use Modules\Tenant\Models\Organization;

class Contact extends Model
{
    use Auditable, HasFactory, SoftDeletes, TenantScoped;

    protected $fillable = [
        'tenant_id',
        'organization_id',
        'customer_id',
        'contact_type',
        'first_name',
        'last_name',
        'job_title',
        'department',
        'phone',
        'mobile',
        'email',
        'is_primary',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'contact_type' => ContactType::class,
        'is_primary' => 'boolean',
        'metadata' => 'array',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }
}
