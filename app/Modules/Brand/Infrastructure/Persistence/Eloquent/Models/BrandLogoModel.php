<?php

declare(strict_types=1);

namespace Modules\Brand\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BrandLogoModel extends Model
{
    use SoftDeletes;

    protected $table = 'brand_logos';

    protected $fillable = [
        'tenant_id',
        'brand_id',
        'uuid',
        'name',
        'file_path',
        'mime_type',
        'size',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function brand(): BelongsTo
    {
        return $this->belongsTo(BrandModel::class, 'brand_id');
    }
}
