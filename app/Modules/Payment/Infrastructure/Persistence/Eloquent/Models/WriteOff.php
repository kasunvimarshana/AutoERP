<?php

declare(strict_types=1);

namespace Modules\Payment\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Payment\Infrastructure\Persistence\Eloquent\Concerns\HasTenantAndOrganization;

class WriteOff extends Model
{
    use HasTenantAndOrganization;

    protected $table = 'write_offs';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'row_version' => 'integer',
            'metadata' => 'array',
            'amount' => 'decimal:4',
        ];
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\Finance\\Infrastructure\\Persistence\\Eloquent\\Models\\JournalEntry',
            'journal_entry_id'
        );
    }
}
