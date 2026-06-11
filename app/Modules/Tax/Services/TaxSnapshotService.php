<?php

declare(strict_types=1);

namespace Modules\Tax\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Tax\DTOs\TaxAmountData;
use Modules\Tax\DTOs\TaxCalculationResult;
use Modules\Tax\Models\TaxDocumentSnapshot;
use Modules\Tax\Models\TaxTransaction;

final class TaxSnapshotService
{
    public function __construct(private readonly DecimalMath $math) {}

    /**
     * @param  array<string, mixed>  $source
     * @return list<TaxDocumentSnapshot>
     */
    public function snapshotCalculation(TaxCalculationResult $calculation, array $source): array
    {
        $tenantId = (int) $source['tenant_id'];
        $organizationUnitId = isset($source['organization_unit_id']) && $source['organization_unit_id'] !== ''
            ? (int) $source['organization_unit_id']
            : null;
        $sourceType = (string) $source['source_type'];
        $sourceId = (int) $source['source_id'];

        return DB::transaction(function () use ($calculation, $source, $tenantId, $organizationUnitId, $sourceType, $sourceId): array {
            $existingPosted = TaxDocumentSnapshot::query()
                ->where('tenant_id', $tenantId)
                ->where('source_type', $sourceType)
                ->where('source_id', $sourceId)
                ->where('posted', true)
                ->exists();
            if ($existingPosted) {
                throw new InvalidArgumentException('Posted tax snapshots cannot be recalculated.');
            }

            TaxDocumentSnapshot::query()
                ->where('tenant_id', $tenantId)
                ->where('source_type', $sourceType)
                ->where('source_id', $sourceId)
                ->delete();

            $rows = [];
            foreach ($calculation->lineResults as $lineResult) {
                foreach ($lineResult->taxes as $tax) {
                    $rows[] = $this->createSnapshot($tax, $source, $tenantId, $organizationUnitId, [
                        'line_type' => $source['line_type'] ?? 'line',
                        'line_id' => $source['line_ids'][$lineResult->lineNumber] ?? null,
                        'line_number' => $lineResult->lineNumber,
                    ]);
                }
            }

            foreach ($calculation->headerTaxes as $tax) {
                $rows[] = $this->createSnapshot($tax, $source, $tenantId, $organizationUnitId, [
                    'line_type' => 'header',
                    'line_id' => null,
                    'line_number' => null,
                ]);
            }

            return $rows;
        });
    }

    public function markPosted(int $tenantId, string $sourceType, int $sourceId): void
    {
        TaxDocumentSnapshot::query()
            ->where('tenant_id', $tenantId)
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->update(['posted' => true]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function recordTransaction(TaxDocumentSnapshot $snapshot, array $attributes = []): TaxTransaction
    {
        $taxAmount = $this->math->normalize((string) $snapshot->tax_amount);

        return TaxTransaction::query()->create([
            'tenant_id' => $snapshot->tenant_id,
            'organization_unit_id' => $snapshot->organization_unit_id,
            'tax_id' => $snapshot->tax_id,
            'tax_document_snapshot_id' => $snapshot->getKey(),
            'transaction_date' => $attributes['transaction_date'] ?? $snapshot->source_date ?? now()->toDateString(),
            'source_module' => $snapshot->source_module,
            'source_type' => $snapshot->source_type,
            'source_id' => $snapshot->source_id,
            'source_number' => $snapshot->source_number,
            'party_type' => $attributes['party_type'] ?? null,
            'party_id' => $attributes['party_id'] ?? null,
            'tax_code' => $snapshot->tax_code,
            'tax_name' => $snapshot->tax_name,
            'tax_type' => $snapshot->tax_type,
            'taxable_amount' => $this->math->normalize((string) $snapshot->taxable_amount),
            'tax_amount' => $taxAmount,
            'withholding_amount' => (bool) $snapshot->is_withholding ? $taxAmount : '0.000000',
            'is_withholding' => (bool) $snapshot->is_withholding,
            'recoverable' => (bool) $snapshot->recoverable,
            'payable' => (bool) $snapshot->payable,
            'receivable' => (bool) $snapshot->receivable,
            'account_id' => $attributes['account_id'] ?? null,
            'metadata' => $attributes['metadata'] ?? null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $source
     * @param  array<string, mixed>  $line
     */
    private function createSnapshot(
        TaxAmountData $tax,
        array $source,
        int $tenantId,
        ?int $organizationUnitId,
        array $line,
    ): TaxDocumentSnapshot {
        return TaxDocumentSnapshot::query()->create([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'source_module' => $source['source_module'] ?? null,
            'source_type' => $source['source_type'],
            'source_id' => $source['source_id'],
            'source_number' => $source['source_number'] ?? null,
            'source_date' => $source['source_date'] ?? null,
            'line_type' => $line['line_type'] ?? null,
            'line_id' => $line['line_id'] ?? null,
            'tax_id' => $tax->taxId,
            'tax_code' => $tax->taxCode,
            'tax_name' => $tax->taxName,
            'tax_type' => $tax->taxType,
            'calculation_method' => $tax->calculationMethod,
            'rate' => $this->math->normalize($tax->rate),
            'sequence' => $tax->sequence,
            'taxable_amount' => $tax->taxableAmount,
            'tax_amount' => $tax->taxAmount,
            'total_amount' => $tax->totalAfterTax,
            'is_withholding' => $tax->isWithholding,
            'recoverable' => $tax->recoverable,
            'payable' => $tax->payable,
            'receivable' => $tax->receivable,
            'posted' => false,
            'metadata' => ['line_number' => $line['line_number'] ?? null],
        ]);
    }
}
