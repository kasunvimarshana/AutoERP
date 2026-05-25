<?php

declare(strict_types=1);

namespace Modules\Configuration\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class CountryModel extends CoreModel
{
    use SoftDeletes;

    protected $table = 'countries';

    protected $guarded = ['id'];
}
