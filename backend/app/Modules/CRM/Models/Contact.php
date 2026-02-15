<?php

declare(strict_types=1);

namespace App\Modules\CRM\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Core\Traits\HasUuid;
use App\Core\Traits\TenantScoped;
use App\Core\Traits\Auditable;
use App\Models\User;

class Contact extends Model
{
    use HasFactory, SoftDeletes, HasUuid, TenantScoped, Auditable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'tenant_id',
        'customer_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'mobile',
        'designation',
        'department',
        'is_primary',
        'is_decision_maker',
        'email_opt_in',
        'sms_opt_in',
        'phone_opt_in',
        'preferred_contact_method',
        'preferred_contact_time',
        'linkedin_url',
        'twitter_handle',
        'is_active',
        'notes',
        'birthday',
        'custom_fields',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_primary' => 'boolean',
        'is_decision_maker' => 'boolean',
        'email_opt_in' => 'boolean',
        'sms_opt_in' => 'boolean',
        'phone_opt_in' => 'boolean',
        'is_active' => 'boolean',
        'birthday' => 'date',
        'custom_fields' => 'array',
    ];

    /**
     * Get the customer that owns the contact.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the user who created the contact.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the contact.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the contact's full name.
     */
    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    /**
     * Check if contact is active.
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Check if this is the primary contact.
     */
    public function isPrimary(): bool
    {
        return $this->is_primary;
    }

    /**
     * Check if contact is a decision maker.
     */
    public function isDecisionMaker(): bool
    {
        return $this->is_decision_maker;
    }

    /**
     * Check if contact can be contacted via email.
     */
    public function canContactViaEmail(): bool
    {
        return $this->email_opt_in && !empty($this->email);
    }

    /**
     * Check if contact can be contacted via SMS.
     */
    public function canContactViaSms(): bool
    {
        return $this->sms_opt_in && !empty($this->mobile);
    }

    /**
     * Check if contact can be contacted via phone.
     */
    public function canContactViaPhone(): bool
    {
        return $this->phone_opt_in && (!empty($this->phone) || !empty($this->mobile));
    }
}
