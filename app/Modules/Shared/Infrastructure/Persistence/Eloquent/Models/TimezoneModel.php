<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;

class TimezoneModel extends Model
{
    use HasAudit;

    protected $table = 'timezones';

    protected $fillable = [
        'name',
        'offset',
    ];
}
