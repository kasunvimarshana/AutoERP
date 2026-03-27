<?php

declare(strict_types=1);

namespace Modules\Category\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class CategoryModel extends Model
{
    use SoftDeletes;

    protected $table = 'categories';

    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'description',
        'parent_id',
        'depth',
        'path',
        'status',
        'attributes',
        'metadata',
    ];

    protected $casts = [
        'attributes' => 'array',
        'metadata'   => 'array',
        'depth'      => 'integer',
    ];

    public function image(): HasOne
    {
        return $this->hasOne(CategoryImageModel::class, 'category_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(CategoryModel::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(CategoryModel::class, 'parent_id');
    }

    public function allChildren(): HasMany
    {
        return $this->children()->with('allChildren');
    }
}
