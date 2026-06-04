<?php

declare(strict_types=1);

namespace Modules\Invoice\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class InvoiceTypeModel extends CoreModel
{
    use SoftDeletes;

    protected $table = 'invoice_types';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'row_version' => 'integer',
            'organization_unit_id' => 'integer',
            'schema_version' => 'integer',
            'document_type_id' => 'integer',
            'settings_json' => 'array',
            'is_active' => 'boolean',
        ]);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(InvoiceModel::class, 'invoice_type_id');
    }
}
