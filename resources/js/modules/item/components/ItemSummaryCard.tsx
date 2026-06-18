import { DetailGrid } from '@/shared/components/DetailGrid';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { readableRelation } from '@/shared/utils/object';
import type { Item } from '../itemTypes';

export function ItemSummaryCard({ item }: { item: Item }) {
    return <DetailGrid items={[
        { label: 'Status', value: <StatusBadge status={item.is_active ? 'active' : 'inactive'} /> },
        { label: 'Type', value: item.item_type },
        { label: 'Tracking', value: item.tracking_type },
        { label: 'Costing', value: item.costing_method },
        { label: 'Category', value: readableRelation(item.category) },
        { label: 'Brand', value: readableRelation(item.brand) },
        { label: 'Base UOM', value: readableRelation(item.base_uom) },
        { label: 'Standard Price', value: item.standard_price ?? 'Not configured' },
        { label: 'Standard Currency', value: readableRelation(item.tenant_base_currency) },
        { label: 'SKU', value: item.sku },
        { label: 'Barcode', value: item.barcode },
        { label: 'Stockable', value: item.is_stockable ? 'Yes' : 'No' },
        { label: 'Combo/package', value: item.is_combo ? 'Yes' : 'No' },
        { label: 'Description', value: item.description },
    ]} />;
}
