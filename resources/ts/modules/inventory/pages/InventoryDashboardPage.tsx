import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { Button } from '../../../shared/components/ui/Button';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import { InventoryDashboardCards, StockMovementTable, ValuationTable } from '../components/InventoryComponents';
import { inventoryApi } from '../services/inventoryApi';
import type { InventoryValuation, StockMovement } from '../types/inventory.types';

export function InventoryDashboardPage() {
    const [metrics, setMetrics] = useState<Array<{ label: string; status: string; value: string }>>([]);
    const [movements, setMovements] = useState<StockMovement[]>([]);
    const [valuation, setValuation] = useState<InventoryValuation[]>([]);
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        let active = true;
        Promise.all([
            inventoryApi.listDashboardMetrics(),
            inventoryApi.listStockMovements({ per_page: 5 }),
            inventoryApi.listValuation({ per_page: 5 }),
        ])
            .then(([metricResponse, movementResponse, valuationResponse]) => {
                if (!active) {
                    return;
                }

                setMetrics(metricResponse.data);
                setMovements(movementResponse.data);
                setValuation(valuationResponse.data);
            })
            .catch((caught: unknown) => {
                if (active) {
                    setError(caught instanceof Error ? caught.message : 'Unable to load inventory dashboard.');
                }
            });

        return () => {
            active = false;
        };
    }, []);

    return (
        <div className="space-y-6">
            <PageHeader
                actions={<Link to="/inventory/availability-preview"><Button>Availability Preview</Button></Link>}
                eyebrow="Core Inventory"
                subtitle="Generic stock, movement, reservation, transfer, adjustment, valuation, and traceability workspace."
                title="Inventory"
            />
            {error ? <EmptyState description={error} title="Unable to load inventory" /> : null}
            <InventoryDashboardCards metrics={metrics} />
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
                <h2 className="text-sm font-bold uppercase text-slate-500">Recent Movements</h2>
                <StockMovementTable rows={movements} />
            </section>
            <section className="space-y-3">
                <h2 className="text-sm font-bold uppercase text-slate-500">Valuation Summary</h2>
                <ValuationTable rows={valuation} />
            </section>
        </div>
    );
}

export { InventoryDashboardPage as InventoryPage };
