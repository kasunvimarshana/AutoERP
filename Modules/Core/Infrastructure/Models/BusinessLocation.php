<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BusinessLocation extends Model
{
    use SoftDeletes;

    protected $table = 'business_locations';

    protected $fillable = [
        'tenant_id',
        'name',
        'address',
        'city',
        'state',
        'country',
        'phone',
        'email',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
