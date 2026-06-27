<?php

declare(strict_types=1);

namespace Modules\Purchase\Services;

use Illuminate\Validation\ValidationException;
use Modules\Purchase\DTOs\PurchaseHeaderAdjustmentData;
use Modules\Purchase\Enums\PurchaseAdjustmentType;
use Modules\Purchase\Models\PurchaseHeaderAdjustment;

final class PurchaseAdjustmentPolicyResolver
{
    public function __construct(
        private readonly PurchaseAdjustmentCatalogueService $catalogue,
        private readonly PurchaseAuthorizationService $authorization,
    ) {}

    /**
     * @return array{cost_treatment: string, tax_treatment: string, mapping_source: string, final_treatment: string, profile_key: string|null}
     */
    public function resolveForData(
        PurchaseHeaderAdjustmentData $data,
        int $tenantId,
        ?int $organizationUnitId,
        string $fieldPrefix,
        ?int $userId = null,
        bool $lockReferences = false,
    ): array {
        unset($organizationUnitId, $lockReferences);

        $defaults = $this->catalogue->defaultsFor($data->adjustmentType);
        $overrideRequested = $this->overrideRequested($data, $defaults);
        if ($overrideRequested) {
            $this->assertOverrideAllowed($data->adjustmentType, $defaults, $tenantId, $userId, $fieldPrefix);
        }

        $costTreatment = $overrideRequested
            ? (string) ($data->costTreatment ?? $defaults['cost_treatment'])
            : (string) $defaults['cost_treatment'];
        $taxTreatment = $overrideRequested
            ? (string) ($data->taxTreatment ?? $defaults['tax_treatment'])
            : (string) $defaults['tax_treatment'];
        $finalTreatment = $this->finalTreatment(
            $costTreatment,
            $taxTreatment,
            $data->adjustmentType,
            $data->isAllocatable,
        );

        return [
            'cost_treatment' => $costTreatment,
            'tax_treatment' => $taxTreatment,
            'mapping_source' => $overrideRequested ? 'override' : 'catalogue',
            'final_treatment' => $finalTreatment,
            'profile_key' => $this->profileKeyForTreatment($finalTreatment, $taxTreatment),
        ];
    }

    /**
     * @return array{cost_treatment: string, tax_treatment: string, mapping_source: string, final_treatment: string, profile_key: string|null}
     */
    public function resolveForModel(PurchaseHeaderAdjustment $adjustment): array
    {
        $costTreatment = trim((string) $adjustment->cost_treatment);
        $taxTreatment = trim((string) $adjustment->tax_treatment);
        $type = $adjustment->adjustment_type instanceof PurchaseAdjustmentType
            ? $adjustment->adjustment_type
            : PurchaseAdjustmentType::from((string) $adjustment->adjustment_type);
        $finalTreatment = $this->finalTreatment(
            $costTreatment,
            $taxTreatment,
            $type,
            (bool) $adjustment->is_allocatable,
        );

        return [
            'cost_treatment' => $costTreatment,
            'tax_treatment' => $taxTreatment,
            'mapping_source' => (string) $adjustment->mapping_source,
            'final_treatment' => $finalTreatment,
            'profile_key' => $this->profileKeyForTreatment($finalTreatment, $taxTreatment),
        ];
    }

    public function recognizesAtGoodsReceipt(PurchaseHeaderAdjustment $adjustment): bool
    {
        if (! (bool) $adjustment->is_allocatable) {
            return false;
        }

        $type = $adjustment->adjustment_type instanceof PurchaseAdjustmentType
            ? $adjustment->adjustment_type
            : PurchaseAdjustmentType::from((string) $adjustment->adjustment_type);
        if (in_array($type, [PurchaseAdjustmentType::Tax, PurchaseAdjustmentType::Withholding], true)) {
            return false;
        }

        return in_array($this->resolveForModel($adjustment)['final_treatment'], ['capitalize', 'reduce_cost'], true);
    }

    public function receiveOnlySupported(PurchaseHeaderAdjustmentData $data): bool
    {
        $defaults = $this->catalogue->defaultsFor($data->adjustmentType);
        $costTreatment = (string) ($data->costTreatment ?? $defaults['cost_treatment']);
        $taxTreatment = (string) ($data->taxTreatment ?? $defaults['tax_treatment']);
        $treatment = $this->finalTreatment($costTreatment, $taxTreatment, $data->adjustmentType, $data->isAllocatable);

        return in_array($treatment, ['capitalize', 'reduce_cost'], true)
            && ! in_array($data->adjustmentType, [PurchaseAdjustmentType::Tax, PurchaseAdjustmentType::Withholding], true);
    }

    private function overrideRequested(PurchaseHeaderAdjustmentData $data, array $defaults): bool
    {
        if (($data->mappingSource ?? 'catalogue') === 'override') {
            return true;
        }
        if ($data->costTreatment !== null && $data->costTreatment !== (string) $defaults['cost_treatment']) {
            return true;
        }

        return $data->taxTreatment !== null && $data->taxTreatment !== (string) $defaults['tax_treatment'];
    }

    private function assertOverrideAllowed(
        PurchaseAdjustmentType $type,
        array $defaults,
        int $tenantId,
        ?int $userId,
        string $fieldPrefix,
    ): void {
        if (! (bool) ($defaults['override_allowed'] ?? false)) {
            throw ValidationException::withMessages([
                "{$fieldPrefix}.mapping_source" => ['Accounting treatment for this adjustment type is controlled by the purchase catalogue.'],
            ]);
        }
        if ($userId === null || ! $this->authorization->can(
            $userId,
            $tenantId,
            PurchaseAuthorizationService::ADJUSTMENT_ACCOUNTING_OVERRIDE,
        )) {
            throw ValidationException::withMessages([
                "{$fieldPrefix}.mapping_source" => ['You are not allowed to override purchase adjustment treatment.'],
            ]);
        }
    }

    private function finalTreatment(
        string $costTreatment,
        string $taxTreatment,
        PurchaseAdjustmentType $type,
        bool $isAllocatable,
    ): string {
        $cost = mb_strtolower(trim($costTreatment));
        $tax = mb_strtolower(trim($taxTreatment));

        if ($type === PurchaseAdjustmentType::Tax || in_array($tax, ['input_tax', 'recoverable_tax', 'non_recoverable_tax'], true)) {
            return 'tax_only';
        }
        if ($type === PurchaseAdjustmentType::Withholding || $cost === 'withholding' || $tax === 'withholding') {
            return 'document_payable';
        }

        return match ($cost) {
            'landed_cost' => $isAllocatable ? 'capitalize' : 'unsupported',
            'landed_cost_or_expense' => $isAllocatable ? 'capitalize' : 'expense',
            'inventory_cost_reduction' => $isAllocatable ? 'reduce_cost' : 'unsupported',
            'expense', 'rounding', 'custom', 'other' => 'expense',
            'supplier_credit', 'supplier_debit' => 'document_payable',
            'none' => $tax === 'none' ? 'document_payable' : 'tax_only',
            default => 'unsupported',
        };
    }

    private function profileKeyForTreatment(string $finalTreatment, string $taxTreatment): ?string
    {
        return match ($finalTreatment) {
            'capitalize', 'reduce_cost' => 'inventory',
            'tax_only' => in_array(mb_strtolower(trim($taxTreatment)), ['input_tax', 'recoverable_tax', 'non_recoverable_tax'], true)
                ? 'tax_receivable'
                : 'payable',
            'document_payable' => 'payable',
            'expense' => 'adjustment',
            default => null,
        };
    }
}
