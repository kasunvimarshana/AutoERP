import { PageHeader } from '../../../shared/components/business/PageHeader';
import { StockAdjustmentForm, StockTransferForm } from '../components/InventoryComponents';

export function StockTransferCreatePage() {
    return (
        <div className="space-y-6">
            <PageHeader eyebrow="Inventory" subtitle="Create transfer request inputs. Backend validates availability and posts movement effects." title="Create Stock Transfer" />
            <StockTransferForm />
        </div>
    );
}

export function StockAdjustmentCreatePage() {
    return (
        <div className="space-y-6">
            <PageHeader eyebrow="Inventory" subtitle="Create adjustment request inputs. Backend validates quantity impact and valuation." title="Create Stock Adjustment" />
            <StockAdjustmentForm />
        </div>
    );
}
