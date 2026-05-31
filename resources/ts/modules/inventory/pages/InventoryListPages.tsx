import { useEffect, useState, type ReactNode } from 'react';
import { Link } from 'react-router-dom';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { SearchFilterBar } from '../../../shared/components/data/SearchFilterBar';
import { Button } from '../../../shared/components/ui/Button';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import {
    BatchTable,
    CostLayerPanel,
    CycleCountTable,
    PickingTaskTable,
    PutAwayTaskTable,
    ReceiptInspectionTable,
    SerialTable,
    StockAdjustmentLineTable,
    StockLevelTable,
    StockMovementTable,
    StockReservationTable,
    StockTransferLineTable,
    ValuationTable,
} from '../components/InventoryComponents';
import { inventoryApi } from '../services/inventoryApi';
import type { CostLayer, CycleCount, InventoryBatch, InventorySerial, InventoryValuation, PickingTask, PutAwayTask, ReceiptInspection, StockAdjustment, StockLevel, StockMovement, StockReservation, StockTransfer } from '../types/inventory.types';

export function StockLevelListPage() {
    return <AsyncList loader={inventoryApi.listStockLevels} render={(rows: StockLevel[]) => <StockLevelTable rows={rows} />} subtitle="Readonly stock by item, warehouse, location, batch, and serial. Frontend does not calculate available quantity." title="Stock Levels" />;
}

export function StockMovementListPage() {
    return <AsyncList loader={inventoryApi.listStockMovements} render={(rows: StockMovement[]) => <StockMovementTable rows={rows} />} subtitle="Stock movement ledger from all source modules. Inventory remains generic." title="Stock Movements" />;
}

export function StockReservationListPage() {
    return <AsyncList loader={inventoryApi.listReservations} render={(rows: StockReservation[]) => <StockReservationTable rows={rows} />} subtitle="Reservations created by generic source references. Backend owns reservation balance and availability." title="Stock Reservations" />;
}

export function StockTransferListPage() {
    return <AsyncList actions={<Link to="/inventory/transfers/new"><Button>New Transfer</Button></Link>} loader={inventoryApi.listTransfers} render={(rows: StockTransfer[]) => rows.length ? rows.map((transfer) => (
        <div className="space-y-3 rounded-lg border border-slate-200 bg-white p-5" key={transfer.id}>
            <div className="flex items-center justify-between">
                <Link className="font-bold text-slate-950 hover:underline" to={`/inventory/transfers/${transfer.id}`}>{transfer.transferNumber}</Link>
                <span className="text-sm text-slate-500">{transfer.status}</span>
            </div>
            <p className="text-sm text-slate-500">{transfer.sourceWarehouse} / {transfer.sourceLocation} to {transfer.destinationWarehouse} / {transfer.destinationLocation}</p>
            <StockTransferLineTable rows={transfer.lines} />
        </div>
    )) : <EmptyState description="No transfers returned by backend." title="No transfers" />} subtitle="Warehouse transfers with backend-owned stock effects." title="Stock Transfers" />;
}

export function StockAdjustmentListPage() {
    return <AsyncList actions={<Link to="/inventory/adjustments/new"><Button>New Adjustment</Button></Link>} loader={inventoryApi.listAdjustments} render={(rows: StockAdjustment[]) => rows.length ? rows.map((adjustment) => (
        <div className="space-y-3 rounded-lg border border-slate-200 bg-white p-5" key={adjustment.id}>
            <div className="flex items-center justify-between">
                <Link className="font-bold text-slate-950 hover:underline" to={`/inventory/adjustments/${adjustment.id}`}>{adjustment.adjustmentNumber}</Link>
                <span className="text-sm text-slate-500">{adjustment.status}</span>
            </div>
            <p className="text-sm text-slate-500">{adjustment.warehouse} / {adjustment.location} / {adjustment.reason}</p>
            <StockAdjustmentLineTable rows={adjustment.lines} />
        </div>
    )) : <EmptyState description="No adjustments returned by backend." title="No adjustments" />} subtitle="Inventory adjustments with backend-owned quantity and valuation impact." title="Stock Adjustments" />;
}

export function CycleCountListPage() {
    return <AsyncList loader={inventoryApi.listCycleCounts} render={(rows: CycleCount[]) => <CycleCountTable rows={rows} />} subtitle="Count variance is returned by backend." title="Cycle Counts" />;
}

export function BatchListPage() {
    return <AsyncList loader={inventoryApi.listBatches} render={(rows: InventoryBatch[]) => <BatchTable rows={rows} />} subtitle="Batch availability is backend-owned." title="Batches" />;
}

export function SerialListPage() {
    return <AsyncList loader={inventoryApi.listSerials} render={(rows: InventorySerial[]) => <SerialTable rows={rows} />} subtitle="Serial availability and assignment are backend-owned." title="Serials" />;
}

export function ReceiptInspectionListPage() {
    return <AsyncList loader={inventoryApi.listReceiptInspections} render={(rows: ReceiptInspection[]) => <ReceiptInspectionTable rows={rows} />} subtitle="Receipt inspection records are generic and source-reference based." title="Receipt Inspections" />;
}

export function PutAwayTaskListPage() {
    return <AsyncList loader={inventoryApi.listPutAwayTasks} render={(rows: PutAwayTask[]) => <PutAwayTaskTable rows={rows} />} subtitle="Put-away task quantities and destinations are backend validated." title="Put-away Tasks" />;
}

export function PickingTaskListPage() {
    return <AsyncList loader={inventoryApi.listPickingTasks} render={(rows: PickingTask[]) => <PickingTaskTable rows={rows} />} subtitle="Picking tasks are source-reference based and backend validated." title="Picking Tasks" />;
}

export function ValuationListPage() {
    const [layers, setLayers] = useState<CostLayer[]>([]);
    useEffect(() => { void inventoryApi.getCostLayers().then((response) => setLayers(response.data)); }, []);

    return <AsyncList loader={inventoryApi.listValuation} render={(rows: InventoryValuation[]) => <><ValuationTable rows={rows} /><div className="mt-6 space-y-3"><h2 className="text-sm font-bold uppercase text-slate-500">Cost Layers</h2><CostLayerPanel rows={layers} /></div></>} subtitle="Readonly valuation from backend. Frontend never calculates cost layers or inventory value." title="Inventory Valuation" />;
}

function AsyncList<T>({ actions, loader, render, subtitle, title }: { actions?: ReactNode; loader: () => Promise<{ data: T[] }>; render: (rows: T[]) => ReactNode; subtitle: string; title: string }) {
    const [rows, setRows] = useState<T[]>([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [query, setQuery] = useState('');

    useEffect(() => {
        setLoading(true);
        loader()
            .then((response) => setRows(response.data))
            .catch((caught: unknown) => setError(caught instanceof Error ? caught.message : `Unable to load ${title.toLowerCase()}.`))
            .finally(() => setLoading(false));
    }, [loader, title]);

    const filteredRows = query
        ? rows.filter((row) => JSON.stringify(row).toLowerCase().includes(query.toLowerCase()))
        : rows;

    return (
        <div className="space-y-6">
            <PageHeader actions={actions} eyebrow="Inventory" subtitle={subtitle} title={title} />
            <SearchFilterBar onSearch={setQuery} />
            {loading ? <EmptyState description="Loading records from backend." title="Loading" /> : null}
            {error ? <EmptyState description={error} title="Unable to load" /> : null}
            {!loading && !error ? render(filteredRows) : null}
        </div>
    );
}
