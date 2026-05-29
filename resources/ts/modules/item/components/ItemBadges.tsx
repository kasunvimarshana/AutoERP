import { StatusBadge } from '../../../shared/components/business/StatusBadge';
import type { Item, ItemType } from '../types/item.types';

const typeLabels: Record<ItemType, string> = {
    combo: 'Combo / Bundle',
    customer_supplied: 'Customer Supplied',
    external_service: 'External Service',
    fee_adjustment: 'Fee / Adjustment',
    inventory_product: 'Inventory Product',
    labour: 'Labour',
    non_inventory: 'Non-Inventory',
    rental_charge: 'Rental Charge',
    service: 'Service',
};

const stockLabels: Record<Item['stockBehavior'], string> = {
    no_stock_impact: 'No Stock Impact',
    reference_only: 'Reference Only',
    stock_tracked: 'Stock Tracked',
};

export function ItemTypeBadge({ type }: { type: ItemType }) {
    return <StatusBadge status={typeLabels[type] ?? type} />;
}

export function ItemStockBehaviorBadge({ behavior }: { behavior: Item['stockBehavior'] }) {
    return <StatusBadge status={stockLabels[behavior] ?? behavior} />;
}

export function itemTypeLabel(type: ItemType) {
    return typeLabels[type] ?? type;
}

export function stockBehaviorLabel(behavior: Item['stockBehavior']) {
    return stockLabels[behavior] ?? behavior;
}
