import { useMemo, useState, type FormEvent, type ReactNode } from 'react';
import { Link } from 'react-router-dom';
import {
    approveStockCount,
    cancelTransfer,
    createAllocation,
    createCostAdjustment,
    createReservation,
    createStockCount,
    createTransfer,
    dispatchTransfer,
    getAvailability,
    issueAllocation,
    listAllocations,
    listBatches,
    listCostAdjustments,
    listReservations,
    listSerials,
    listStateChanges,
    listStockBalances,
    listStockCounts,
    listTransfers,
    listValuationLayers,
    postStockCount,
    postCostAdjustment,
    receiveTransfer,
    releaseAllocation,
    releaseReservation,
    type AllocationPayload,
    type CostAdjustmentPayload,
    type InventoryRecord,
    type ReservationPayload,
    type StockCountPayload,
    type TransferPayload,
} from '../inventoryApi';
import { useApi } from '@/shared/hooks/useApi';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { Panel } from '@/shared/components/Panel';
import { Input } from '@/shared/components/Input';
import { Button } from '@/shared/components/Button';
import { DataTable } from '@/shared/components/DataTable';
import { DetailGrid } from '@/shared/components/DetailGrid';
import { Pagination } from '@/shared/components/Pagination';
import { QuantityDisplay } from '@/shared/components/QuantityDisplay';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { LookupSelect } from '@/shared/components/LookupSelect';
import { DecimalInput } from '@/shared/components/DecimalInput';
import { Tabs } from '@/shared/components/Tabs';
import { Select } from '@/shared/components/Select';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { compactObject, humanize, readableRelation } from '@/shared/utils/object';
import { fieldError, toApiError, type ApiError } from '@/shared/api/apiError';
import { lookupApi } from '@/shared/api/lookupApi';
import { searchWarehouses } from '@/shared/api/referenceApi';
import type { NamedResource } from '@/shared/types/common';

type Tab = 'dashboard' | 'availability' | 'reservations' | 'allocations' | 'transfers' | 'counts' | 'costing' | 'tracking' | 'audit' | 'reports';

const tabs = [
    { id: 'dashboard' as Tab, label: 'Dashboard' },
    { id: 'availability' as Tab, label: 'Availability' },
    { id: 'reservations' as Tab, label: 'Reservations' },
    { id: 'allocations' as Tab, label: 'Allocations' },
    { id: 'transfers' as Tab, label: 'Transfers' },
    { id: 'counts' as Tab, label: 'Stock counts' },
    { id: 'costing' as Tab, label: 'Costing' },
    { id: 'tracking' as Tab, label: 'Batch/serial' },
    { id: 'audit' as Tab, label: 'Audit' },
    { id: 'reports' as Tab, label: 'Reports' },
];

const today = () => new Date().toISOString().slice(0, 10);
const decimal = '0.000000';

interface ApiResult<T> {
    data: T | null;
    error: ApiError | null;
    loading: boolean;
    reload: () => void;
    setData: (data: T) => void;
}

