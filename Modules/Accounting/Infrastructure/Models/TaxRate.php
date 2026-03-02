<?php
declare(strict_types=1);
namespace Modules\Accounting\Infrastructure\Models;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class TaxRate extends Model {
    use HasFactory;
    protected $table = 'tax_rates';
    protected $fillable = [
        'tenant_id','name','rate','type','is_active','is_compound',
    ];
    protected $casts = [
        'rate'        => 'decimal:4',
        'is_active'   => 'boolean',
        'is_compound' => 'boolean',
    ];
    protected static function booted(): void {
        static::addGlobalScope('tenant', function (Builder $q): void {
            if (app()->bound('tenant.id')) $q->where('tax_rates.tenant_id', app('tenant.id'));
        });
    }
}
