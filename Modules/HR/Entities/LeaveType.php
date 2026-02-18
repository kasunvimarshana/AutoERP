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
use Modules\HR\Database\Factories\LeaveTypeFactory;

/**
 * LeaveType Entity
 *
 * Represents a type of leave (sick, vacation, personal, etc.).
 * Defines leave policies and allowances.
 *
 * @property int $id
 * @property int $tenant_id
 * @property string $name
 * @property string $code
 * @property string|null $description
 * @property int $max_days_per_year
 * @property bool $is_paid
 * @property bool $requires_approval
 * @property bool $is_carry_forward
 * @property int|null $max_carry_forward_days
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class LeaveType extends Model
{
    use Auditable, HasFactory, SoftDeletes, Tenantable, Translatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'tenant_id',
        'name',
        'code',
        'description',
        'max_days_per_year',
        'is_paid',
        'requires_approval',
        'is_carry_forward',
        'max_carry_forward_days',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'max_days_per_year' => 'integer',
        'is_paid' => 'boolean',
        'requires_approval' => 'boolean',
        'is_carry_forward' => 'boolean',
        'max_carry_forward_days' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * The attributes that are translatable.
     *
     * @var array<string>
     */
    protected $translatable = ['name', 'description'];

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): LeaveTypeFactory
    {
        return LeaveTypeFactory::new();
    }

    /**
     * Get leaves of this type.
     */
    public function leaves(): HasMany
    {
        return $this->hasMany(Leave::class);
    }
}
