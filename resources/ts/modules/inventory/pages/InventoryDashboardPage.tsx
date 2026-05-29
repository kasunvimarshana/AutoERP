import { Link } from 'react-router-dom';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { Button } from '../../../shared/components/ui/Button';
import { InventoryDashboardCards, StockMovementTable, ValuationTable } from '../components/InventoryComponents';
import { inventoryDashboardMetrics, stockMovements, valuations } from '../mock/inventoryMock';

export function InventoryDashboardPage() {
    return (
        <div className="space-y-6">
            <PageHeader
                actions={<Link to="/inventory/availability-preview"><Button>Availability Preview</Button></Link>}
                eyebrow="Core Inventory"
                subtitle="Generic stock, movement, reservation, transfer, adjustment, valuation, and traceability workspace. Backend owns all stock math."
                title="Inventory"
            />
            <InventoryDashboardCards metrics={inventoryDashboardMetrics} />
            <div className="grid gap-4 md:grid-cols-5">
                {[
                    ['Stock levels', '/inventory/stock-levels'],
                    ['Create transfer', '/inventory/transfers/new'],
                    ['Create adjustment', '/inventory/adjustments/new'],
                    ['Cycle counts', '/inventory/cycle-counts'],
                    ['Traceability', '/inventory/traceability'],
                ].map(([label, path]) => <Link className="rounded-lg border border-slate-200 bg-white p-5 text-sm font-bold text-slate-900 shadow-sm hover:border-slate-300" key={label} to={path}>{label}</Link>)}
            </div>
            <section className="space-y-3">
                <h2 className="text-sm font-bold uppercase tracking-wide text-slate-500">Recent Movements</h2>
                <StockMovementTable rows={stockMovements.slice(0, 3)} />
            </section>
            <section className="space-y-3">
                <h2 className="text-sm font-bold uppercase tracking-wide text-slate-500">Valuation Summary</h2>
                <ValuationTable rows={valuations} />
            </section>
        </div>
    );
}

export { InventoryDashboardPage as InventoryPage };
