<?php

declare(strict_types=1);

namespace Modules\Invoice\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;
use Modules\Core\Models\TenantOwnedModel;
use Modules\Finance\Enums\FinancePostingProfileCode;
use Modules\Invoice\Enums\InvoicePostingPlanStatus;

final class InvoicePostingPlan extends TenantOwnedModel
{
    protected $table = 'invoice_posting_plans';

    protected $guarded = ['*'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'invoice_id' => 'integer',
            'posting_profile_code' => FinancePostingProfileCode::class,
            'posting_date' => 'date',
            'lines' => 'array',
            'status' => InvoicePostingPlanStatus::class,
            'posted_by' => 'integer',
            'posted_at' => 'immutable_datetime',
            'reversed_by' => 'integer',
            'reversed_at' => 'immutable_datetime',
            'row_version' => 'integer',
        ]);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    protected static function booted(): void
    {
        self::updating(function (self $plan): void {
            $semanticFields = [
                'tenant_id',
                'organization_unit_id',
                'invoice_id',
                'posting_profile_code',
                'posting_date',
                'lines',
            ];
            if (array_intersect(array_keys($plan->getDirty()), $semanticFields) !== []) {
                throw new LogicException('Invoice posting plan facts are immutable.');
            }

            $originalStatus = $plan->getOriginal('status');
            $status = $originalStatus instanceof InvoicePostingPlanStatus
                ? $originalStatus
                : InvoicePostingPlanStatus::from((string) $originalStatus);
            if ($status === InvoicePostingPlanStatus::Reversed) {
                throw new LogicException('Reversed invoice posting plans are immutable.');
            }

            if (! $plan->isDirty('row_version')) {
                $plan->row_version = ((int) $plan->getOriginal('row_version')) + 1;
            }
        });

        self::deleting(static function (): void {
            throw new LogicException('Invoice posting plans are financial evidence and cannot be deleted.');
        });
    }
}
