<?php

declare(strict_types=1);

namespace Modules\Configuration\Infrastructure\Persistence\Eloquent\Models;

use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class CountryModel extends BaseModel
{
    use HasAudit;

    protected $table = 'countries';

    protected $fillable = [
        'code',
        'name',
        'phone_code',
    ];
}
