<?php

declare(strict_types=1);

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Base model with deny-by-default mass assignment and shared schema casts.
 *
 * Every concrete model must explicitly define its writable attributes.
 */
abstract class CoreModel extends Model
{
    protected $guarded = ['*'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'metadata' => 'array',
        ]);
    }
}
