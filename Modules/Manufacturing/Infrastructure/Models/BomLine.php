<?php

declare(strict_types=1);

namespace Modules\Manufacturing\Infrastructure\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BomLine extends Model
{
    use HasFactory;

    protected $table = 'bom_lines';

    public $timestamps = false;

    protected $fillable = [
        'bom_id',
        'component_product_id',
        'component_variant_id',
        'quantity',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
    ];

    public function bom(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Bom::class, 'bom_id');
    }
}
