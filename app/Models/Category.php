<?php

namespace App\Models;

/**
 * Category Model
 * 
 * Product categories for organization and filtering.
 *
 * @property int $id
 * @property int $tenant_id
 * @property string $name
 * @property string|null $description
 * @property int|null $parent_id
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class Category extends BaseModel
{
    /**
     * The table associated with the model
     */
    protected $table = 'categories';

    /**
     * The attributes that are mass assignable
     */
    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'parent_id',
    ];

    /**
     * The attributes that should be cast
     */
    protected $casts = [
        'tenant_id' => 'integer',
        'parent_id' => 'integer',
    ];

    /**
     * Get the parent category
     */
    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * Get the child categories
     */
    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    /**
     * Get the products in this category
     */
    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
