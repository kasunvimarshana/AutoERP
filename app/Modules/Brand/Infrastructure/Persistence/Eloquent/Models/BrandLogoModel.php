<?php

declare(strict_types=1);

namespace Modules\Brand\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasAudit;

class BrandLogoModel extends Model
{
    use HasAudit, SoftDeletes;

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
