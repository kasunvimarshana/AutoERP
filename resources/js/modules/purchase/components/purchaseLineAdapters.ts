import type { NamedResource } from '@/shared/types/common';
import { decimalOr } from '../purchaseFormUtils';
import type {
    FastPurchasePayload,
    PurchaseOrder,
    PurchaseOrderPayload,
} from '../purchaseTypes';
import type { EditablePurchaseLine } from './purchaseLineModel';
import { purchaseLineKey } from './purchaseLineModel';

type PurchaseOrderPayloadLine = PurchaseOrderPayload['lines'][number];
type FastPurchasePayloadLine = FastPurchasePayload['lines'][number];

function resourceOrNull(resource: NamedResource | null | undefined): NamedResource | null {
    return resource?.id ? resource : null;
}

export function purchaseOrderLineFromResource(line: NonNullable<PurchaseOrder['lines']>[number]): EditablePurchaseLine {
    return {
        client_key: `purchase-order-line-${line.id ?? purchaseLineKey()}`,
        item: resourceOrNull(line.item),
        item_variant: resourceOrNull(line.item_variant),
        item_variant_id: line.item_variant_id ?? line.item_variant?.id ?? null,
        uom: resourceOrNull(line.uom),
        description: line.description ?? '',
        quantity: line.ordered_quantity,
        unit_price: line.unit_price,
        discount_calculation_type: line.discount_calculation_type ?? 'fixed',
        discount_rate: line.discount_rate ?? '0.000000',
        discount_amount: line.discount_amount,
        tax_calculation_type: line.tax_calculation_type ?? 'fixed',
        tax_rate: line.tax_rate ?? '0.000000',
        tax_amount: line.tax_amount,
        tax_group_id: '',
        charge_calculation_type: line.charge_calculation_type ?? 'fixed',
        charge_rate: line.charge_rate ?? '0.000000',
        charge_amount: line.charge_amount,
        auto_price: false,
        pricing_state: 'persisted',
        manual_price_confirmed: true,
        auto_uom: false,
    };
}

export function purchaseOrderLineToPayload(line: EditablePurchaseLine): PurchaseOrderPayloadLine {
    return {
        item_id: line.item?.id ?? 0,
        item_variant_id: line.item_variant_id ?? line.item_variant?.id ?? undefined,
        uom_id: line.uom?.id ?? 0,
        description: line.description || undefined,
        ordered_quantity: decimalOr(line.quantity),
        unit_price: decimalOr(line.unit_price),
        discount_calculation_type: line.discount_calculation_type,
        discount_rate: decimalOr(line.discount_rate),
        discount_amount: decimalOr(line.discount_amount),
        tax_calculation_type: line.tax_calculation_type,
        tax_rate: decimalOr(line.tax_rate),
        tax_amount: decimalOr(line.tax_amount),
        charge_calculation_type: line.charge_calculation_type,
        charge_rate: decimalOr(line.charge_rate),
        charge_amount: decimalOr(line.charge_amount),
    };
}

export function fastPurchaseLineToPayload(line: EditablePurchaseLine): FastPurchasePayloadLine {
    const pricingMode = line.auto_price === false && line.pricing_state !== 'persisted' ? 'manual' : 'auto';

    return {
        client_line_key: line.client_key,
        item_id: line.item?.id ?? 0,
        item_variant_id: line.item_variant_id ?? line.item_variant?.id ?? undefined,
        description: line.description || undefined,
        uom_id: line.uom?.id,
        quantity: decimalOr(line.quantity),
        unit_cost: pricingMode === 'manual' && line.unit_price ? decimalOr(line.unit_price) : undefined,
        pricing_mode: pricingMode,
        manual_price_confirmed: pricingMode === 'manual' ? Boolean(line.manual_price_confirmed) : undefined,
        pricing_context_hash: line.pricing_context_hash ?? undefined,
        discount_calculation_type: line.discount_calculation_type,
        discount_rate: decimalOr(line.discount_rate),
        discount_amount: decimalOr(line.discount_amount),
        tax_group_id: line.tax_group_id ? Number(line.tax_group_id) : undefined,
        charge_calculation_type: line.charge_calculation_type,
        charge_rate: decimalOr(line.charge_rate),
        charge_amount: decimalOr(line.charge_amount),
    };
}
