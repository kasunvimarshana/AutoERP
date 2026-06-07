<?php
declare(strict_types=1);
namespace Modules\Hr\Models;
use Illuminate\Database\Eloquent\Relations\HasMany;
final class HrDesignation extends HrMasterModel
{
    protected $table = 'hr_designations';
    public function employees(): HasMany { return $this->hasMany(HrEmployee::class, 'designation_id'); }
}
