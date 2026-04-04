<?php
namespace Modules\Accounting\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class JournalEntryModel extends BaseModel
{
    protected $table = 'journal_entries';

    protected $fillable = [
        'tenant_id',
        'reference_number',
        'status',
        'entry_date',
        'description',
        'source_type',
        'source_id',
        'posted_by',
        'posted_at',
        'reversed_by',
        'reversed_at',
    ];

    protected $casts = [
        'entry_date'  => 'date',
        'posted_at'   => 'datetime',
        'reversed_at' => 'datetime',
        'created_at'  => 'datetime',
        'updated_at'  => 'datetime',
        'deleted_at'  => 'datetime',
    ];

    public function lines()
    {
        return $this->hasMany(JournalLineModel::class, 'journal_entry_id');
    }
}
