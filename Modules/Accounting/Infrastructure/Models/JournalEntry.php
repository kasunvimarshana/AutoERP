<?php
declare(strict_types=1);
namespace Modules\Accounting\Infrastructure\Models;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
class JournalEntry extends Model {
    // Immutable — no soft deletes
    protected $table = 'journal_entries';
    protected $fillable = [
        'tenant_id','entry_number','entry_date','description',
        'reference_type','reference_id','is_posted','is_reversed',
        'reversed_entry_id','posted_by',
    ];
    protected $casts = [
        'entry_date'  => 'date',
        'is_posted'   => 'boolean',
        'is_reversed' => 'boolean',
    ];
    protected static function booted(): void {
        static::addGlobalScope('tenant', function (Builder $q): void {
            if (app()->bound('tenant.id')) $q->where('journal_entries.tenant_id', app('tenant.id'));
        });
    }
    public function lines(): \Illuminate\Database\Eloquent\Relations\HasMany {
        return $this->hasMany(JournalLine::class, 'journal_entry_id');
    }
}
