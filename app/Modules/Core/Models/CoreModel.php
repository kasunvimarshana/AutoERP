<?php

declare(strict_types=1);

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Base model with deny-by-default mass assignment.
 *
 * Every concrete model must explicitly define its writable attributes.
 */
abstract class CoreModel extends Model
{
    protected $guarded = ['*'];
}
