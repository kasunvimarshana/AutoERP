<?php

declare(strict_types=1);

namespace Modules\Configuration\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Models\CoreModel;

final class CountryModel extends CoreModel
{
    use SoftDeletes;

    protected $table = 'countries';

    protected $guarded = ['id'];
}
