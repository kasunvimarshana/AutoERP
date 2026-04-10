<?php

namespace App\Modules\Identity\Models;

use BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PartyAddresse extends BaseModel
{
    protected $table = 'party_addresses';

    protected $fillable = [
        'party_id',
        'type',
        'line1',
        'line2',
        'city',
        'state',
        'postal_code',
        'country_code',
        'is_default'
    ];

    protected $casts = [
        'is_default' => 'boolean'
    ];

    public function party(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Identity\Models\Party::class, 'party_id');
    }
}
