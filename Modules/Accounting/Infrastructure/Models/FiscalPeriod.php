<?php
declare(strict_types=1);
namespace Modules\Accounting\Infrastructure\Models;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class FiscalPeriod extends Model {
    use HasFactory;
    protected $table = 'fiscal_periods';
    protected $fillable = [
        'tenant_id','name','start_date','end_date','is_closed','closed_at','closed_by',
    ];
    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
        'is_closed'  => 'boolean',
        'closed_at'  => 'datetime',
    ];
    protected static function booted(): void {
        static::addGlobalScope('tenant', function (Builder $q): void {
            if (app()->bound('tenant.id')) $q->where('fiscal_periods.tenant_id', app('tenant.id'));
        });
    }
}
