<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Payment\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Models\TenantOwnedModel;
use Modules\OrganizationUnit\Models\OrganizationUnitModel;
use Modules\Tenant\Models\TenantModel;

final class ChequeTemplate extends TenantOwnedModel
{
    use SoftDeletes;

    protected $table = 'cheque_templates';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'row_version' => 'integer',
            'page_width_mm' => 'decimal:3',
            'page_height_mm' => 'decimal:3',
            'date_x_mm' => 'decimal:3',
            'date_y_mm' => 'decimal:3',
            'payee_x_mm' => 'decimal:3',
            'payee_y_mm' => 'decimal:3',
            'amount_x_mm' => 'decimal:3',
            'amount_y_mm' => 'decimal:3',
            'amount_words_x_mm' => 'decimal:3',
            'amount_words_y_mm' => 'decimal:3',
            'cheque_number_x_mm' => 'decimal:3',
            'cheque_number_y_mm' => 'decimal:3',
            'font_size' => 'decimal:2',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'metadata' => 'array',
        ]);
    }

    protected static function booted(): void
    {
        self::deleting(static function (ChequeTemplate $template): void {
            if ($template->is_default) {
                throw new \LogicException('The default cheque template cannot be deleted.');
            }
            if ($template->printLogs()->exists()) {
                throw new \LogicException('Cheque templates with print history cannot be deleted. Deactivate the template instead.');
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(TenantModel::class, 'tenant_id');
    }

    public function organizationUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationUnitModel::class, 'organization_unit_id');
    }

    public function printLogs(): HasMany
    {
        return $this->hasMany(ChequePrintLog::class, 'cheque_template_id');
    }

    public function canDelete(): bool
    {
        return ! $this->is_default && ! $this->printLogs()->exists();
    }
}
