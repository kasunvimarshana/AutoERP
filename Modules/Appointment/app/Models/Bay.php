<?php

declare(strict_types=1);

namespace Modules\Appointment\Models;

use App\Core\Traits\AuditTrait;
use App\Core\Traits\TenantAware;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Organization\Models\Branch;

/**
 * Bay Model
 *
 * Represents a service bay in a branch
 *
 * @property int $id
 * @property int $branch_id
 * @property string $bay_number
 * @property string $bay_type
 * @property string $status
 * @property int $capacity
 * @property string|null $notes
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class Bay extends Model
{
    use AuditTrait;
    use HasFactory;
    use SoftDeletes;
    use TenantAware;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): \Modules\Appointment\Database\Factories\BayFactory
    {
        return \Modules\Appointment\Database\Factories\BayFactory::new();
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'branch_id',
        'bay_number',
        'bay_type',
        'status',
        'capacity',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Get the branch that owns the bay.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the schedules for the bay.
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(BaySchedule::class);
    }

    /**
     * Scope to filter available bays.
     */
    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }

    /**
     * Scope to filter by branch.
     */
    public function scopeForBranch($query, int $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    /**
     * Scope to filter by bay type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('bay_type', $type);
    }
}
