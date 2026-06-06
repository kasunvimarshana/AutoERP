<?php

declare(strict_types=1);

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Core\Constants\SchemaColumns;

abstract class CoreModel extends Model
{
    protected $guarded = [SchemaColumns::ID];
}
