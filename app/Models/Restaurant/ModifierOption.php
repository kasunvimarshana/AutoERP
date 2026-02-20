<?php

namespace App\Models\Restaurant;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModifierOption extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'modifier_options';

    protected $fillable = [
        'modifier_set_id', 'name', 'price', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'string',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function modifierSet(): BelongsTo
    {
        return $this->belongsTo(ModifierSet::class);
    }
}
