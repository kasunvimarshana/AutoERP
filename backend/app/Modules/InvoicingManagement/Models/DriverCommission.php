<?php

namespace App\Modules\InvoicingManagement\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class DriverCommission extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'tenant_id',
        'driver_id',
        'invoice_id',
        'job_card_id',
        'commission_type',
        'commission_rate',
        'commission_amount',
        'base_amount',
        'status',
        'calculation_date',
        'payment_date',
        'notes',
    ];

    protected $casts = [
        'commission_rate' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'base_amount' => 'decimal:2',
        'calculation_date' => 'date',
        'payment_date' => 'date',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($commission) {
            if (empty($commission->uuid)) {
                $commission->uuid = Str::uuid();
            }
        });
    }

    /**
     * Get the tenant that owns the driver commission
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }

    /**
     * Get the driver (user) that owns the commission
     */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'driver_id');
    }

    /**
     * Get the invoice associated with the commission
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Get the job card associated with the commission
     */
    public function jobCard(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\JobCardManagement\Models\JobCard::class);
    }

    /**
     * Check if commission is paid
     */
    public function getIsPaidAttribute(): bool
    {
        return $this->status === 'paid';
    }

    /**
     * Check if commission is pending
     */
    public function getIsPendingAttribute(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if commission is approved
     */
    public function getIsApprovedAttribute(): bool
    {
        return $this->status === 'approved';
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
     * Scope: For driver
     */
    public function scopeForDriver($query, int $driverId)
    {
        return $query->where('driver_id', $driverId);
    }

    /**
     * Scope: By status
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Pending commissions
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope: Approved commissions
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope: Paid commissions
     */
    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    /**
     * Scope: Unpaid commissions
     */
    public function scopeUnpaid($query)
    {
        return $query->whereIn('status', ['pending', 'approved']);
    }

    /**
     * Scope: By date range
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('calculation_date', [$startDate, $endDate]);
    }
}
