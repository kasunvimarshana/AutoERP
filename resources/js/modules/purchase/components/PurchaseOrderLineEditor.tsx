import { PurchaseLineEditor } from './PurchaseLineEditor';
import type {
    EditablePurchaseLine,
    PurchaseLineEditorConfig,
    PurchaseLineField,
} from './purchaseLineModel';

export type { EditablePurchaseLine } from './purchaseLineModel';
export { previewLineAmounts } from './purchaseLineModel';

const purchaseOrderLineConfig: PurchaseLineEditorConfig = {
    unitLabel: 'Unit price',
    taxMode: 'manual',
    emptyMessage: 'No purchase lines added. Add the first item to this order.',
};

const purchaseOrderFieldMap: Record<PurchaseLineField, string> = {
    item_id: 'item_id',
    item_variant_id: 'item_variant_id',
    uom_id: 'uom_id',
    description: 'description',
    quantity: 'ordered_quantity',
    unit_price: 'unit_price',
    discount_calculation_type: 'discount_calculation_type',
    discount_rate: 'discount_rate',
    discount_amount: 'discount_amount',
    tax_calculation_type: 'tax_calculation_type',
    tax_rate: 'tax_rate',
    tax_amount: 'tax_amount',
    tax_group_id: 'tax_group_id',
    charge_calculation_type: 'charge_calculation_type',
    charge_rate: 'charge_rate',
    charge_amount: 'charge_amount',
};

export function PurchaseOrderLineEditor({ lines, onChange, errorFor, supplierId, currencyId, warehouseId }: {
    lines: EditablePurchaseLine[];
    onChange: (lines: EditablePurchaseLine[]) => void;
    errorFor: (field: string) => string | undefined;
    supplierId?: number;
    currencyId?: number;
    warehouseId?: number;
}) {
    return (
        <PurchaseLineEditor
            lines={lines}
            onChange={onChange}
            config={purchaseOrderLineConfig}
            supplierId={supplierId}
            currencyId={currencyId}
            warehouseId={warehouseId}
            errorForLineField={(_, index, field) => errorFor(`lines.${index}.${purchaseOrderFieldMap[field]}`)}
        />
    );
}