export default function InventoryPage() {
    const [tab, setTab] = useState<Tab>('dashboard');
    const [page, setPage] = useState(1);
    const [itemFilter, setItemFilter] = useState<NamedResource | null>(null);
    const balances = useApi((signal) => listStockBalances({ page, per_page: 25, item_id: itemFilter?.id }, signal), [page, itemFilter?.id]);
    const reservations = useApi((signal) => listReservations({ per_page: 25 }, signal), [], ['reservations', 'allocations'].includes(tab));
    const allocations = useApi((signal) => listAllocations({ per_page: 25 }, signal), [], tab === 'allocations');
    const transfers = useApi((signal) => listTransfers({ per_page: 25 }, signal), [], tab === 'transfers');
    const counts = useApi((signal) => listStockCounts({ per_page: 25 }, signal), [], tab === 'counts');
    const valuationLayers = useApi((signal) => listValuationLayers({ per_page: 25, status: 'open' }, signal), [], tab === 'costing');
    const costAdjustments = useApi((signal) => listCostAdjustments({ per_page: 25 }, signal), [], tab === 'costing');
    const batches = useApi((signal) => listBatches({ per_page: 25 }, signal), [], tab === 'tracking');
    const serials = useApi((signal) => listSerials({ per_page: 25 }, signal), [], tab === 'tracking');
    const states = useApi((signal) => listStateChanges({ per_page: 20 }, signal), [], ['dashboard', 'audit'].includes(tab));
    const reloadInventory = () => {
        balances.reload();
        reservations.reload();
        allocations.reload();
        transfers.reload();
        counts.reload();
        valuationLayers.reload();
        costAdjustments.reload();
        states.reload();
    };

    return (
        <>
            <ContentHeader title="Inventory" description="Availability, reservations, allocations, transfers, counts, tracking, and reports." />
            <Tabs tabs={tabs} active={tab} onChange={setTab} />
            <div className="mt-5">
                {tab === 'dashboard' && <DashboardTab balances={balances} states={states} itemFilter={itemFilter} setItemFilter={setItemFilter} page={page} setPage={setPage} />}
                {tab === 'availability' && <AvailabilityTab />}
                {tab === 'reservations' && <ReservationsTab data={reservations.data?.data ?? []} loading={reservations.loading} error={reservations.error} reload={reloadInventory} />}
                {tab === 'allocations' && <AllocationsTab data={allocations.data?.data ?? []} reservations={reservations.data?.data ?? []} loading={allocations.loading} error={allocations.error} reload={reloadInventory} />}
                {tab === 'transfers' && <TransfersTab data={transfers.data?.data ?? []} loading={transfers.loading} error={transfers.error} reload={reloadInventory} />}
                {tab === 'counts' && <CountsTab data={counts.data?.data ?? []} loading={counts.loading} error={counts.error} reload={reloadInventory} />}
                {tab === 'costing' && <CostingTab data={costAdjustments.data?.data ?? []} layers={valuationLayers.data?.data ?? []} layersLoading={valuationLayers.loading} layersError={valuationLayers.error} loading={costAdjustments.loading} error={costAdjustments.error} reload={reloadInventory} />}
                {tab === 'tracking' && <TrackingTab batches={batches} serials={serials} />}
                {tab === 'audit' && <AuditTab states={states} />}
                {tab === 'reports' && <ReportsTab />}
            </div>
        </>
    );
}

function DashboardTab({ balances, states, itemFilter, setItemFilter, page, setPage }: {
    balances: ApiResult<Awaited<ReturnType<typeof listStockBalances>>>;
    states: ApiResult<Awaited<ReturnType<typeof listStateChanges>>>;
    itemFilter: NamedResource | null;
    setItemFilter: (value: NamedResource | null) => void;
    page: number;
    setPage: (page: number) => void;
}) {
    const totals = useMemo(() => {
        const rows = balances.data?.data ?? [];
        return {
            balances: rows.length,
            onHand: sum(rows.map((row) => String(row.quantity_on_hand ?? decimal))),
            available: sum(rows.map((row) => String(row.quantity_available ?? decimal))),
            reserved: sum(rows.map((row) => String(row.quantity_reserved ?? decimal))),
            allocated: sum(rows.map((row) => String(row.quantity_allocated ?? decimal))),
        };
    }, [balances.data?.data]);

    return (
        <div className="space-y-5">
            <Panel>
                <DetailGrid items={[
                    { label: 'Balance rows', value: totals.balances },
                    { label: 'On hand', value: <QuantityDisplay value={totals.onHand} /> },
                    { label: 'Available', value: <QuantityDisplay value={totals.available} /> },
                    { label: 'Reserved', value: <QuantityDisplay value={totals.reserved} /> },
                    { label: 'Allocated', value: <QuantityDisplay value={totals.allocated} /> },
                ]} />
            </Panel>
            <div className="grid gap-5 xl:grid-cols-[1fr_24rem]">
                <Panel title="Stock balances">
                    <div className="mb-4 max-w-md">
                        <LookupSelect label="Item" value={itemFilter} onChange={(value) => { setItemFilter(value); setPage(1); }} search={lookupApi.stockableItems} placeholder="Search stockable items..." />
                    </div>
                    <ErrorAlert error={balances.error} />
                    {balances.loading ? <LoadingState /> : <BalanceTable rows={balances.data?.data ?? []} />}
                    <Pagination meta={balances.data?.meta} onPageChange={setPage} />
                    <div className="sr-only">Current balance page {page}</div>
                </Panel>
                <Panel title="Recent state changes">
                    <ErrorAlert error={states.error} />
                    {states.loading ? <LoadingState /> : (
                        <DataTable
                            rows={states.data?.data ?? []}
                            rowKey={(row) => row.id}
                            columns={[
                                { key: 'item', header: 'Item', render: (row) => relation(row.item) },
                                { key: 'state', header: 'State', render: (row) => `${humanize(String(row.from_state ?? ''))} -> ${humanize(String(row.to_state ?? ''))}` },
                                { key: 'qty', header: 'Qty', render: (row) => <QuantityDisplay value={String(row.quantity ?? decimal)} /> },
                            ]}
                        />
                    )}
                </Panel>
            </div>
        </div>
    );
}

