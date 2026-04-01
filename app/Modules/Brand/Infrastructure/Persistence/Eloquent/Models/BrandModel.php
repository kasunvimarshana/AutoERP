<?php

declare(strict_types=1);

namespace Modules\Brand\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasAudit;

class BrandModel extends Model
{
    use HasAudit, SoftDeletes;

    protected $table = 'brands';

    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'description',
        'website',
        'status',
        'attributes',
        'metadata',
    ];

    protected $casts = [
        'attributes' => 'array',
        'metadata'   => 'array',
    ];

    public function logo(): HasOne
    {
        return $this->hasOne(BrandLogoModel::class, 'brand_id');
    }
}
