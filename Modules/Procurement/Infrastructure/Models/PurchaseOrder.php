<?php
declare(strict_types=1);
namespace Modules\Procurement\Infrastructure\Models;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Procurement\Domain\Enums\PurchaseStatus;
class PurchaseOrder extends Model {
    use HasFactory, SoftDeletes;
    protected $table = 'purchase_orders';
    protected $fillable = [
        'tenant_id','vendor_id','po_number','status','subtotal',
        'tax_amount','total','expected_delivery_date','notes','created_by',
    ];
    protected $casts = [
        'subtotal'              => 'decimal:4',
        'tax_amount'            => 'decimal:4',
        'total'                 => 'decimal:4',
        'expected_delivery_date'=> 'date',
        'status'                => PurchaseStatus::class,
    ];
    protected static function booted(): void {
        static::addGlobalScope('tenant', function (Builder $q): void {
            if (app()->bound('tenant.id')) $q->where('purchase_orders.tenant_id', app('tenant.id'));
        });
    }
    public function vendor(): \Illuminate\Database\Eloquent\Relations\BelongsTo {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }
    public function lines(): \Illuminate\Database\Eloquent\Relations\HasMany {
        return $this->hasMany(PurchaseOrderLine::class, 'purchase_order_id');
    }
}