function AvailabilityTab() {
    const [item, setItem] = useState<NamedResource | null>(null);
    const [warehouse, setWarehouse] = useState<NamedResource | null>(null);
    const [availability, setAvailability] = useState<Record<string, unknown> | null>(null);
    const [error, setError] = useState<ApiError | null>(null);
    const [checking, setChecking] = useState(false);

    return (
        <Panel title="Availability lookup">
            <form className="grid gap-4 lg:grid-cols-[1fr_1fr_auto]" onSubmit={async (event) => {
                event.preventDefault();
                setChecking(true);
                setError(null);
                try {
                    setAvailability(await getAvailability({ item_id: item?.id ?? 0, warehouse_id: warehouse?.id ?? 0 }));
                } catch (requestError) {
                    setError(toApiError(requestError));
                } finally {
                    setChecking(false);
                }
            }}>
                <LookupSelect label="Item" value={item} onChange={setItem} search={lookupApi.stockableItems} placeholder="Search stockable items..." />
                <LookupSelect label="Warehouse" value={warehouse} onChange={setWarehouse} search={searchWarehouses} placeholder="Search warehouses..." />
                <div className="flex items-end"><Button type="submit" loading={checking}>Check</Button></div>
            </form>
            <div className="mt-4"><ErrorAlert error={error} /></div>
            {availability && <div className="mt-5"><DetailGrid items={[
                { label: 'Available', value: q(availability.quantityAvailable) },
                { label: 'Reserved', value: q(availability.quantityReserved) },
                { label: 'Allocated', value: q(availability.quantityAllocated) },
                { label: 'In transit', value: q(availability.quantityInTransit) },
                { label: 'Damaged', value: q(availability.quantityDamaged) },
                { label: 'Quarantine', value: q(availability.quantityQuarantine) },
                { label: 'Expired', value: q(availability.quantityExpired) },
                { label: 'Total', value: q(availability.quantityTotal) },
            ]} /></div>}
        </Panel>
    );
}

function ReservationsTab({ data, loading, error, reload }: WorkflowProps) {
    const [form, setForm] = useState({ reservation_date: today(), quantity_reserved: '1.000000', notes: '' });
    const [item, setItem] = useState<NamedResource | null>(null);
    const [warehouse, setWarehouse] = useState<NamedResource | null>(null);
    const [busy, setBusy] = useState(false);
    const [actionError, setActionError] = useState<ApiError | null>(null);
    const submit = (event: FormEvent) => void runAction(event, setBusy, setActionError, async () => {
        await createReservation(compactObject({
            reservation_date: form.reservation_date,
            item_id: item?.id ?? 0,
            warehouse_id: warehouse?.id ?? 0,
            quantity_reserved: form.quantity_reserved,
            notes: form.notes,
        }) as ReservationPayload);
        reload();
    });

    return (
        <WorkflowPanel title="Reservation management" loading={loading} error={error} actionError={actionError}>
            <form className="grid gap-4 lg:grid-cols-[1fr_1fr_10rem_1fr_auto]" onSubmit={submit}>
                <LookupSelect label="Item" value={item} onChange={setItem} search={lookupApi.stockableItems} error={fieldError(actionError, 'item_id')} />
                <LookupSelect label="Warehouse" value={warehouse} onChange={setWarehouse} search={searchWarehouses} error={fieldError(actionError, 'warehouse_id')} />
                <DecimalInput label="Quantity" value={form.quantity_reserved} error={fieldError(actionError, 'quantity_reserved')} onChange={(event) => setForm({ ...form, quantity_reserved: event.target.value })} />
                <Input label="Date" type="date" value={form.reservation_date} error={fieldError(actionError, 'reservation_date')} onChange={(event) => setForm({ ...form, reservation_date: event.target.value })} />
                <div className="flex items-end"><Button type="submit" loading={busy}>Reserve</Button></div>
            </form>
            <RecordList rows={data} columns={reservationColumns((row) => <Button variant="secondary" onClick={() => void action(() => releaseReservation(row.id), reload, setActionError)}>Unreserve</Button>)} />
        </WorkflowPanel>
    );
}

