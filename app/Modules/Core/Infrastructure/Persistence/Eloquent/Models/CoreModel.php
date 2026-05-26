<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Core\Infrastructure\Persistence\Eloquent\Constants\SchemaColumns;

abstract class CoreModel extends Model
{
    protected $guarded = [SchemaColumns::ID];
}
