<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VariationValue extends Model
{
    use HasFactory;

    protected $table = 'variation_values';

    public $timestamps = false;

    protected $fillable = [
        'variation_template_id',
        'value',
    ];

    public function template(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(VariationTemplate::class, 'variation_template_id');
    }
}
