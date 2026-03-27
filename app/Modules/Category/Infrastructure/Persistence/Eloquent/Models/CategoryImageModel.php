<?php

declare(strict_types=1);

namespace Modules\Category\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CategoryImageModel extends Model
{
    use SoftDeletes;

    protected $table = 'category_images';

    protected $fillable = [
        'tenant_id',
        'category_id',
        'uuid',
        'name',
        'file_path',
        'mime_type',
        'size',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'size'     => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(CategoryModel::class, 'category_id');
    }
}
