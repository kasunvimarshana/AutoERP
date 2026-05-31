import { useEffect, useState, type ReactNode } from 'react';
import { Link } from 'react-router-dom';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { DataToolbar, type DataToolbarFilterConfig, type DataToolbarFilterValue, type DataToolbarFilterValues } from '../../../shared/components/data/DataToolbar';
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
import type { CostLayer, CycleCount, InventoryBatch, InventoryListQuery, InventoryLookupOption, InventorySerial, InventoryValuation, PickingTask, PutAwayTask, ReceiptInspection, StockAdjustment, StockLevel, StockMovement, StockReservation, StockTransfer } from '../types/inventory.types';

export function StockLevelListPage() {
    const [rows, setRows] = useState<StockLevel[]>([]);
    const [lookups, setLookups] = useState<StockLevelLookups>({ items: [], locations: [], uoms: [], warehouses: [] });
    const [filterValues, setFilterValues] = useState<DataToolbarFilterValues>({});
    const [search, setSearch] = useState('');
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        let mounted = true;

        Promise.all([
            inventoryApi.listItems(),
            inventoryApi.listWarehouses(),
            inventoryApi.listLocations(),
            inventoryApi.listUoms(),
        ])
            .then(([items, warehouses, locations, uoms]) => {
                if (!mounted) return;
                setLookups({
                    items: items.data,
                    locations: locations.data,
                    uoms: uoms.data,
                    warehouses: warehouses.data,
                });
            })
            .catch(() => {
                if (mounted) {
                    setLookups({ items: [], locations: [], uoms: [], warehouses: [] });
                }
            });

        return () => { mounted = false; };
    }, []);

    useEffect(() => {
        let mounted = true;
        setLoading(true);
        setError(null);

        inventoryApi.listStockLevels(stockLevelQuery(search, filterValues))
            .then((response) => {
                if (mounted) setRows(response.data);
            })
            .catch((caught: unknown) => {
                if (mounted) setError(caught instanceof Error ? caught.message : 'Unable to load stock levels.');
            })
            .finally(() => {
                if (mounted) setLoading(false);
            });

        return () => { mounted = false; };
    }, [filterValues, search]);

    function updateFilter(filterId: string, value: DataToolbarFilterValue): void {
        setFilterValues((current) => ({ ...current, [filterId]: value }));
    }

    function resetFilters(): void {
        setFilterValues({});
    }

    const filters = stockLevelFilters(lookups);

    return (
        <div className="space-y-6">
            <PageHeader eyebrow="Inventory" subtitle="Readonly stock by item, warehouse, location, batch, and serial." title="Stock Levels" />
            <DataToolbar
                disabled={loading}
                filterValues={filterValues}
                filters={filters}
                isLoading={loading}
                onFilterChange={updateFilter}
                onRemoveFilter={(filterId) => updateFilter(filterId, undefined)}
                onResetFilters={resetFilters}
                onSearchChange={setSearch}
                savedViewsDisabledReason="Saved views need a user-preferences backend before they can be enabled for inventory lists."
                searchPlaceholder="Search item, warehouse, location, batch, serial, UOM, or status..."
                searchValue={search}
            />
            {loading ? <EmptyState description="Loading stock levels." title="Loading" /> : null}
            {error ? <EmptyState description={error} title="Unable to load" /> : null}
            {!loading && !error ? <StockLevelTable rows={rows} /> : null}
        </div>
    );
}

export function StockMovementListPage() {
    return <AsyncList loader={inventoryApi.listStockMovements} render={(rows: StockMovement[]) => <StockMovementTable rows={rows} />} subtitle="Stock movement ledger from all source modules. Inventory remains generic." title="Stock Movements" />;
}

export function StockReservationListPage() {
    return <AsyncList loader={inventoryApi.listReservations} render={(rows: StockReservation[]) => <StockReservationTable rows={rows} />} subtitle="Reservations created by generic source references." title="Stock Reservations" />;
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
    )) : <EmptyState description="No transfers available." title="No transfers" />} subtitle="Warehouse transfers and transfer lines." title="Stock Transfers" />;
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
    )) : <EmptyState description="No adjustments available." title="No adjustments" />} subtitle="Inventory adjustments and adjustment lines." title="Stock Adjustments" />;
}

export function CycleCountListPage() {
    return <AsyncList loader={inventoryApi.listCycleCounts} render={(rows: CycleCount[]) => <CycleCountTable rows={rows} />} subtitle="Cycle count headers and variance summaries." title="Cycle Counts" />;
}

export function BatchListPage() {
    return <AsyncList loader={inventoryApi.listBatches} render={(rows: InventoryBatch[]) => <BatchTable rows={rows} />} subtitle="Batch tracking by item and warehouse." title="Batches" />;
}

