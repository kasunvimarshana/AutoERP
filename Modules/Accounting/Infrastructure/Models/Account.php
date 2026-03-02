<?php
declare(strict_types=1);
namespace Modules\Accounting\Infrastructure\Models;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Accounting\Domain\Enums\AccountType;
class Account extends Model {
    use HasFactory;
    protected $table = 'accounts';
    protected $fillable = [
        'tenant_id','parent_id','code','name','type','normal_balance',
        'description','is_active','opening_balance','current_balance',
    ];
    protected $casts = [
        'opening_balance' => 'decimal:4',
        'current_balance' => 'decimal:4',
        'is_active'       => 'boolean',
        'type'            => AccountType::class,
    ];
    protected static function booted(): void {
        static::addGlobalScope('tenant', function (Builder $q): void {
            if (app()->bound('tenant.id')) $q->where('accounts.tenant_id', app('tenant.id'));
        });
    }
    public function parent(): \Illuminate\Database\Eloquent\Relations\BelongsTo {
        return $this->belongsTo(self::class, 'parent_id');
    }
    public function children(): \Illuminate\Database\Eloquent\Relations\HasMany {
        return $this->hasMany(self::class, 'parent_id');
    }
    public function journalLines(): \Illuminate\Database\Eloquent\Relations\HasMany {
        return $this->hasMany(JournalLine::class, 'account_id');
    }
}
