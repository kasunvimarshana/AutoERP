<?php
declare(strict_types=1);
namespace Modules\Procurement\Infrastructure\Models;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Vendor extends Model {
    use HasFactory, SoftDeletes;
    protected $table = 'vendors';
    protected $fillable = [
        'tenant_id','name','email','phone','address','tax_number',
        'payment_terms','credit_limit','opening_balance','is_active',
    ];
    protected $casts = [
        'credit_limit'    => 'decimal:4',
        'opening_balance' => 'decimal:4',
        'is_active'       => 'boolean',
        'payment_terms'   => 'integer',
    ];
    protected static function booted(): void {
        static::addGlobalScope('tenant', function (Builder $q): void {
            if (app()->bound('tenant.id')) $q->where('vendors.tenant_id', app('tenant.id'));
        });
    }
    public function purchaseOrders(): \Illuminate\Database\Eloquent\Relations\HasMany {
        return $this->hasMany(PurchaseOrder::class, 'vendor_id');
    }
}
