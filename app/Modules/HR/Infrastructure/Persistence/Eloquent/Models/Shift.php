<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\HR\Infrastructure\Persistence\Eloquent\Concerns\HasTenantAndOrganizationScopes;

class Shift extends Model
{
    use HasTenantAndOrganizationScopes;
    use SoftDeletes;

    protected $table = 'shifts';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'row_version' => 'integer',
            'metadata' => 'array',
            'work_days' => 'array',
            'break_duration' => 'integer',
            'grace_minutes' => 'integer',
            'overtime_threshold' => 'integer',
            'is_night_shift' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo('Modules\\Tenant\\Infrastructure\\Persistence\\Eloquent\\Models\\Tenant', 'tenant_id');
    }

    public function organizationUnit(): BelongsTo
    {
        return $this->belongsTo('Modules\\OrganizationUnit\\Infrastructure\\Persistence\\Eloquent\\Models\\OrganizationUnit', 'organization_unit_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo('Modules\\User\\Infrastructure\\Persistence\\Eloquent\\Models\\User', 'created_by');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany('Modules\\HR\\Infrastructure\\Persistence\\Eloquent\\Models\\ShiftAssignment', 'shift_id');
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany('Modules\\HR\\Infrastructure\\Persistence\\Eloquent\\Models\\AttendanceRecord', 'shift_id');
    }
}
