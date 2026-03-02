<?php
declare(strict_types=1);
namespace Modules\Sales\Infrastructure\Models;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Sales\Domain\Enums\PaymentStatus;
use Modules\Sales\Domain\Enums\SaleStatus;
class Sale extends Model {
    use HasFactory, SoftDeletes;
    protected $table = 'sales';
    protected $fillable = [
        'tenant_id','organisation_id','invoice_number','customer_id',
        'status','payment_status','subtotal','discount_amount','tax_amount',
        'total','paid_amount','due_amount','sale_date','due_date','notes',
        'created_by','cash_register_id',
    ];
    protected $casts = [
        'subtotal'        => 'decimal:4',
        'discount_amount' => 'decimal:4',
        'tax_amount'      => 'decimal:4',
        'total'           => 'decimal:4',
        'paid_amount'     => 'decimal:4',
        'due_amount'      => 'decimal:4',
        'sale_date'       => 'date',
        'due_date'        => 'date',
        'status'          => SaleStatus::class,
        'payment_status'  => PaymentStatus::class,
    ];
    protected static function booted(): void {
        static::addGlobalScope('tenant', function (Builder $q): void {
            if (app()->bound('tenant.id')) $q->where('sales.tenant_id', app('tenant.id'));
        });
    }
    public function lines(): \Illuminate\Database\Eloquent\Relations\HasMany {
        return $this->hasMany(SaleLine::class, 'sale_id');
    }
    public function payments(): \Illuminate\Database\Eloquent\Relations\HasMany {
        return $this->hasMany(SalePayment::class, 'sale_id');
    }
}