function AllocationsTab({ data, reservations, loading, error, reload }: WorkflowProps & { reservations: InventoryRecord[] }) {
    const [form, setForm] = useState({ allocation_date: today(), quantity_allocated: '1.000000', reservation: '' });
    const [item, setItem] = useState<NamedResource | null>(null);
    const [warehouse, setWarehouse] = useState<NamedResource | null>(null);
    const [busy, setBusy] = useState(false);
    const [actionError, setActionError] = useState<ApiError | null>(null);
    const submit = (event: FormEvent) => void runAction(event, setBusy, setActionError, async () => {
        await createAllocation(compactObject({
            allocation_date: form.allocation_date,
            item_id: item?.id ?? 0,
            warehouse_id: warehouse?.id ?? 0,
            quantity_allocated: form.quantity_allocated,
            reservation_id: form.reservation ? Number(form.reservation) : undefined,
        }) as AllocationPayload);
        reload();
    });

    return (
        <WorkflowPanel title="Allocation management" loading={loading} error={error} actionError={actionError}>
            <form className="grid gap-4 lg:grid-cols-[1fr_1fr_10rem_1fr_12rem_auto]" onSubmit={submit}>
                <LookupSelect label="Item" value={item} onChange={setItem} search={lookupApi.stockableItems} error={fieldError(actionError, 'item_id')} />
                <LookupSelect label="Warehouse" value={warehouse} onChange={setWarehouse} search={searchWarehouses} error={fieldError(actionError, 'warehouse_id')} />
                <DecimalInput label="Quantity" value={form.quantity_allocated} error={fieldError(actionError, 'quantity_allocated')} onChange={(event) => setForm({ ...form, quantity_allocated: event.target.value })} />
                <Input label="Date" type="date" value={form.allocation_date} error={fieldError(actionError, 'allocation_date')} onChange={(event) => setForm({ ...form, allocation_date: event.target.value })} />
                <Select label="Reservation" value={form.reservation} error={fieldError(actionError, 'reservation_id')} options={reservations.filter((row) => ['active', 'partially_allocated'].includes(String(row.status))).map((row) => ({ value: String(row.id), label: label(row, 'reservation_number') }))} onChange={(event) => setForm({ ...form, reservation: event.target.value })} />
                <div className="flex items-end"><Button type="submit" loading={busy}>Allocate</Button></div>
            </form>
            <RecordList rows={data} columns={allocationColumns((row) => (
                <div className="flex flex-wrap gap-2">
                    <Button variant="secondary" onClick={() => void action(() => issueAllocation(row.id), reload, setActionError)}>Issue</Button>
                    <Button variant="ghost" onClick={() => void action(() => releaseAllocation(row.id), reload, setActionError)}>Release</Button>
                </div>
            ))} />
        </WorkflowPanel>
    );
}

function TransfersTab({ data, loading, error, reload }: WorkflowProps) {
    const [form, setForm] = useState({ transfer_date: today(), quantity: '1.000000', unit_cost: '0.000000' });
    const [item, setItem] = useState<NamedResource | null>(null);
    const [fromWarehouse, setFromWarehouse] = useState<NamedResource | null>(null);
    const [toWarehouse, setToWarehouse] = useState<NamedResource | null>(null);
    const [busy, setBusy] = useState(false);
    const [actionError, setActionError] = useState<ApiError | null>(null);
    const submit = (event: FormEvent) => void runAction(event, setBusy, setActionError, async () => {
        await createTransfer(compactObject({
            transfer_date: form.transfer_date,
            from_warehouse_id: fromWarehouse?.id ?? 0,
            to_warehouse_id: toWarehouse?.id ?? 0,
            lines: [{ item_id: item?.id ?? 0, quantity: form.quantity, unit_cost: form.unit_cost }],
        }) as TransferPayload);
        reload();
    });

    return (
        <WorkflowPanel title="Transfer workflow" loading={loading} error={error} actionError={actionError}>
            <form className="grid gap-4 xl:grid-cols-[1fr_1fr_1fr_9rem_9rem_1fr_auto]" onSubmit={submit}>
                <LookupSelect label="Item" value={item} onChange={setItem} search={lookupApi.stockableItems} error={fieldError(actionError, 'lines.0.item_id')} />
                <LookupSelect label="From warehouse" value={fromWarehouse} onChange={setFromWarehouse} search={searchWarehouses} error={fieldError(actionError, 'from_warehouse_id')} />
                <LookupSelect label="To warehouse" value={toWarehouse} onChange={setToWarehouse} search={searchWarehouses} error={fieldError(actionError, 'to_warehouse_id')} />
                <DecimalInput label="Qty" value={form.quantity} error={fieldError(actionError, 'lines.0.quantity')} onChange={(event) => setForm({ ...form, quantity: event.target.value })} />
                <DecimalInput label="Unit cost" value={form.unit_cost} error={fieldError(actionError, 'lines.0.unit_cost')} onChange={(event) => setForm({ ...form, unit_cost: event.target.value })} />
                <Input label="Date" type="date" value={form.transfer_date} error={fieldError(actionError, 'transfer_date')} onChange={(event) => setForm({ ...form, transfer_date: event.target.value })} />
                <div className="flex items-end"><Button type="submit" loading={busy}>Create</Button></div>
            </form>
            <RecordList rows={data} columns={transferColumns((row) => (
                <div className="flex flex-wrap gap-2">
                    <Button variant="secondary" onClick={() => void action(() => dispatchTransfer(row.id), reload, setActionError)}>Dispatch</Button>
                    <Button variant="secondary" onClick={() => void action(() => receiveTransfer(row.id), reload, setActionError)}>Receive</Button>
                    <Button variant="ghost" onClick={() => void action(() => cancelTransfer(row.id), reload, setActionError)}>Cancel</Button>
                </div>
            ))} />
        </WorkflowPanel>
    );
}

