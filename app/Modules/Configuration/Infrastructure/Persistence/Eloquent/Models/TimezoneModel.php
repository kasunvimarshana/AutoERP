<?php

declare(strict_types=1);

namespace Modules\Configuration\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;

class TimezoneModel extends BaseModel
{
    use HasAudit;

    protected $table = 'timezones';

    protected $fillable = [
        'name',
        'offset',
    ];
}
