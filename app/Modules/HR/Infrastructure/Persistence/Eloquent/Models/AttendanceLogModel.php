<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Models;


use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class AttendanceLogModel extends CoreModel
{


    protected $table = 'attendance_logs';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'row_version' => 'integer',
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'metadata' => 'array',
            'employee_id' => 'integer',
            'biometric_device_id' => 'integer',
            'punch_time' => 'datetime',
            'raw_data' => 'array',
            'processed_at' => 'datetime',
        ]);
    }
}