<?php

declare(strict_types=1);

namespace Modules\CRM\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Audit\Contracts\Auditable;
use Modules\Auth\Models\User;
use Modules\CRM\Enums\LeadStatus;
use Modules\Tenant\Contracts\TenantScoped;
use Modules\Tenant\Models\Organization;

class Lead extends Model
{
    use Auditable, HasFactory, SoftDeletes, TenantScoped;

    protected $fillable = [
        'tenant_id',
        'organization_id',
        'assigned_to',
        'status',
        'source',
        'first_name',
        'last_name',
        'company_name',
        'job_title',
        'phone',
        'mobile',
        'email',
        'website',
        'address_line1',
        'address_line2',
        'city',
        'state',
        'postal_code',
        'country',
        'estimated_value',
        'probability',
        'expected_close_date',
        'notes',
        'metadata',
        'converted_at',
        'converted_to_customer_id',
    ];

    protected $casts = [
        'status' => LeadStatus::class,
        'estimated_value' => 'decimal:2',
        'probability' => 'integer',
        'expected_close_date' => 'date',
        'metadata' => 'array',
        'converted_at' => 'datetime',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function convertedCustomer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'converted_to_customer_id');
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function isConverted(): bool
    {
        return $this->converted_at !== null;
    }
}
