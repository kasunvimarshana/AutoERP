<?php
declare(strict_types=1);
namespace Modules\Hr\Models;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
final class HrCertification extends HrMasterModel
{
    protected $table = 'hr_certifications';
    public function assignments(): HasMany { return $this->hasMany(HrEmployeeCertificationAssignment::class, 'certification_id'); }
    public function employees(): BelongsToMany { return $this->belongsToMany(HrEmployee::class, 'hr_employee_certification_assignments', 'certification_id', 'employee_id')->withPivot(['id', 'certificate_number', 'issued_date', 'expiry_date', 'status'])->withTimestamps(); }
}
