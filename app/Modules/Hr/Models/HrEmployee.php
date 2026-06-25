<?php

declare(strict_types=1);

namespace Modules\Hr\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Models\TenantOwnedModel;
use Modules\Hr\Enums\EmployeeAvailabilityStatus;
use Modules\Hr\Enums\EmployeeStatus;
use Modules\Hr\Enums\Gender;
use Modules\Hr\Models\Concerns\ScopesHrTenant;

final class HrEmployee extends TenantOwnedModel
{
    use ScopesHrTenant;
    use SoftDeletes;

    protected $table = 'hr_employees';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'department_id' => 'integer',
            'designation_id' => 'integer',
            'employment_type_id' => 'integer',
            'reporting_manager_id' => 'integer',
            'joined_date' => 'date',
            'resigned_date' => 'date',
            'date_of_birth' => 'date',
            'gender' => Gender::class,
            'status' => EmployeeStatus::class,
            'availability_status' => EmployeeAvailabilityStatus::class,
            'default_hourly_rate' => 'decimal:6',
            'default_daily_rate' => 'decimal:6',
            'default_service_rate' => 'decimal:6',
            'metadata' => 'array',
            'approved_by' => 'integer',
            'approved_at' => 'datetime',
        ]);
    }

    public function department(): BelongsTo { return $this->belongsTo(HrDepartment::class, 'department_id'); }
    public function designation(): BelongsTo { return $this->belongsTo(HrDesignation::class, 'designation_id'); }
    public function employmentType(): BelongsTo { return $this->belongsTo(HrEmploymentType::class, 'employment_type_id'); }
    public function reportingManager(): BelongsTo { return $this->belongsTo(self::class, 'reporting_manager_id'); }
    public function directReports(): HasMany { return $this->hasMany(self::class, 'reporting_manager_id'); }
    public function contacts(): HasMany { return $this->hasMany(HrEmployeeContact::class, 'employee_id'); }
    public function addresses(): HasMany { return $this->hasMany(HrEmployeeAddress::class, 'employee_id'); }
    public function documents(): HasMany { return $this->hasMany(HrEmployeeDocument::class, 'employee_id'); }
    public function skillAssignments(): HasMany { return $this->hasMany(HrEmployeeSkillAssignment::class, 'employee_id'); }
    public function certificationAssignments(): HasMany { return $this->hasMany(HrEmployeeCertificationAssignment::class, 'employee_id'); }
    public function licenseAssignments(): HasMany { return $this->hasMany(HrEmployeeLicenseAssignment::class, 'employee_id'); }
    public function skills(): BelongsToMany { return $this->belongsToMany(HrSkill::class, 'hr_employee_skill_assignments', 'employee_id', 'skill_id')->withPivot(['id', 'proficiency_level', 'years_of_experience', 'is_primary'])->withTimestamps(); }
    public function certifications(): BelongsToMany { return $this->belongsToMany(HrCertification::class, 'hr_employee_certification_assignments', 'employee_id', 'certification_id')->withPivot(['id', 'certificate_number', 'issued_date', 'expiry_date', 'status'])->withTimestamps(); }
    public function licenses(): BelongsToMany { return $this->belongsToMany(HrLicense::class, 'hr_employee_license_assignments', 'employee_id', 'license_id')->withPivot(['id', 'license_number', 'issued_date', 'expiry_date', 'status'])->withTimestamps(); }
    public function rates(): HasMany { return $this->hasMany(HrEmployeeRate::class, 'employee_id'); }
    public function availabilities(): HasMany { return $this->hasMany(HrEmployeeAvailability::class, 'employee_id'); }
    public function statusHistories(): HasMany { return $this->hasMany(HrEmployeeStatusHistory::class, 'employee_id'); }

    public function scopeActive(Builder $query): Builder { return $query->where('status', EmployeeStatus::Active); }
    public function scopeAvailable(Builder $query): Builder { return $query->active()->where('availability_status', EmployeeAvailabilityStatus::Available); }
}
