<?php

declare(strict_types=1);

namespace Modules\Invoice\Services;

use InvalidArgumentException;
use Modules\Invoice\DTOs\CreateInvoiceData;
use Modules\Invoice\DTOs\InvoiceSourceLineData;
use Modules\Invoice\Models\InvoiceSourceAllocationGuard;

final class InvoiceSourceAllocationGuardService
{
    /**
     * Lock one stable database row for every source line before remaining quantities
     * are read. This also serializes the first allocation, when no invoice allocation
     * row exists yet and therefore there would otherwise be nothing to lock.
     */
    public function lock(CreateInvoiceData $data): void
    {
        $guards = [];

        foreach ($data->sourceLines as $sourceLine) {
            if (! $sourceLine instanceof InvoiceSourceLineData) {
                throw new InvalidArgumentException('Invoice source lines must be InvoiceSourceLineData instances.');
            }
            if ($sourceLine->tenantId !== $data->tenantId
                || $sourceLine->organizationUnitId !== $data->organizationUnitId) {
                throw new InvalidArgumentException('Invoice source line scope does not match the invoice scope.');
            }

            $attributes = [
                'tenant_id' => $data->tenantId,
                'organization_unit_id' => $data->organizationUnitId,
                'source_type' => trim($sourceLine->sourceType),
                'source_id' => $sourceLine->sourceId,
                'source_line_type' => trim($sourceLine->sourceLineType),
                'source_line_id' => $sourceLine->sourceLineId,
            ];
            if ($attributes['source_type'] === '' || $attributes['source_line_type'] === '') {
                throw new InvalidArgumentException('Invoice source type and source line type are required.');
            }

            $allocationKey = hash('sha256', json_encode($attributes, JSON_THROW_ON_ERROR));
            $guards[$allocationKey] = [...$attributes, 'allocation_key' => $allocationKey];
        }

        ksort($guards);

        foreach ($guards as $attributes) {
            InvoiceSourceAllocationGuard::query()->insertOrIgnore([
                ...$attributes,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $guard = InvoiceSourceAllocationGuard::query()
                ->where('allocation_key', $attributes['allocation_key'])
                ->lockForUpdate()
                ->firstOrFail();

            foreach (['tenant_id', 'organization_unit_id', 'source_type', 'source_id', 'source_line_type', 'source_line_id'] as $column) {
                if ($guard->{$column} != $attributes[$column]) {
                    throw new InvalidArgumentException('Invoice source allocation guard identity is inconsistent.');
                }
            }
        }
    }
}
