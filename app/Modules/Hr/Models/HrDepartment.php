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

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class HrDepartment extends HrMasterModel
{
    protected $table = 'hr_departments';

    protected function casts(): array
    {
        return array_merge(parent::casts(), ['sort_order' => 'integer']);
    }

    public function parent(): BelongsTo { return $this->belongsTo(self::class, 'parent_id'); }
    public function children(): HasMany { return $this->hasMany(self::class, 'parent_id'); }
    public function employees(): HasMany { return $this->hasMany(HrEmployee::class, 'department_id'); }
}
