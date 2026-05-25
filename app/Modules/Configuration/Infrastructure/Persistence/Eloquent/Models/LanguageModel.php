<?php

declare(strict_types=1);

namespace Modules\Configuration\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class LanguageModel extends CoreModel
{
    use SoftDeletes;

    protected $table = 'languages';

    protected $guarded = ['id'];
}
