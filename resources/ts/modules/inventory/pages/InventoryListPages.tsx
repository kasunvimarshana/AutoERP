import { useEffect, useState } from 'react';
import type { ReactNode } from 'react';
import { Link } from 'react-router-dom';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { SearchFilterBar } from '../../../shared/components/data/SearchFilterBar';
import { Button } from '../../../shared/components/ui/Button';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import {
    BatchTable,
    CycleCountTable,
    PickingTaskTable,
    PutAwayTaskTable,
    ReceiptInspectionTable,
    SerialTable,
    StockLevelTable,
    StockMovementTable,
    StockReservationTable,
    StockTransferLineTable,
    ValuationTable,
    CostLayerPanel,
} from '../components/InventoryComponents';
import { inventoryApi } from '../services/inventoryApi';
import type { CostLayer, CycleCount, InventoryBatch, InventorySerial, InventoryValuation, PickingTask, PutAwayTask, ReceiptInspection, StockAdjustment, StockLevel, StockMovement, StockReservation, StockTransfer } from '../types/inventory.types';

export function StockLevelListPage() {
    const [rows, setRows] = useState<StockLevel[]>([]);
    useEffect(() => { inventoryApi.listStockLevels().then((response) => setRows(response.data)); }, []);
    return <ListPage subtitle="Readonly stock by item, warehouse, location, batch, and serial. Frontend does not calculate available quantity." title="Stock Levels"><StockLevelTable rows={rows} /></ListPage>;
}

export function StockMovementListPage() {
    const [rows, setRows] = useState<StockMovement[]>([]);
    useEffect(() => { inventoryApi.listStockMovements().then((response) => setRows(response.data)); }, []);
    return <ListPage subtitle="Stock movement ledger from all source modules. Inventory remains generic." title="Stock Movements"><StockMovementTable rows={rows} /></ListPage>;
}

export function StockReservationListPage() {
    const [rows, setRows] = useState<StockReservation[]>([]);
    useEffect(() => { inventoryApi.listReservations().then((response) => setRows(response.data)); }, []);
    return <ListPage subtitle="Reservations created by generic source references. Backend owns reservation balance and availability." title="Stock Reservations"><StockReservationTable rows={rows} /></ListPage>;
}

export function StockTransferListPage() {
    const [rows, setRows] = useState<StockTransfer[]>([]);
    useEffect(() => { inventoryApi.listTransfers().then((response) => setRows(response.data)); }, []);
    return (
        <ListPage actions={<Link to="/inventory/transfers/new"><Button>New Transfer</Button></Link>} subtitle="Warehouse transfers with backend-owned stock effects." title="Stock Transfers">
            {rows.length ? rows.map((transfer) => (
                <div className="space-y-3 rounded-lg border border-slate-200 bg-white p-5" key={transfer.id}>
                    <div className="flex items-center justify-between"><Link className="font-bold text-slate-950 hover:underline" to={`/inventory/transfers/${transfer.id}`}>{transfer.transferNumber}</Link><span className="text-sm text-slate-500">{transfer.status}</span></div>
                    <p className="text-sm text-slate-500">{transfer.sourceWarehouse} / {transfer.sourceLocation} to {transfer.destinationWarehouse} / {transfer.destinationLocation}</p>
                    <StockTransferLineTable rows={transfer.lines} />
                </div>
            )) : <EmptyState description="No transfers returned yet." title="No transfers" />}
        </ListPage>
    );
}

export function StockAdjustmentListPage() {
    return <ListPage actions={<Link to="/inventory/adjustments/new"><Button>New Adjustment</Button></Link>} subtitle="Inventory adjustments with backend-owned quantity and valuation impact." title="Stock Adjustments"><AdjustmentRows /></ListPage>;
}

export function CycleCountListPage() {
    const [rows, setRows] = useState<CycleCount[]>([]);
    useEffect(() => { inventoryApi.listCycleCounts().then((response) => setRows(response.data)); }, []);
    return <ListPage subtitle="Count variance is backend/mock readonly." title="Cycle Counts"><CycleCountTable rows={rows} /></ListPage>;
}

export function BatchListPage() {
    const [rows, setRows] = useState<InventoryBatch[]>([]);
    useEffect(() => { inventoryApi.listBatches().then((response) => setRows(response.data)); }, []);
    return <ListPage subtitle="Batch availability is backend-owned." title="Batches"><BatchTable rows={rows} /></ListPage>;
}

export function SerialListPage() {
    const [rows, setRows] = useState<InventorySerial[]>([]);
    useEffect(() => { inventoryApi.listSerials().then((response) => setRows(response.data)); }, []);
    return <ListPage subtitle="Serial availability and assignment are backend-owned." title="Serials"><SerialTable rows={rows} /></ListPage>;
}

export function ReceiptInspectionListPage() {
    const [rows, setRows] = useState<ReceiptInspection[]>([]);
    useEffect(() => { inventoryApi.listReceiptInspections().then((response) => setRows(response.data)); }, []);
    return <ListPage subtitle="Receipt inspection records are generic and source-reference based." title="Receipt Inspections"><ReceiptInspectionTable rows={rows} /></ListPage>;
}

export function PutAwayTaskListPage() {
    const [rows, setRows] = useState<PutAwayTask[]>([]);
    useEffect(() => { inventoryApi.listPutAwayTasks().then((response) => setRows(response.data)); }, []);
    return <ListPage subtitle="Put-away task quantities and destinations are backend validated." title="Put-away Tasks"><PutAwayTaskTable rows={rows} /></ListPage>;
}

export function PickingTaskListPage() {
    const [rows, setRows] = useState<PickingTask[]>([]);
    useEffect(() => { inventoryApi.listPickingTasks().then((response) => setRows(response.data)); }, []);
    return <ListPage subtitle="Picking tasks are source-reference based and backend validated." title="Picking Tasks"><PickingTaskTable rows={rows} /></ListPage>;
}

export function ValuationListPage() {
    const [rows, setRows] = useState<InventoryValuation[]>([]);
    useEffect(() => { inventoryApi.listValuation().then((response) => setRows(response.data)); }, []);
    return <ListPage subtitle="Readonly valuation from backend/mock. Frontend never calculates cost layers or inventory value." title="Inventory Valuation"><ValuationTable rows={rows} /><CostLayers /></ListPage>;
}

function AdjustmentRows() {
    const [rows, setRows] = useState<StockAdjustment[]>([]);
    useEffect(() => { inventoryApi.listAdjustments().then((response) => setRows(response.data)); }, []);
    return rows.length ? <ReceiptInspectionTable rows={rows.map((row) => ({ id: row.id, inspectionNumber: row.adjustmentNumber, itemName: row.warehouse, result: row.reason, sourceReference: 'Inventory adjustment', status: row.status }))} /> : <EmptyState description="No adjustments returned yet." title="No adjustments" />;
}

function CostLayers() {
    const [rows, setRows] = useState<CostLayer[]>([]);
    useEffect(() => { inventoryApi.getCostLayers().then((response) => setRows(response.data)); }, []);
    return <div className="mt-6 space-y-3"><h2 className="text-sm font-bold uppercase tracking-wide text-slate-500">Cost Layers</h2><CostLayerPanel rows={rows} /></div>;
}

function ListPage({ actions, children, subtitle, title }: { actions?: ReactNode; children: ReactNode; subtitle: string; title: string }) {
    return (
        <div className="space-y-6">
            <PageHeader actions={actions} eyebrow="Inventory" subtitle={subtitle} title={title} />
            <SearchFilterBar placeholder={`Search ${title.toLowerCase()}...`} />
            {children}
        </div>
    );
}
