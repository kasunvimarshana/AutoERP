<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VariationValueTemplate extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'variation_template_id', 'name',
    ];

    public function variationTemplate(): BelongsTo
    {
        return $this->belongsTo(VariationTemplate::class);
    }
}
