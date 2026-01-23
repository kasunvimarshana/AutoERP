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

class Notification extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'notification_type',
        'title',
        'message',
        'channels',
        'scheduled_at',
        'sent_at',
        'status',
        'error_message',
    ];

    protected $casts = [
        'channels' => 'array',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($notification) {
            if (empty($notification->uuid)) {
                $notification->uuid = Str::uuid();
            }
        });
    }

    /**
     * Get the tenant that owns the notification
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }

    /**
     * Get the customer that owns the notification
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Check if notification has been sent
     */
    public function isSent(): bool
    {
        return !is_null($this->sent_at);
    }

    /**
     * Check if notification is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if notification has failed
     */
    public function hasFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Check if notification is scheduled
     */
    public function isScheduled(): bool
    {
        return !is_null($this->scheduled_at) && $this->scheduled_at->isFuture();
    }

    /**
     * Check if notification is due
     */
    public function isDue(): bool
    {
        return !is_null($this->scheduled_at) && $this->scheduled_at->isPast() && $this->status === 'pending';
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
     * Mark as failed
     */
    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
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
     * Scope: By notification type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('notification_type', $type);
    }

    /**
     * Scope: By status
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Pending notifications
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope: Sent notifications
     */
    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    /**
     * Scope: Failed notifications
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope: Scheduled notifications
     */
    public function scopeScheduled($query)
    {
        return $query->whereNotNull('scheduled_at')
            ->where('scheduled_at', '>', now())
            ->where('status', 'pending');
    }

    /**
     * Scope: Due notifications
     */
    public function scopeDue($query)
    {
        return $query->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->where('status', 'pending');
    }
}
