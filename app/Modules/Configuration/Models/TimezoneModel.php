<?php

declare(strict_types=1);

namespace Modules\Configuration\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Models\CoreModel;

final class TimezoneModel extends CoreModel
{
    use SoftDeletes;

    protected $table = 'timezones';

    protected $guarded = ['id'];
}
