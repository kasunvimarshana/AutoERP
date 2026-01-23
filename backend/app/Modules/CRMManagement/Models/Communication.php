<?php

namespace App\Modules\CRMManagement\Models;

use App\Modules\CustomerManagement\Models\Customer;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Communication extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'communication_type',
        'direction',
        'subject',
        'message',
        'status',
        'sent_at',
        'delivered_at',
        'read_at',
        'reference_type',
        'reference_id',
        'created_by',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'read_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($communication) {
            if (empty($communication->uuid)) {
                $communication->uuid = Str::uuid();
            }
        });
    }

    /**
     * Get the tenant that owns the communication
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }

    /**
     * Get the customer that owns the communication
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the user who created the communication
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * Get the reference model (polymorphic)
     */
    public function reference()
    {
        return $this->morphTo('reference');
    }

    /**
     * Check if communication has been read
     */
    public function isRead(): bool
    {
        return !is_null($this->read_at);
    }

    /**
     * Check if communication has been sent
     */
    public function isSent(): bool
    {
        return !is_null($this->sent_at);
    }

    /**
     * Mark as sent
     */
    public function markAsSent(): void
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    /**
     * Mark as delivered
     */
    public function markAsDelivered(): void
    {
        $this->update([
            'status' => 'delivered',
            'delivered_at' => now(),
        ]);
    }

    /**
     * Mark as read
     */
    public function markAsRead(): void
    {
        $this->update([
            'status' => 'read',
            'read_at' => now(),
        ]);
    }

    /**
     * Activity log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Scope: By tenant
     */
    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope: By customer
     */
    public function scopeForCustomer($query, int $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    /**
     * Scope: By communication type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('communication_type', $type);
    }

    /**
     * Scope: By status
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: By direction
     */
    public function scopeByDirection($query, string $direction)
    {
        return $query->where('direction', $direction);
    }

    /**
     * Scope: Sent communications
     */
    public function scopeSent($query)
    {
        return $query->whereNotNull('sent_at');
    }

    /**
     * Scope: Unread communications
     */
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    /**
     * Scope: Failed communications
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }
}
