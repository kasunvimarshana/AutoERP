<?php

declare(strict_types=1);

namespace Modules\Configuration\Infrastructure\Persistence\Eloquent\Models;

use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class LanguageModel extends BaseModel
{
    use HasAudit;

    protected $table = 'languages';

    protected $fillable = [
        'code',
        'name',
    ];
}
