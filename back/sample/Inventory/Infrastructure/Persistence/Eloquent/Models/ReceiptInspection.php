<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Inventory\Domain\Enums\InspectionResult;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Concerns\HasTenantAndOrganization;

class ReceiptInspection extends Model
{
    use HasTenantAndOrganization;

    protected $table = 'receipt_inspections';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'row_version' => 'integer',
            'metadata' => 'array',
            'inspection_result' => InspectionResult::class,
            'inspected_at' => 'datetime',
        ];
    }
}
