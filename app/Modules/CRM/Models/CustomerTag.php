<?php

namespace App\Modules\CRM\Models;

use App\Core\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * CustomerTag Model
 * 
 * Represents a tag for customer segmentation
 */
class CustomerTag extends Model
{
    use HasFactory, TenantScoped;

    protected $fillable = [
        'name',
        'color',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $hidden = [];

    /**
     * Get customers with this tag
     */
    public function customers(): BelongsToMany
    {
        return $this->belongsToMany(Customer::class, 'customer_tag', 'tag_id', 'customer_id');
    }
}
