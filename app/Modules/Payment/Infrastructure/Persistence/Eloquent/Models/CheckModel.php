<?php

declare(strict_types=1);

namespace Modules\Payment\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class CheckModel extends CoreModel
{
    use SoftDeletes;

    protected $table = 'checks';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'row_version' => 'integer',
            'organization_unit_id' => 'integer',
            'metadata' => 'array',
            'party_id' => 'integer',
            'bank_account_id' => 'integer',
            'check_date' => 'date',
            'due_date' => 'date',
            'amount' => 'decimal:4',
            'clearance_date' => 'date',
            'created_by' => 'integer'
        ]);
    }
}