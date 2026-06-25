<?php

declare(strict_types=1);

namespace Modules\Tax\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\CoreModel;

final class TaxRate extends CoreModel
{
    protected $table = 'tax_rates';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tax_id' => 'integer',
            'rate' => 'decimal:6',
            'effective_from' => 'date',
            'effective_to' => 'date',
            'active' => 'boolean',
        ]);
    }

    public function tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class, 'tax_id');
    }
}
