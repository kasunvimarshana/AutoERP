<?php
declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

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
