<?php
declare(strict_types=1);
namespace Modules\Sales\Infrastructure\Models;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
class SalePayment extends Model {
    protected $table = 'sale_payments';
    protected $fillable = [
        'tenant_id','sale_id','amount','payment_method',
        'reference_number','payment_date','notes','received_by',
    ];
    protected $casts = [
        'amount'       => 'decimal:4',
        'payment_date' => 'date',
    ];
    protected static function booted(): void {
        static::addGlobalScope('tenant', function (Builder $q): void {
            if (app()->bound('tenant.id')) $q->where('sale_payments.tenant_id', app('tenant.id'));
        });
    }
    public function sale(): \Illuminate\Database\Eloquent\Relations\BelongsTo {
        return $this->belongsTo(Sale::class, 'sale_id');
    }
}
