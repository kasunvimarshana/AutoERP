<?php

declare(strict_types=1);

namespace Modules\Accounting\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Traits\BelongsToTenant;

class JournalEntryModel extends Model
{
    use BelongsToTenant;
    use SoftDeletes;

    protected $table = 'journal_entries';

    protected $fillable = [
        'tenant_id',
        'entry_number',
        'entry_date',
        'reference',
        'description',
        'currency',
        'status',
        'total_debit',
        'total_credit',
    ];

    protected $casts = [
        'total_debit' => 'string',
        'total_credit' => 'string',
    ];

    public function lines(): HasMany
    {
        return $this->hasMany(JournalEntryLineModel::class, 'journal_entry_id');
    }
}
