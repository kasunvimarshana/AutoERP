<?php

namespace Modules\Accounting\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Modules\Shared\Infrastructure\Traits\HasTenantScope;

class JournalEntryModel extends Model
{
    use HasTenantScope, SoftDeletes;

    protected $table = 'accounting_journal_entries';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'tenant_id',
        'number',
        'reference',
        'description',
        'entry_date',
        'status',
        'created_by',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (self $model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    public function lines(): HasMany
    {
        return $this->hasMany(JournalLineModel::class, 'journal_entry_id');
    }
}
