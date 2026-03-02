<?php

declare(strict_types=1);

namespace Modules\Tenant\Infrastructure\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TenantModel extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'tenants';

    protected $fillable = [
        'name',
        'slug',
        'status',
        'domain',
        'plan_code',
        'currency',
        'settings',
    ];

    protected $casts = [
        'settings' => 'array',
    ];
}
