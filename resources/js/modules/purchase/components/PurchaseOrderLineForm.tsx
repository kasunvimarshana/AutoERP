import { PurchaseLineForm } from './PurchaseLineForm';
import type {
    EditablePurchaseLine,
    PurchaseLineEditorConfig,
    PurchaseLineField,
} from './purchaseLineModel';

const purchaseOrderLineConfig: PurchaseLineEditorConfig = {
    unitLabel: 'Unit price',
    taxMode: 'manual',
    emptyMessage: 'No purchase lines added. Add the first item to this order.',
};

export function PurchaseOrderLineForm({ line, mode, supplierId, currencyId, warehouseId, errorFor, onSave, onCancel }: {
    line: EditablePurchaseLine;
    mode: 'create' | 'edit';
    supplierId?: number;
    currencyId?: number;
    warehouseId?: number;
    errorFor: (field: PurchaseLineField) => string | undefined;
    onSave: (line: EditablePurchaseLine) => void;
    onCancel: () => void;
}) {
    return (
        <PurchaseLineForm
            line={line}
            mode={mode}
            config={purchaseOrderLineConfig}
            supplierId={supplierId}
            currencyId={currencyId}
            warehouseId={warehouseId}
            errorFor={errorFor}
            onSave={onSave}
            onCancel={onCancel}
        />
    );
}
