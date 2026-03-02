<?php

declare(strict_types=1);

namespace Modules\Crm\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Traits\BelongsToTenant;

class ContactModel extends Model
{
    use BelongsToTenant;
    use SoftDeletes;

    protected $table = 'crm_contacts';

    protected $fillable = [
        'tenant_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'company',
        'job_title',
        'status',
        'notes',
    ];
}
