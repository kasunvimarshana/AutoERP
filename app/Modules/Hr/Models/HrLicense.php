<?php
declare(strict_types=1);
namespace Modules\Hr\Models;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
final class HrLicense extends HrMasterModel
{
    protected $table = 'hr_licenses';
    public function assignments(): HasMany { return $this->hasMany(HrEmployeeLicenseAssignment::class, 'license_id'); }
    public function employees(): BelongsToMany { return $this->belongsToMany(HrEmployee::class, 'hr_employee_license_assignments', 'license_id', 'employee_id')->withPivot(['id', 'license_number', 'issued_date', 'expiry_date', 'status'])->withTimestamps(); }
}