function CountsTab({ data, loading, error, reload }: WorkflowProps) {
    const [form, setForm] = useState({ count_date: today(), counted_quantity: '0.000000', system_quantity: '', unit_cost: '0.000000', count_type: 'stock_count' });
    const [item, setItem] = useState<NamedResource | null>(null);
    const [warehouse, setWarehouse] = useState<NamedResource | null>(null);
    const [busy, setBusy] = useState(false);
    const [actionError, setActionError] = useState<ApiError | null>(null);
    const submit = (event: FormEvent) => void runAction(event, setBusy, setActionError, async () => {
        await createStockCount(compactObject({
            count_date: form.count_date,
            count_type: form.count_type as 'stock_count' | 'cycle_count',
            warehouse_id: warehouse?.id ?? 0,
            lines: [compactObject({
                item_id: item?.id ?? 0,
                counted_quantity: form.counted_quantity,
                system_quantity: form.system_quantity || undefined,
                unit_cost: form.unit_cost,
            })],
        }) as StockCountPayload);
        reload();
    });

    return (
        <WorkflowPanel title="Stock count workflow" loading={loading} error={error} actionError={actionError}>
            <form className="grid gap-4 xl:grid-cols-[1fr_1fr_9rem_9rem_9rem_1fr_10rem_auto]" onSubmit={submit}>
                <LookupSelect label="Item" value={item} onChange={setItem} search={lookupApi.stockableItems} error={fieldError(actionError, 'lines.0.item_id')} />
                <LookupSelect label="Warehouse" value={warehouse} onChange={setWarehouse} search={searchWarehouses} error={fieldError(actionError, 'warehouse_id')} />
                <DecimalInput label="Counted" value={form.counted_quantity} error={fieldError(actionError, 'lines.0.counted_quantity')} onChange={(event) => setForm({ ...form, counted_quantity: event.target.value })} />
                <DecimalInput label="System" value={form.system_quantity} error={fieldError(actionError, 'lines.0.system_quantity')} onChange={(event) => setForm({ ...form, system_quantity: event.target.value })} />
                <DecimalInput label="Unit cost" value={form.unit_cost} error={fieldError(actionError, 'lines.0.unit_cost')} onChange={(event) => setForm({ ...form, unit_cost: event.target.value })} />
                <Input label="Date" type="date" value={form.count_date} error={fieldError(actionError, 'count_date')} onChange={(event) => setForm({ ...form, count_date: event.target.value })} />
                <Select label="Type" value={form.count_type} error={fieldError(actionError, 'count_type')} options={[{ value: 'stock_count', label: 'Stock count' }, { value: 'cycle_count', label: 'Cycle count' }]} onChange={(event) => setForm({ ...form, count_type: event.target.value })} />
                <div className="flex items-end"><Button type="submit" loading={busy}>Create</Button></div>
            </form>
            <RecordList rows={data} columns={countColumns((row) => (
                <div className="flex flex-wrap gap-2">
                    <Button variant="secondary" onClick={() => void action(() => approveStockCount(row.id), reload, setActionError)}>Approve</Button>
                    <Button variant="secondary" onClick={() => void action(() => postStockCount(row.id), reload, setActionError)}>Post</Button>
                </div>
            ))} />
        </WorkflowPanel>
    );
}

