<?php
declare(strict_types=1);
namespace Modules\Hr\Models;
use Illuminate\Database\Eloquent\Relations\HasMany;
final class HrEmploymentType extends HrMasterModel
{
    protected $table = 'hr_employment_types';
    public function employees(): HasMany { return $this->hasMany(HrEmployee::class, 'employment_type_id'); }
}
