import type { ItemLookupResource } from '@/shared/api/lookupApi';
import type { NamedResource } from '@/shared/types/common';
import { addDecimal, multiplyDecimal, percentageOfDecimal, subtractDecimal } from '@/shared/utils/decimal';
import type {
    VehicleServiceJobLine,
    VehicleServiceLinePayload,
    VehicleServiceLineSourceType,
} from '../../vehicleServiceTypes';

export type CalculationType = 'fixed' | 'percentage';

export interface VehicleServiceLineFormValue {
    source: VehicleServiceLineSourceType;
    item: ItemLookupResource | null;
    uom: NamedResource | null;
    description: string;
    quantity: string;
    unit_cost: string;
    unit_price: string;
    discount_type: CalculationType;
    discount_value: string;
    tax_type: CalculationType;
    tax_value: string;
    charge_type: CalculationType;
    charge_value: string;
    customer_supplied: boolean;
    billable: boolean;
    issueWarehouse: NamedResource | null;
    issueLocation: NamedResource | null;
}

export interface LineDialog {
    lineId: number;
    value: VehicleServiceLineFormValue;
}

export const calculationOptions = [
    { value: 'fixed', label: 'Fixed' },
    { value: 'percentage', label: 'Percentage' },
];

export const lineTypeOptions = [
    { value: 'inventory_item', label: 'Inventory item' },
    { value: 'external_item', label: 'External / customer supplied' },
    { value: 'service_item', label: 'Service item' },
    { value: 'labour_item', label: 'Labour item' },
    { value: 'combo_parent', label: 'Combo / package' },
];

export function emptyLineForm(): VehicleServiceLineFormValue {
    return {
        source: 'inventory_item',
        item: null,
        uom: null,
        description: '',
        quantity: '1.000000',
        unit_cost: '0.000000',
        unit_price: '0.000000',
        discount_type: 'fixed',
        discount_value: '0.000000',
        tax_type: 'fixed',
        tax_value: '0.000000',
        charge_type: 'fixed',
        charge_value: '0.000000',
        customer_supplied: false,
        billable: true,
        issueWarehouse: null,
        issueLocation: null,
    };
}

export function lineToForm(line: VehicleServiceJobLine): VehicleServiceLineFormValue {
    return {
        source: line.line_source_type,
        item: line.item ? {
            ...line.item,
            item_id: line.item_id ?? line.item.id,
            item_variant_id: line.item_variant_id,
            batch: line.batch ?? null,
            batch_price_revision_id: line.batch_price_revision_id,
            is_stockable: line.is_inventory_tracked,
        } : null,
        uom: line.uom ?? null,
        description: line.description,
        quantity: line.quantity,
        unit_cost: line.unit_cost,
        unit_price: line.unit_price,
        discount_type: line.discount_calculation_type ?? 'fixed',
        discount_value: line.discount_calculation_type === 'percentage'
            ? line.discount_rate
            : line.discount_amount,
        tax_type: line.tax_calculation_type ?? 'fixed',
        tax_value: line.tax_calculation_type === 'percentage'
            ? line.tax_rate
            : line.tax_amount,
        charge_type: line.charge_calculation_type ?? 'fixed',
        charge_value: line.charge_calculation_type === 'percentage'
            ? line.charge_rate
            : line.charge_amount,
        customer_supplied: line.is_customer_supplied,
        billable: line.is_billable,
        issueWarehouse: null,
        issueLocation: null,
    };
}

export function lineFormToPayload(form: VehicleServiceLineFormValue): VehicleServiceLinePayload {
    const discount = valueByType(form.discount_type, form.discount_value);
    const tax = valueByType(form.tax_type, form.tax_value);
    const charge = valueByType(form.charge_type, form.charge_value);
    const external = form.source === 'external_item';
    const description = form.description.trim() || form.item?.name || lineTypeLabel(form.source);

    return {
        line_source_type: form.source,
        item_id: external ? undefined : form.item?.item_id ?? form.item?.id,
        item_variant_id: external ? undefined : form.item?.item_variant_id ?? undefined,
        batch_id: external ? undefined : form.item?.batch?.id,
        batch_price_revision_id: external ? undefined : form.item?.batch_price_revision_id ?? undefined,
        uom_id: form.uom?.id,
        description,
        quantity: form.quantity,
        unit_cost: form.unit_cost,
        unit_price: form.unit_price,
        discount_calculation_type: form.discount_type,
        discount_rate: discount.rate,
        discount_amount: discount.amount,
        tax_calculation_type: form.tax_type,
        tax_rate: tax.rate,
        tax_amount: tax.amount,
        charge_calculation_type: form.charge_type,
        charge_rate: charge.rate,
        charge_amount: charge.amount,
        is_customer_supplied: external && form.customer_supplied,
        is_billable: form.billable,
        expand_combo: true,
    };
}

export function calculateLinePreview(line: VehicleServiceLineFormValue) {
    const subtotal = multiplyDecimal(line.quantity, line.unit_price);
    const discount = line.discount_type === 'percentage'
        ? percentageOfDecimal(subtotal, line.discount_value)
        : line.discount_value;
    const tax = line.tax_type === 'percentage'
        ? percentageOfDecimal(subtotal, line.tax_value)
        : line.tax_value;
    const charge = line.charge_type === 'percentage'
        ? percentageOfDecimal(subtotal, line.charge_value)
        : line.charge_value;

    return {
        subtotal,
        discount,
        tax,
        charge,
        total: addDecimal(addDecimal(subtractDecimal(subtotal, discount), tax), charge),
    };
}

export function formatLineItem(line: VehicleServiceJobLine): string {
    const itemLabel = line.item
        ? [line.item.code, line.item.name].filter(Boolean).join(' - ')
        : line.description || line.line_source_type.replaceAll('_', ' ');
    const batchLabel = line.batch
        ? `Batch ${line.batch.batch_number ?? line.batch.code}${line.batch.lot_number ? ` / Lot ${line.batch.lot_number}` : ''}`
        : null;

    return [itemLabel, batchLabel].filter(Boolean).join(' / ');
}

export function lineTypeLabel(source: VehicleServiceLineSourceType): string {
    return lineTypeOptions.find((option) => option.value === source)?.label ?? source.replaceAll('_', ' ');
}

function valueByType(type: CalculationType, value: string): { rate: string; amount: string } {
    return type === 'percentage'
        ? { rate: value, amount: '0.000000' }
        : { rate: '0.000000', amount: value };
}