function CostingTab({ data, layers, layersLoading, layersError, loading, error, reload }: WorkflowProps & { layers: InventoryRecord[]; layersLoading: boolean; layersError: ApiError | null }) {
    const [form, setForm] = useState({ adjustment_date: today(), valuation_layer: '', adjustment_amount: '0.000000', reason: '' });
    const [busy, setBusy] = useState(false);
    const [actionError, setActionError] = useState<ApiError | null>(null);
    const submit = (event: FormEvent) => void runAction(event, setBusy, setActionError, async () => {
        await createCostAdjustment(compactObject({
            adjustment_date: form.adjustment_date,
            reason: form.reason,
            lines: [compactObject({
                valuation_layer_id: form.valuation_layer ? Number(form.valuation_layer) : 0,
                adjustment_amount: form.adjustment_amount,
                reason: form.reason,
            })],
        }) as CostAdjustmentPayload);
        reload();
    });

    return (
        <div className="space-y-5">
            <WorkflowPanel title="Inventory cost adjustments" loading={loading} error={error} actionError={actionError}>
                <form className="grid gap-4 xl:grid-cols-[1fr_10rem_1fr_1fr_auto]" onSubmit={submit}>
                    <Select
                        label="Valuation layer"
                        value={form.valuation_layer}
                        error={fieldError(actionError, 'lines.0.valuation_layer_id')}
                        options={layers.map((layer) => ({ value: String(layer.id), label: valuationLayerLabel(layer) }))}
                        onChange={(event) => setForm({ ...form, valuation_layer: event.target.value })}
                    />
                    <DecimalInput label="Adjustment" value={form.adjustment_amount} error={fieldError(actionError, 'lines.0.adjustment_amount')} onChange={(event) => setForm({ ...form, adjustment_amount: event.target.value })} />
                    <Input label="Reason" value={form.reason} error={fieldError(actionError, 'reason')} onChange={(event) => setForm({ ...form, reason: event.target.value })} />
                    <Input label="Date" type="date" value={form.adjustment_date} error={fieldError(actionError, 'adjustment_date')} onChange={(event) => setForm({ ...form, adjustment_date: event.target.value })} />
                    <div className="flex items-end"><Button type="submit" loading={busy} disabled={!form.valuation_layer}>Create</Button></div>
                </form>
                <RecordList rows={data} columns={costAdjustmentColumns((row) => (
                    <Button variant="secondary" onClick={() => void action(() => postCostAdjustment(row.id), reload, setActionError)} disabled={String(row.status ?? '') !== 'draft'}>Post</Button>
                ))} />
            </WorkflowPanel>
            <Panel title="Open valuation layers">
                <ErrorAlert error={layersError} />
                {layersLoading ? <LoadingState /> : <RecordList rows={layers} columns={valuationLayerColumns()} />}
            </Panel>
        </div>
    );
}

function TrackingTab({ batches, serials }: {
    batches: ApiResult<Awaited<ReturnType<typeof listBatches>>>;
    serials: ApiResult<Awaited<ReturnType<typeof listSerials>>>;
}) {
    return (
        <div className="grid gap-5 xl:grid-cols-2">
            <Panel title="Batches and lots">
                <ErrorAlert error={batches.error} />
                {batches.loading ? <LoadingState /> : <RecordList rows={batches.data?.data ?? []} columns={[
                    { key: 'batch', header: 'Batch', render: (row) => label(row, 'batch_number') },
                    { key: 'item', header: 'Item', render: (row) => relation(row.item) },
                    { key: 'expiry', header: 'Expiry', render: (row) => String(row.expiry_date ?? '-') },
                    { key: 'status', header: 'Status', render: (row) => <StatusBadge status={String(row.status ?? '')} /> },
                ]} />}
            </Panel>
            <Panel title="Serial tracking">
                <ErrorAlert error={serials.error} />
                {serials.loading ? <LoadingState /> : <RecordList rows={serials.data?.data ?? []} columns={[
                    { key: 'serial', header: 'Serial', render: (row) => label(row, 'serial_number') },
                    { key: 'item', header: 'Item', render: (row) => relation(row.item) },
                    { key: 'warehouse', header: 'Warehouse', render: (row) => relation(row.warehouse) },
                    { key: 'status', header: 'Status', render: (row) => <StatusBadge status={String(row.status ?? '')} /> },
                ]} />}
            </Panel>
        </div>
    );
}

function AuditTab({ states }: { states: ApiResult<Awaited<ReturnType<typeof listStateChanges>>> }) {
    return (
        <Panel title="State audit history">
            <ErrorAlert error={states.error} />
            {states.loading ? <LoadingState /> : (
                <RecordList rows={states.data?.data ?? []} columns={[
                    { key: 'item', header: 'Item', render: (row) => relation(row.item) },
                    { key: 'warehouse', header: 'Warehouse', render: (row) => relation(row.warehouse) },
                    { key: 'state', header: 'State', render: (row) => `${humanize(String(row.from_state ?? ''))} -> ${humanize(String(row.to_state ?? ''))}` },
                    { key: 'qty', header: 'Qty', render: (row) => q(row.quantity) },
                    { key: 'source', header: 'Source', render: (row) => humanize(String(row.source_type ?? '-')) },
                    { key: 'date', header: 'Changed', render: (row) => String(row.created_at ?? '-') },
                ]} />
            )}
        </Panel>
    );
}

