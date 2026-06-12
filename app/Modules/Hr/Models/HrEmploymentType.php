<?php
declare(strict_types=1);
namespace Modules\Hr\Models;
use Illuminate\Database\Eloquent\Relations\HasMany;
final class HrEmploymentType extends HrMasterModel
{
    protected $table = 'hr_employment_types';

    protected function casts(): array
    {
        return array_merge(parent::casts(), ['sort_order' => 'integer']);
    }

    public function employees(): HasMany { return $this->hasMany(HrEmployee::class, 'employment_type_id'); }
}
