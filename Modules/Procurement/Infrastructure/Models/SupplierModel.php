<?php

declare(strict_types=1);

namespace Modules\Procurement\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Traits\BelongsToTenant;

class SupplierModel extends Model
{
    use BelongsToTenant;
    use SoftDeletes;

    protected $table = 'suppliers';

    protected $fillable = [
        'tenant_id',
        'name',
        'contact_name',
        'email',
        'phone',
        'address',
        'status',
        'notes',
    ];
}
