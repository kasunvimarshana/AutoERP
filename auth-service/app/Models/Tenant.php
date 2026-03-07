<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Tenant model – represents an isolated SaaS customer.
 *
 * @property string      $id
 * @property string      $name
 * @property string      $slug
 * @property string      $plan
 * @property bool        $is_active
 * @property array|null  $settings
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class Tenant extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    /** @var array<string> */
    protected $fillable = [
        'name',
        'slug',
        'plan',
        'is_active',
        'settings',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'is_active' => 'boolean',
        'settings'  => 'array',
    ];

    /**
     * Users belonging to this tenant.
     *
     * @return HasMany<User>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
