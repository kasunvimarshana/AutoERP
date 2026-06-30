<?php

declare(strict_types=1);

namespace Modules\Purchase\Services;

use Modules\Purchase\DTOs\PurchaseHeaderAdjustmentData;
use Modules\Purchase\Enums\PurchaseAdjustmentType;
use Modules\Purchase\Models\PurchaseHeaderAdjustment;

final class PurchaseAdjustmentPolicyResolver
{
    private const CAPITALIZE = 'capitalize';
    private const REDUCE_COST = 'reduce_cost';
    private const EXPENSE = 'expense';
    private const TAX_ONLY = 'tax_only';
    private const DOCUMENT_PAYABLE = 'document_payable';
    private const UNSUPPORTED = 'unsupported';

    public function __construct(private readonly PurchaseAdjustmentCatalogueService $catalogue) {}

    /**
     * @return array{cost_treatment: string, tax_treatment: string, recognition_source: string, final_treatment: string, invoice_profile_key: string|null}
     */
    public function resolveForData(PurchaseHeaderAdjustmentData $data): array
    {
        $defaults = $this->catalogue->defaultsFor($data->adjustmentType);
        $costTreatment = (string) $defaults['cost_treatment'];
        $taxTreatment = (string) $defaults['tax_treatment'];
        $finalTreatment = $this->finalTreatment(
            $costTreatment,
            $taxTreatment,
            $data->adjustmentType,
            $data->isAllocatable,
        );

        return [
            'cost_treatment' => $costTreatment,
            'tax_treatment' => $taxTreatment,
            'recognition_source' => 'purchase_catalogue',
            'final_treatment' => $finalTreatment,
            'invoice_profile_key' => $this->invoiceProfileKey($finalTreatment),
        ];
    }

    /**
     * @return array{cost_treatment: string, tax_treatment: string, recognition_source: string, final_treatment: string, invoice_profile_key: string|null}
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
            'recognition_source' => 'purchase_snapshot',
            'final_treatment' => $finalTreatment,
            'invoice_profile_key' => $this->invoiceProfileKey($finalTreatment),
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

        return in_array($this->resolveForModel($adjustment)['final_treatment'], [self::CAPITALIZE, self::REDUCE_COST], true);
    }

    public function receiveOnlySupported(PurchaseHeaderAdjustmentData $data): bool
    {
        $policy = $this->resolveForData($data);
        if (! in_array($policy['final_treatment'], [self::CAPITALIZE, self::REDUCE_COST], true)) {
            return false;
        }

        return ! in_array($data->adjustmentType, [PurchaseAdjustmentType::Tax, PurchaseAdjustmentType::Withholding], true);
    }

    public function invoiceProfileKeyFor(PurchaseHeaderAdjustment $adjustment): ?string
    {
        return $this->resolveForModel($adjustment)['invoice_profile_key'];
    }

    private function finalTreatment(
        string $costTreatment,
        string $taxTreatment,
        PurchaseAdjustmentType $type,
        bool $isAllocatable,
    ): string {
        $cost = mb_strtolower(trim($costTreatment));
        $tax = mb_strtolower(trim($taxTreatment));

        if ($type === PurchaseAdjustmentType::Tax
            || in_array($tax, ['input_tax', 'recoverable_tax', 'non_recoverable_tax'], true)) {
            return self::TAX_ONLY;
        }
        if ($type === PurchaseAdjustmentType::Withholding || $cost === 'withholding' || $tax === 'withholding') {
            return self::DOCUMENT_PAYABLE;
        }

        return match ($cost) {
            'landed_cost' => $isAllocatable ? self::CAPITALIZE : self::UNSUPPORTED,
            'landed_cost_or_expense' => $isAllocatable ? self::CAPITALIZE : self::EXPENSE,
            'inventory_cost_reduction' => $isAllocatable ? self::REDUCE_COST : self::UNSUPPORTED,
            'expense', 'rounding', 'custom', 'other' => self::EXPENSE,
            'supplier_credit', 'supplier_debit', 'none' => self::DOCUMENT_PAYABLE,
            default => self::UNSUPPORTED,
        };
    }

    private function invoiceProfileKey(string $finalTreatment): ?string
    {
        return match ($finalTreatment) {
            self::CAPITALIZE,
            self::REDUCE_COST,
            self::EXPENSE,
            self::DOCUMENT_PAYABLE => 'expense',
            self::TAX_ONLY => 'tax_receivable',
            default => null,
        };
    }
}
