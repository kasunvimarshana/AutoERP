<?php

declare(strict_types=1);

namespace Modules\Configuration\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class TimezoneModel extends CoreModel
{
    use SoftDeletes;

    protected $table = 'timezones';

    protected $guarded = ['id'];
}
