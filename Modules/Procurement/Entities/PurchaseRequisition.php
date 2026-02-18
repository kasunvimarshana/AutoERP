<?php

declare(strict_types=1);

namespace Modules\Procurement\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Traits\Auditable;
use Modules\Core\Traits\Tenantable;
use Modules\Core\Traits\Translatable;
use Modules\Procurement\Database\Factories\PurchaseRequisitionFactory;

/**
 * Purchase Requisition Entity
 *
 * Represents a request for purchasing goods or services.
 */
class PurchaseRequisition extends Model
{
    use Auditable, HasFactory, SoftDeletes, Tenantable, Translatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'tenant_id',
        'requisition_number',
        'requester_id',
        'department',
        'requested_date',
        'required_date',
        'status',
        'approval_status',
        'approved_by',
        'approved_at',
        'supplier_id',
        'currency',
        'total_amount',
        'notes',
        'internal_notes',
        'rejection_reason',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'requested_date' => 'date',
        'required_date' => 'date',
        'approved_at' => 'datetime',
        'total_amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * The attributes that are translatable.
     *
     * @var array
     */
    protected $translatable = ['notes', 'internal_notes', 'rejection_reason'];

    /**
     * Get the requester.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function requester()
    {
        return $this->belongsTo('App\Models\User', 'requester_id');
    }

    /**
     * Get the approver.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function approver()
    {
        return $this->belongsTo('App\Models\User', 'approved_by');
    }

    /**
     * Get the supplier.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Get the requisition lines.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function lines()
    {
        return $this->hasMany(PurchaseRequisitionLine::class);
    }

    /**
     * Get the purchase orders created from this requisition.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    /**
     * Calculate and update totals.
     */
    public function calculateTotals(): void
    {
        $totalAmount = $this->lines()->sum('estimated_total');

        $this->update([
            'total_amount' => $totalAmount,
        ]);
    }

    /**
     * Approve the requisition.
     */
    public function approve(int $approverId): void
    {
        $this->update([
            'approval_status' => 'approved',
            'approved_by' => $approverId,
            'approved_at' => now(),
            'status' => 'approved',
        ]);

        event(new \Modules\Procurement\Events\RequisitionApproved($this));
    }

    /**
     * Reject the requisition.
     */
    public function reject(int $approverId, string $reason): void
    {
        $this->update([
            'approval_status' => 'rejected',
            'approved_by' => $approverId,
            'approved_at' => now(),
            'status' => 'rejected',
            'rejection_reason' => $reason,
        ]);
    }

    /**
     * Check if requisition is approved.
     */
    public function isApproved(): bool
    {
        return $this->approval_status === 'approved';
    }

    /**
     * Check if requisition is pending.
     */
    public function isPending(): bool
    {
        return $this->approval_status === 'pending';
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): PurchaseRequisitionFactory
    {
        return PurchaseRequisitionFactory::new();
    }
}