export function SerialListPage() {
    return <AsyncList loader={inventoryApi.listSerials} render={(rows: InventorySerial[]) => <SerialTable rows={rows} />} subtitle="Serial tracking by item and warehouse." title="Serials" />;
}

export function ReceiptInspectionListPage() {
    return <AsyncList loader={inventoryApi.listReceiptInspections} render={(rows: ReceiptInspection[]) => <ReceiptInspectionTable rows={rows} />} subtitle="Receipt inspection records are generic and source-reference based." title="Receipt Inspections" />;
}

export function PutAwayTaskListPage() {
    return <AsyncList loader={inventoryApi.listPutAwayTasks} render={(rows: PutAwayTask[]) => <PutAwayTaskTable rows={rows} />} subtitle="Put-away task quantities and destinations." title="Put-away Tasks" />;
}

export function PickingTaskListPage() {
    return <AsyncList loader={inventoryApi.listPickingTasks} render={(rows: PickingTask[]) => <PickingTaskTable rows={rows} />} subtitle="Picking tasks by source reference." title="Picking Tasks" />;
}

export function ValuationListPage() {
    const [layers, setLayers] = useState<CostLayer[]>([]);
    useEffect(() => { void inventoryApi.getCostLayers().then((response) => setLayers(response.data)); }, []);

    return <AsyncList loader={inventoryApi.listValuation} render={(rows: InventoryValuation[]) => <><ValuationTable rows={rows} /><div className="mt-6 space-y-3"><h2 className="text-sm font-bold uppercase text-slate-500">Cost Layers</h2><CostLayerPanel rows={layers} /></div></>} subtitle="Readonly valuation and cost layers." title="Inventory Valuation" />;
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
            <DataToolbar
                isLoading={loading}
                onSearchChange={setQuery}
                savedViewsDisabledReason={`Saved views need a user-preferences backend before they can be enabled for ${title.toLowerCase()}.`}
                searchPlaceholder={`Search ${title.toLowerCase()}...`}
                searchValue={query}
            />
            {loading ? <EmptyState description="Loading records." title="Loading" /> : null}
            {error ? <EmptyState description={error} title="Unable to load" /> : null}
            {!loading && !error ? render(filteredRows) : null}
        </div>
    );
}

type StockLevelLookups = {
    items: InventoryLookupOption[];
    locations: InventoryLookupOption[];
    uoms: InventoryLookupOption[];
    warehouses: InventoryLookupOption[];
};

function stockLevelFilters(lookups: StockLevelLookups): DataToolbarFilterConfig[] {
    return [
        { id: 'item_id', label: 'Item', options: lookupOptions(lookups.items), placeholder: 'Any item', type: 'entity' },
        { id: 'warehouse_id', label: 'Warehouse', options: lookupOptions(lookups.warehouses), placeholder: 'Any warehouse', type: 'entity' },
        { id: 'location_id', label: 'Location', options: lookupOptions(lookups.locations), placeholder: 'Any location', type: 'entity' },
        { id: 'uom_id', label: 'UOM', options: lookupOptions(lookups.uoms), placeholder: 'Any UOM', type: 'select' },
        { id: 'status', label: 'Condition', options: [
            { label: 'Good', value: 'good' },
            { label: 'Damaged', value: 'damaged' },
            { label: 'Blocked', value: 'blocked' },
            { label: 'Expired', value: 'expired' },
            { label: 'Quarantine', value: 'quarantine' },
        ], placeholder: 'Any condition', type: 'status' },
        { id: 'batch_serial', label: 'Batch or serial', placeholder: 'Batch or serial number', type: 'text' },
        { id: 'low_stock', label: 'Low stock only', placeholder: 'Quantity at or below minimum', type: 'boolean' },
    ];
}

function lookupOptions(options: InventoryLookupOption[]) {
    return options.map((option) => ({
        label: option.secondary ? `${option.secondary} - ${option.label}` : option.label,
        value: option.id,
    }));
}

function stockLevelQuery(search: string, filterValues: DataToolbarFilterValues): InventoryListQuery {
    return {
        batch_serial: stringFilter(filterValues.batch_serial),
        item_id: stringFilter(filterValues.item_id),
        location_id: stringFilter(filterValues.location_id),
        low_stock: filterValues.low_stock === true ? true : undefined,
        search: search.trim() || undefined,
        status: stringFilter(filterValues.status),
        uom_id: stringFilter(filterValues.uom_id),
        warehouse_id: stringFilter(filterValues.warehouse_id),
    };
}

function stringFilter(value: DataToolbarFilterValue): string | undefined {
    return typeof value === 'string' && value.trim() !== '' ? value : undefined;
}
