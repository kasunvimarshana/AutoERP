<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Selling\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;
use Modules\Core\Models\TenantOwnedModel;
use Modules\Invoice\Models\Invoice;
use Modules\OrganizationUnit\Models\OrganizationUnitModel;
use Modules\Payment\Enums\PaymentDocumentStatus;
use Modules\Payment\Enums\PaymentPostingStatus;
use Modules\Payment\Enums\PaymentSourceType;
use Modules\Payment\Enums\PaymentType;
use Modules\Payment\Models\Payment;
use Modules\Tenant\Models\TenantModel;
use Modules\User\Models\User;

final class SaleReturn extends TenantOwnedModel
{
    protected $table = 'sale_returns';

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        self::updating(static function (SaleReturn $saleReturn): void {
            if ($saleReturn->getOriginal('posted_at') !== null) {
                $immutable = ['return_number', 'return_date', 'sale_id', 'invoice_id', 'credit_amount', 'tenant_id', 'organization_unit_id'];
                if ($saleReturn->isDirty($immutable)) {
                    throw new LogicException('Posted sale returns are immutable in place.');
                }
            }
        });

        self::deleting(static function (SaleReturn $saleReturn): void {
            if ($saleReturn->posted_at !== null) {
                throw new LogicException('Posted sale returns cannot be deleted.');
            }
        });
    }

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'sale_id' => 'integer',
            'invoice_id' => 'integer',
            'return_date' => 'date',
            'credit_amount' => 'decimal:6',
            'credit_allocated_amount' => 'decimal:6',
            'credit_available_amount' => 'decimal:6',
            'created_by' => 'integer',
            'posted_at' => 'datetime',
        ]);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class, 'sale_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(SaleReturnLine::class, 'sale_return_id');
    }

    public function postedRefundPayments(): HasMany
    {
        return $this->hasMany(Payment::class, 'source_id')
            ->where('source_type', PaymentSourceType::SaleReturn->value)
            ->where('payment_type', PaymentType::Refund->value)
            ->where('document_status', PaymentDocumentStatus::Approved->value)
            ->where('posting_status', PaymentPostingStatus::Posted->value);
    }

    public function organizationUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationUnitModel::class, 'organization_unit_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(TenantModel::class, 'tenant_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
