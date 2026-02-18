<?php

declare(strict_types=1);

namespace Modules\HR\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Traits\Auditable;
use Modules\Core\Traits\Tenantable;
use Modules\Core\Traits\Translatable;
use Modules\HR\Database\Factories\PositionFactory;

/**
 * Position Entity
 *
 * Represents a job position/role in the organization.
 * Defines salary ranges and responsibilities.
 *
 * @property int $id
 * @property int $tenant_id
 * @property string $title
 * @property string $code
 * @property string|null $description
 * @property string|null $grade
 * @property float|null $min_salary
 * @property float|null $max_salary
 * @property string|null $responsibilities
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class Position extends Model
{
    use Auditable, HasFactory, SoftDeletes, Tenantable, Translatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'tenant_id',
        'title',
        'code',
        'description',
        'grade',
        'min_salary',
        'max_salary',
        'responsibilities',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'min_salary' => 'decimal:2',
        'max_salary' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * The attributes that are translatable.
     *
     * @var array<string>
     */
    protected $translatable = ['title', 'description', 'responsibilities'];

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): PositionFactory
    {
        return PositionFactory::new();
    }

    /**
     * Get employees in this position.
     */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }
}
