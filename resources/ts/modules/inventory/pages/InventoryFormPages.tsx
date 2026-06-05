import { PageHeader } from '../../../shared/components/business/PageHeader';
import { StockAdjustmentForm, StockTransferForm } from '../components/InventoryComponents';

export function StockTransferCreatePage() {
    return (
        <div className="space-y-6">
            <PageHeader eyebrow="Inventory" subtitle="Create a warehouse transfer with item, UOM, quantity, and tracking context." title="Create Stock Transfer" />
            <StockTransferForm />
        </div>
    );
}

export function StockAdjustmentCreatePage() {
    return (
        <div className="space-y-6">
            <PageHeader eyebrow="Inventory" subtitle="Create a stock adjustment with item, UOM, quantity, and tracking context." title="Create Stock Adjustment" />
            <StockAdjustmentForm />
        </div>
    );
}
