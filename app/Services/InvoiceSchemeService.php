<?php

namespace App\Services;

use App\Models\InvoiceScheme;
use App\Models\ReferenceCount;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Manages configurable invoice-numbering schemes.
 *
 * Each scheme has a prefix, suffix, start number, and zero-padding width.
 * The `ReferenceCount` model (already in the repo) is used as the per-tenant
 * monotonic counter so reference numbers are unique within a tenant.
 */
class InvoiceSchemeService
{
    /**
     * Paginate invoice schemes for a tenant.
     */
    public function paginate(string $tenantId, int $perPage = 15): LengthAwarePaginator
    {
        return InvoiceScheme::where('tenant_id', $tenantId)
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Create a new invoice scheme.
     *
     * If `is_default` is true, all other schemes for the same tenant and
     * scheme_type are demoted.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): InvoiceScheme
    {
        return DB::transaction(function () use ($data) {
            if (! empty($data['is_default'])) {
                $this->clearDefaults($data['tenant_id'], $data['scheme_type'] ?? 'purchase_n_sell');
            }

            return InvoiceScheme::create($data);
        });
    }

    /**
     * Update an existing scheme.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(string $id, array $data): InvoiceScheme
    {
        return DB::transaction(function () use ($id, $data) {
            $scheme = InvoiceScheme::findOrFail($id);

            if (! empty($data['is_default'])) {
                $this->clearDefaults($scheme->tenant_id, $data['scheme_type'] ?? $scheme->scheme_type);
            }

            $scheme->update($data);

            return $scheme->fresh();
        });
    }

    /**
     * Delete a scheme (soft delete).
     */
    public function delete(string $id): void
    {
        InvoiceScheme::findOrFail($id)->delete();
    }

    /**
     * Set the default scheme for a tenant+type.
     */
    public function setDefault(string $tenantId, string $id): InvoiceScheme
    {
        return DB::transaction(function () use ($tenantId, $id) {
            $scheme = InvoiceScheme::where('tenant_id', $tenantId)->findOrFail($id);

            $this->clearDefaults($tenantId, $scheme->scheme_type);
            $scheme->update(['is_default' => true]);

            return $scheme->fresh();
        });
    }

    /**
     * Generate the next formatted reference number for a scheme.
     *
     * Uses the existing `ReferenceNumberService` with a scheme-specific
     * ref_type to generate a unique monotonic counter per scheme.
     */
    public function nextNumber(string $tenantId, string $schemeId): string
    {
        return DB::transaction(function () use ($tenantId, $schemeId) {
            $scheme = InvoiceScheme::where('tenant_id', $tenantId)->findOrFail($schemeId);

            // Use a unique ref_type per scheme so counters are isolated
            $refType = 'scheme_'.$schemeId;

            $ref = ReferenceCount::where('tenant_id', $tenantId)
                ->where('ref_type', $refType)
                ->whereNull('business_location_id')
                ->lockForUpdate()
                ->first();

            if (! $ref) {
                ReferenceCount::create([
                    'tenant_id' => $tenantId,
                    'ref_type' => $refType,
                    'business_location_id' => null,
                    'count' => $scheme->start_number,
                ]);

                return $scheme->format($scheme->start_number);
            }

            $next = $ref->count + 1;
            $ref->update(['count' => $next]);

            return $scheme->format($next);
        });
    }

    /** Demote all default schemes for a given tenant+type. */
    private function clearDefaults(string $tenantId, string $schemeType): void
    {
        InvoiceScheme::where('tenant_id', $tenantId)
            ->where('scheme_type', $schemeType)
            ->where('is_default', true)
            ->update(['is_default' => false]);
    }
}
