<?php

namespace App\Modules\Identity\Models;

use BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PartyContact extends BaseModel
{
    protected $table = 'party_contacts';

    protected $fillable = [
        'party_id',
        'name',
        'role',
        'email',
        'phone',
        'is_primary'
    ];

    protected $casts = [
        'is_primary' => 'boolean'
    ];

    public function party(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Identity\Models\Party::class, 'party_id');
    }
}
