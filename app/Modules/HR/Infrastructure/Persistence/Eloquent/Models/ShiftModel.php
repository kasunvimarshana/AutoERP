<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class ShiftModel extends CoreModel
{
    use SoftDeletes;

    protected $table = 'shifts';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'row_version' => 'integer',
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'metadata' => 'array',
            'break_duration' => 'integer',
            'grace_minutes' => 'integer',
            'overtime_threshold' => 'integer',
            'work_days' => 'array',
            'is_night_shift' => 'boolean',
            'is_active' => 'boolean',
            'created_by' => 'integer',
        ]);
    }
}