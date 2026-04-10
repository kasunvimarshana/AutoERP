<?php

namespace App\Modules\Config\Models;

use BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class NumberingSequence extends BaseModel
{
    protected $table = 'numbering_sequences';

    protected $fillable = [
        'tenant_id',
        'document_type',
        'prefix',
        'suffix',
        'current_number',
        'padding_length',
        'reset_frequency',
        'last_reset_at'
    ];

    protected $casts = [
        'current_number' => 'integer',
        'padding_length' => 'integer'
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Core\Models\Tenant::class, 'tenant_id');
    }
}