function ReportsTab() {
    const reports = [
        ['Stock Balance Report', 'inventory.stock-balance'],
        ['Stock Movement Report', 'inventory.stock-movement'],
        ['Inventory Valuation Report', 'inventory.valuation'],
        ['Inventory Aging Report', 'inventory.aging'],
        ['Reservation Report', 'inventory.reservation'],
        ['Allocation Report', 'inventory.allocation'],
        ['Batch Expiry Report', 'inventory.batch-expiry'],
        ['Serial Tracking Report', 'inventory.serial'],
        ['Low Stock Report', 'inventory.low-stock'],
    ];
    return (
        <Panel title="Inventory reports">
            <div className="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                {reports.map(([title, key]) => (
                    <Link key={key} to={`/reports/${key}`} className="rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-800 hover:border-sky-300 hover:text-sky-700">
                        {title}
                    </Link>
                ))}
            </div>
        </Panel>
    );
}

function WorkflowPanel({ title, children, loading, error, actionError }: { title: string; children: ReactNode; loading: boolean; error: ApiError | null; actionError: ApiError | null }) {
    return (
        <Panel title={title}>
            <div className="space-y-4">
                <ErrorAlert error={error ?? actionError} />
                {children}
                {loading && <LoadingState />}
            </div>
        </Panel>
    );
}

interface WorkflowProps {
    data: InventoryRecord[];
    loading: boolean;
    error: ApiError | null;
    reload: () => void;
}

function BalanceTable({ rows }: { rows: InventoryRecord[] }) {
    return <RecordList rows={rows} columns={[
        { key: 'item', header: 'Item', render: (row) => relation(row.item) },
        { key: 'warehouse', header: 'Warehouse', render: (row) => relation(row.warehouse) },
        { key: 'on_hand', header: 'On hand', render: (row) => q(row.quantity_on_hand) },
        { key: 'reserved', header: 'Reserved', render: (row) => q(row.quantity_reserved) },
        { key: 'allocated', header: 'Allocated', render: (row) => q(row.quantity_allocated) },
        { key: 'available', header: 'Available', render: (row) => q(row.quantity_available) },
        { key: 'states', header: 'Held states', render: (row) => `${row.quantity_damaged ?? decimal} damaged, ${row.quantity_quarantine ?? decimal} quarantine` },
    ]} />;
}

function RecordList({ rows, columns }: { rows: InventoryRecord[]; columns: Array<{ key: string; header: string; render: (row: InventoryRecord) => ReactNode }> }) {
    return <div className="mt-4"><DataTable rows={rows} rowKey={(row) => row.id} columns={columns} rowBadge={(row) => row.status ? <StatusBadge status={String(row.status)} /> : null} /></div>;
}

function reservationColumns(actions: (row: InventoryRecord) => React.ReactNode) {
    return [
        { key: 'number', header: 'Reservation', render: (row: InventoryRecord) => label(row, 'reservation_number') },
        { key: 'item', header: 'Item', render: (row: InventoryRecord) => relation(row.item) },
        { key: 'warehouse', header: 'Warehouse', render: (row: InventoryRecord) => relation(row.warehouse) },
        { key: 'remaining', header: 'Remaining', render: (row: InventoryRecord) => q(row.quantity_remaining) },
        { key: 'status', header: 'Status', render: (row: InventoryRecord) => <StatusBadge status={String(row.status ?? '')} /> },
        { key: 'actions', header: '', render: actions },
    ];
}

function allocationColumns(actions: (row: InventoryRecord) => React.ReactNode) {
    return [
        { key: 'number', header: 'Allocation', render: (row: InventoryRecord) => label(row, 'allocation_number') },
        { key: 'item', header: 'Item', render: (row: InventoryRecord) => relation(row.item) },
        { key: 'warehouse', header: 'Warehouse', render: (row: InventoryRecord) => relation(row.warehouse) },
        { key: 'remaining', header: 'Remaining', render: (row: InventoryRecord) => q(row.quantity_remaining) },
        { key: 'issued', header: 'Issued', render: (row: InventoryRecord) => q(row.quantity_issued) },
        { key: 'status', header: 'Status', render: (row: InventoryRecord) => <StatusBadge status={String(row.status ?? '')} /> },
        { key: 'actions', header: '', render: actions },
    ];
}

