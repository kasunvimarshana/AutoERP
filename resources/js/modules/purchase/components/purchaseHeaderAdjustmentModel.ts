export interface EditableHeaderAdjustment {
    name: string;
    adjustment_type: string;
    effect: 'increase' | 'decrease';
    calculation_type: 'fixed' | 'percentage';
    calculation_base: 'subtotal' | 'subtotal_after_line_discount' | 'subtotal_after_line_adjustments';
    rate: string;
    amount: string;
    allocation_method: string;
    allocations?: Array<{ client_line_key: string; amount: string }>;
    finance_mapping_label?: string;
    cost_treatment?: string;
    tax_treatment?: string;
    mapping_source?: 'catalogue' | 'override';
    override_reason?: string;
    description: string;
}

export function emptyHeaderAdjustment(): EditableHeaderAdjustment {
    return {
        name: '',
        adjustment_type: 'freight',
        effect: 'increase',
        calculation_type: 'fixed',
        calculation_base: 'subtotal',
        rate: '0.000000',
        amount: '0.000000',
        allocation_method: 'proportional',
        allocations: [],
        finance_mapping_label: 'Freight-in / landed cost',
        cost_treatment: 'landed_cost_or_expense',
        tax_treatment: 'none',
        mapping_source: 'catalogue',
        override_reason: '',
        description: '',
    };
}

export function formatAdjustmentEffect(adjustment: EditableHeaderAdjustment): string {
    return adjustment.effect === 'increase' ? 'Increase' : 'Decrease';
}

export function formatAdjustmentCalculation(adjustment: EditableHeaderAdjustment): string {
    if (adjustment.calculation_type === 'fixed') return 'Fixed';
    return `Percentage of ${adjustment.calculation_base.replaceAll('_', ' ')}`;
}

export function formatAdjustmentAmount(adjustment: EditableHeaderAdjustment): string {
    return adjustment.calculation_type === 'percentage' ? `${adjustment.rate}%` : adjustment.amount;
}

export function formatAdjustmentSummary(adjustment: EditableHeaderAdjustment): string {
    return `${formatAdjustmentEffect(adjustment)} ${formatAdjustmentAmount(adjustment)} as ${adjustment.adjustment_type.replaceAll('_', ' ')} using ${adjustment.allocation_method.replaceAll('_', ' ')} allocation.`;
}
