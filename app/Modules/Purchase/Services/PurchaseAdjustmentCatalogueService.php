<?php

declare(strict_types=1);

namespace Modules\Purchase\Services;

use Illuminate\Validation\ValidationException;
use Modules\Purchase\DTOs\PurchaseHeaderAdjustmentData;
use Modules\Purchase\Enums\PurchaseAdjustmentCalculationBase;
use Modules\Purchase\Enums\PurchaseAdjustmentCalculationType;
use Modules\Purchase\Enums\PurchaseAdjustmentEffect;
use Modules\Purchase\Enums\PurchaseAdjustmentType;

final class PurchaseAdjustmentCatalogueService
{
    /**
     * @return list<array<string, mixed>>
     */
    public function catalogue(): array
    {
        return array_values(array_map(
            fn (PurchaseAdjustmentType $type): array => $this->entry($type),
            PurchaseAdjustmentType::cases(),
        ));
    }

    public function defaultsFor(PurchaseAdjustmentType $type): array
    {
        return $this->entry($type);
    }

    public function validate(
        PurchaseHeaderAdjustmentData $data,
        int $tenantId,
        ?int $organizationUnitId,
        string $fieldPrefix = 'adjustments',
    ): void {
        $entry = $this->entry($data->adjustmentType);
        $allowedEffects = $entry['allowed_effects'];

        if (! in_array($data->effect->value, $allowedEffects, true)) {
            throw ValidationException::withMessages([
                "{$fieldPrefix}.effect" => [
                    sprintf(
                        '%s adjustments cannot use the %s effect.',
                        (string) $entry['default_name'],
                        $data->effect->value,
                    ),
                ],
            ]);
        }

        if (! in_array($data->calculationType->value, $entry['allowed_calculation_types'], true)) {
            throw ValidationException::withMessages([
                "{$fieldPrefix}.calculation_type" => ['This calculation type is not valid for the selected adjustment type.'],
            ]);
        }

    }

    /**
     * @return array<string, mixed>
     */
    private function entry(PurchaseAdjustmentType $type): array
    {
        [$name, $effects, $defaultEffect, $costTreatment, $taxTreatment, $mappingLabel] = match ($type) {
            PurchaseAdjustmentType::Discount => ['Order Discount', ['decrease'], 'decrease', 'inventory_cost_reduction', 'none', 'Purchase discount / contra purchase'],
            PurchaseAdjustmentType::Tax => ['Purchase Tax', ['increase'], 'increase', 'none', 'input_tax', 'Input tax receivable'],
            PurchaseAdjustmentType::Freight => ['Freight', ['increase'], 'increase', 'landed_cost_or_expense', 'none', 'Freight-in / landed cost'],
            PurchaseAdjustmentType::Charge => ['Purchase Charge', ['increase'], 'increase', 'landed_cost_or_expense', 'none', 'Purchase charges'],
            PurchaseAdjustmentType::Insurance => ['Insurance', ['increase'], 'increase', 'landed_cost_or_expense', 'none', 'Purchase insurance'],
            PurchaseAdjustmentType::ServiceCharge => ['Service Charge', ['increase'], 'increase', 'expense', 'none', 'Service charge expense'],
            PurchaseAdjustmentType::Duty => ['Import Duty', ['increase'], 'increase', 'landed_cost', 'none', 'Import duty / landed cost'],
            PurchaseAdjustmentType::Levy => ['Levy', ['increase'], 'increase', 'landed_cost_or_expense', 'none', 'Purchase levy'],
            PurchaseAdjustmentType::CreditNote => ['Supplier Credit', ['decrease'], 'decrease', 'supplier_credit', 'none', 'Supplier credit / reduction'],
            PurchaseAdjustmentType::DebitNote => ['Supplier Debit', ['increase'], 'increase', 'supplier_debit', 'none', 'Supplier debit'],
            PurchaseAdjustmentType::Withholding => ['Withholding', ['decrease'], 'decrease', 'withholding', 'withholding', 'Withholding account'],
            PurchaseAdjustmentType::Rounding => ['Rounding', ['increase', 'decrease'], 'decrease', 'rounding', 'none', 'Rounding gain/loss'],
            PurchaseAdjustmentType::Custom => ['Custom Adjustment', ['increase', 'decrease'], 'increase', 'custom', 'custom', 'Custom finance mapping'],
            PurchaseAdjustmentType::Other => ['Other Adjustment', ['increase', 'decrease'], 'increase', 'other', 'other', 'Other purchase adjustment'],
        };

        return [
            'type' => $type->value,
            'default_name' => $name,
            'allowed_effects' => $effects,
            'default_effect' => $defaultEffect,
            'allowed_calculation_types' => [
                PurchaseAdjustmentCalculationType::Fixed->value,
                PurchaseAdjustmentCalculationType::Percentage->value,
            ],
            'default_calculation_type' => PurchaseAdjustmentCalculationType::Fixed->value,
            'allowed_calculation_bases' => array_map(
                static fn (PurchaseAdjustmentCalculationBase $base): string => $base->value,
                PurchaseAdjustmentCalculationBase::cases(),
            ),
            'default_calculation_base' => PurchaseAdjustmentCalculationBase::Subtotal->value,
            'cost_treatment' => $costTreatment,
            'tax_treatment' => $taxTreatment,
            'finance_mapping_label' => $mappingLabel,
            'override_allowed' => in_array($type, [
                PurchaseAdjustmentType::Custom,
                PurchaseAdjustmentType::Other,
                PurchaseAdjustmentType::Rounding,
            ], true),
        ];
    }
}
