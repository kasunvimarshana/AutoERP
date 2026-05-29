import { ModulePlaceholderPage } from '../../../shared/components/business/ModulePlaceholderPage';

export function InventoryPage() {
    return (
        <ModulePlaceholderPage
            description="Stock levels, movements, reservations, transfers, adjustments, batches, serials, valuation, and traceability. Backend owns availability, UOM conversion, reservations, cost layers, and reversals."
            sections={[
                { description: 'Current stock by item, warehouse, batch, and serial where applicable.', label: 'Stock Levels', path: '/inventory/stock-levels', status: 'Ready' },
                { description: 'Backend-owned stock movement ledger.', label: 'Movements', path: '/inventory/movements', status: 'Mocked' },
                { description: 'Reservation views created by source modules.', label: 'Reservations', path: '/inventory/reservations', status: 'Mocked' },
                { description: 'Transfers and warehouse movement workflow.', label: 'Transfers', path: '/inventory/transfers', status: 'Mocked' },
            ]}
            title="Inventory"
        />
    );
}
