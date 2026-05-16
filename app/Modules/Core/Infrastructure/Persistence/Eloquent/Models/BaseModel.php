<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

abstract class BaseModel extends Model
{
    use SoftDeletes;

    // protected $connection = 'tenant';

    protected $casts = [
        'id' => 'int',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];

    /**
     * Child classes should override $fillable with their explicit column list.
     * Keeping $guarded here as a safety net for the abstract base; concrete
     * Eloquent models that extend this class should define $fillable instead.
     */
}
