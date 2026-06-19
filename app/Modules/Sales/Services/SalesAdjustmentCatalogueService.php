<?php

declare(strict_types=1);

namespace Modules\Sales\Services;

use Modules\Sales\Enums\SalesAdjustmentCalculationBase;
use Modules\Sales\Enums\SalesAdjustmentCalculationType;
use Modules\Sales\Enums\SalesAdjustmentType;

final class SalesAdjustmentCatalogueService
{
    /**
     * @return list<array<string, mixed>>
     */
    public function catalogue(): array
    {
        return array_values(array_map(
            fn (SalesAdjustmentType $type): array => $this->entry($type),
            SalesAdjustmentType::cases(),
        ));
    }

    /**
     * @return array<string, mixed>
     */
    private function entry(SalesAdjustmentType $type): array
    {
        [$name, $effects, $defaultEffect, $revenueTreatment, $taxTreatment, $mappingLabel] = match ($type) {
            SalesAdjustmentType::Discount => ['Order Discount', ['decrease'], 'decrease', 'revenue_reduction', 'none', 'Sales discount / contra revenue'],
            SalesAdjustmentType::Tax => ['Sales Tax', ['increase'], 'increase', 'none', 'output_tax', 'Output tax payable'],
            SalesAdjustmentType::Freight => ['Freight', ['increase'], 'increase', 'revenue_or_recovery', 'none', 'Freight recovery'],
            SalesAdjustmentType::Charge => ['Sales Charge', ['increase'], 'increase', 'revenue_or_recovery', 'none', 'Sales charges'],
            SalesAdjustmentType::Insurance => ['Insurance', ['increase'], 'increase', 'revenue_or_recovery', 'none', 'Insurance recovery'],
            SalesAdjustmentType::ServiceCharge => ['Service Charge', ['increase'], 'increase', 'service_revenue', 'none', 'Service charge revenue'],
            SalesAdjustmentType::Duty => ['Duty Recovery', ['increase'], 'increase', 'revenue_or_recovery', 'none', 'Duty recovery'],
            SalesAdjustmentType::Levy => ['Levy', ['increase'], 'increase', 'revenue_or_recovery', 'none', 'Sales levy'],
            SalesAdjustmentType::Withholding => ['Withholding', ['decrease'], 'decrease', 'withholding', 'withholding', 'Withholding receivable'],
            SalesAdjustmentType::Rounding => ['Rounding', ['increase', 'decrease'], 'decrease', 'rounding', 'none', 'Rounding gain/loss'],
            SalesAdjustmentType::CreditNote => ['Customer Credit', ['decrease'], 'decrease', 'customer_credit', 'none', 'Customer credit / reduction'],
            SalesAdjustmentType::DebitNote => ['Customer Debit', ['increase'], 'increase', 'customer_debit', 'none', 'Customer debit'],
            SalesAdjustmentType::Custom => ['Custom Adjustment', ['increase', 'decrease'], 'increase', 'custom', 'custom', 'Custom finance mapping'],
        };

        return [
            'type' => $type->value,
            'default_name' => $name,
            'allowed_effects' => $effects,
            'default_effect' => $defaultEffect,
            'allowed_calculation_types' => [
                SalesAdjustmentCalculationType::Fixed->value,
                SalesAdjustmentCalculationType::Percentage->value,
            ],
            'default_calculation_type' => SalesAdjustmentCalculationType::Fixed->value,
            'allowed_calculation_bases' => array_map(
                static fn (SalesAdjustmentCalculationBase $base): string => $base->value,
                SalesAdjustmentCalculationBase::cases(),
            ),
            'default_calculation_base' => SalesAdjustmentCalculationBase::Subtotal->value,
            'revenue_treatment' => $revenueTreatment,
            'tax_treatment' => $taxTreatment,
            'finance_mapping_label' => $mappingLabel,
            'override_allowed' => in_array($type, [
                SalesAdjustmentType::Custom,
                SalesAdjustmentType::Rounding,
            ], true),
        ];
    }
}
