<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasAudit;

class ProductImageModel extends Model
{
    use HasAudit, SoftDeletes;

    protected $table = 'product_images';

    protected $fillable = [
        'tenant_id',
        'product_id',
        'uuid',
        'name',
        'file_path',
        'mime_type',
        'size',
        'sort_order',
        'is_primary',
        'metadata',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'sort_order' => 'integer',
        'size'       => 'integer',
        'metadata'   => 'array',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(ProductModel::class, 'product_id');
    }
}