function transferColumns(actions: (row: InventoryRecord) => React.ReactNode) {
    return [
        { key: 'number', header: 'Transfer', render: (row: InventoryRecord) => label(row, 'transfer_number') },
        { key: 'from', header: 'From', render: (row: InventoryRecord) => relation(row.from_warehouse) },
        { key: 'to', header: 'To', render: (row: InventoryRecord) => relation(row.to_warehouse) },
        { key: 'status', header: 'Status', render: (row: InventoryRecord) => <StatusBadge status={String(row.status ?? '')} /> },
        { key: 'actions', header: '', render: actions },
    ];
}

function countColumns(actions: (row: InventoryRecord) => React.ReactNode) {
    return [
        { key: 'number', header: 'Count', render: (row: InventoryRecord) => label(row, 'count_number') },
        { key: 'type', header: 'Type', render: (row: InventoryRecord) => humanize(String(row.count_type ?? '')) },
        { key: 'warehouse', header: 'Warehouse', render: (row: InventoryRecord) => relation(row.warehouse) },
        { key: 'status', header: 'Status', render: (row: InventoryRecord) => <StatusBadge status={String(row.status ?? '')} /> },
        { key: 'actions', header: '', render: actions },
    ];
}

function costAdjustmentColumns(actions: (row: InventoryRecord) => ReactNode) {
    return [
        { key: 'number', header: 'Adjustment', render: (row: InventoryRecord) => label(row, 'adjustment_number') },
        { key: 'date', header: 'Date', render: (row: InventoryRecord) => String(row.adjustment_date ?? '-') },
        { key: 'reason', header: 'Reason', render: (row: InventoryRecord) => String(row.reason ?? '-') },
        { key: 'status', header: 'Status', render: (row: InventoryRecord) => <StatusBadge status={String(row.status ?? '')} /> },
        { key: 'actions', header: '', render: actions },
    ];
}

function valuationLayerColumns() {
    return [
        { key: 'item', header: 'Item', render: (row: InventoryRecord) => relation(row.item) },
        { key: 'warehouse', header: 'Warehouse', render: (row: InventoryRecord) => relation(row.warehouse) },
        { key: 'remaining', header: 'Remaining', render: (row: InventoryRecord) => q(row.remaining_quantity) },
        { key: 'unit_cost', header: 'Unit cost', render: (row: InventoryRecord) => String(row.unit_cost ?? decimal) },
        { key: 'remaining_value', header: 'Remaining value', render: (row: InventoryRecord) => String(row.remaining_value ?? decimal) },
        { key: 'status', header: 'Status', render: (row: InventoryRecord) => <StatusBadge status={String(row.status ?? '')} /> },
    ];
}

function valuationLayerLabel(layer: InventoryRecord) {
    return `${relation(layer.item)} / ${relation(layer.warehouse)} / ${String(layer.remaining_quantity ?? decimal)} @ ${String(layer.unit_cost ?? decimal)}`;
}

async function runAction(event: FormEvent, setBusy: (value: boolean) => void, setError: (error: ApiError | null) => void, callback: () => Promise<void>) {
    event.preventDefault();
    await action(callback, undefined, setError, setBusy);
}

async function action(callback: () => Promise<unknown>, reload?: () => void, setError?: (error: ApiError | null) => void, setBusy?: (value: boolean) => void) {
    setBusy?.(true);
    setError?.(null);
    try {
        await callback();
        reload?.();
    } catch (requestError) {
        setError?.(toApiError(requestError));
    } finally {
        setBusy?.(false);
    }
}

function relation(value: unknown) {
    return readableRelation(value);
}

function label(row: InventoryRecord, key: string) {
    return String(row[key] ?? '-');
}

function q(value: unknown) {
    return <QuantityDisplay value={String(value ?? decimal)} />;
}

function sum(values: string[]) {
    return values.reduce((total, value) => {
        const left = decimalToScaledInt(total);
        const right = decimalToScaledInt(value);
        const next = left + right;
        const sign = next < 0n ? '-' : '';
        const absolute = next < 0n ? -next : next;
        return `${sign}${absolute / 1_000_000n}.${String(absolute % 1_000_000n).padStart(6, '0')}`;
    }, decimal);
}

function decimalToScaledInt(value: string) {
    const normalized = value.trim() || decimal;
    const sign = normalized.startsWith('-') ? -1n : 1n;
    const unsigned = normalized.replace(/^-/, '');
    const [whole = '0', fraction = ''] = unsigned.split('.');

    return sign * (BigInt(whole || '0') * 1_000_000n + BigInt(fraction.padEnd(6, '0').slice(0, 6) || '0'));
}
