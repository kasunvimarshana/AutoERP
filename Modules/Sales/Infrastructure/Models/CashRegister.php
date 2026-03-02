<?php
declare(strict_types=1);
namespace Modules\Sales\Infrastructure\Models;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
class CashRegister extends Model {
    protected $table = 'cash_registers';
    protected $fillable = [
        'tenant_id','name','location_id','opening_balance',
        'current_balance','is_open','opened_at','closed_at','opened_by',
    ];
    protected $casts = [
        'opening_balance' => 'decimal:4',
        'current_balance' => 'decimal:4',
        'is_open'         => 'boolean',
        'opened_at'       => 'datetime',
        'closed_at'       => 'datetime',
    ];
    protected static function booted(): void {
        static::addGlobalScope('tenant', function (Builder $q): void {
            if (app()->bound('tenant.id')) $q->where('cash_registers.tenant_id', app('tenant.id'));
        });
    }
}
