<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Models;

use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class NumberingSequenceModel extends BaseModel
{
    use HasAudit;
    use HasTenant;

    protected $table = 'numbering_sequences';

    protected $fillable = [
        'tenant_id',
        'org_unit_id',
        'row_version',
        'module',
        'document_type',
        'prefix',
        'suffix',
        'next_number',
        'padding',
        'is_active',
    ];

    protected $casts = [
        'next_number' => 'integer',
        'padding' => 'integer',
        'is_active' => 'boolean',
        'row_version' => 'integer',
    ];
}
