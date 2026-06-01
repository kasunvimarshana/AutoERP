import { useState } from 'react';
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
    const [isLoaded, setIsLoaded] = useState(false);
    const [isLoading, setIsLoading] = useState(false);

    async function loadDashboardData(): Promise<void> {
        if (isLoading) return;

        setIsLoading(true);
        setError(null);

        try {
            const [metricResponse, movementResponse, valuationResponse] = await Promise.all([
                inventoryApi.listDashboardMetrics(),
                inventoryApi.listStockMovements(),
                inventoryApi.listValuation(),
            ]);

            setMetrics(metricResponse.data);
            setMovements(movementResponse.data.slice(0, 5));
            setValuation(valuationResponse.data.slice(0, 5));
            setIsLoaded(true);
        } catch (caught: unknown) {
            setError(caught instanceof Error ? caught.message : 'Unable to load inventory dashboard.');
        } finally {
            setIsLoading(false);
        }
    }

    return (
        <div className="space-y-6">
            <PageHeader
                actions={<Link to="/inventory/availability-preview"><Button>Availability Preview</Button></Link>}
                eyebrow="Core Inventory"
                subtitle="Generic stock, movement, reservation, transfer, adjustment, valuation, and traceability workspace."
                title="Inventory"
            />
            <div className="flex justify-end">
                <Button disabled={isLoading} onClick={() => void loadDashboardData()} type="button" variant="secondary">{isLoaded ? 'Refresh Dashboard Data' : 'Load Dashboard Data'}</Button>
            </div>
            {error ? <EmptyState description={error} title="Unable to load inventory" /> : null}
            {!isLoaded && !error ? <EmptyState description="Inventory metrics, movements, and valuation load only when requested." title="Dashboard data not loaded" /> : null}
            {isLoaded ? <InventoryDashboardCards metrics={metrics} /> : null}
            <div className="grid gap-4 md:grid-cols-5">
                {[
                    ['Stock levels', '/inventory/stock-levels'],
                    ['Create transfer', '/inventory/transfers/new'],
                    ['Create adjustment', '/inventory/adjustments/new'],
                    ['Cycle counts', '/inventory/cycle-counts'],
                    ['Traceability', '/inventory/traceability'],
                ].map(([label, path]) => <Link className="rounded-lg border border-slate-200 bg-white p-5 text-sm font-bold text-slate-900 shadow-sm hover:border-slate-300" key={label} to={path}>{label}</Link>)}
            </div>
            {isLoaded ? <section className="space-y-3">
                <h2 className="text-sm font-bold uppercase text-slate-500">Recent Movements</h2>
                <StockMovementTable rows={movements} />
            </section> : null}
            {isLoaded ? <section className="space-y-3">
                <h2 className="text-sm font-bold uppercase text-slate-500">Valuation Summary</h2>
                <ValuationTable rows={valuation} />
            </section> : null}
        </div>
    );
}

export { InventoryDashboardPage as InventoryPage };
