<?php

declare(strict_types=1);

namespace Modules\Invoice\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class InvoiceSourceLink extends CoreModel
{
    protected $table = 'invoice_source_links';

    protected $fillable = [
        'invoice_id',
        'source_type',
        'source_id',
        'source_number',
        'source_date',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'invoice_id' => 'integer',
            'source_id' => 'integer',
            'source_date' => 'date',
        ]);
    }
}
