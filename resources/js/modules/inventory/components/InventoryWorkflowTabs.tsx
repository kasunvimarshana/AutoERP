import { useState, type FormEvent, type ReactNode } from 'react';
import { Button } from '@/shared/components/Button';
import { DecimalInput } from '@/shared/components/DecimalInput';
import { Input } from '@/shared/components/Input';
import { LookupSelect } from '@/shared/components/LookupSelect';
import { Select } from '@/shared/components/Select';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { fieldError, type ApiError } from '@/shared/api/apiError';
import { lookupApi } from '@/shared/api/lookupApi';
import { searchWarehouses } from '@/shared/api/referenceApi';
import { compactObject, humanize } from '@/shared/utils/object';
import type { NamedResource } from '@/shared/types/common';
import {
    approveStockCount,
    cancelTransfer,
    createAllocation,
    createReservation,
    createStockCount,
    createTransfer,
    dispatchTransfer,
    issueAllocation,
    postStockCount,
    receiveTransfer,
    releaseAllocation,
    releaseReservation,
} from '../inventoryApi';
import type {
    AllocationPayload,
    InventoryRecord,
    ReservationPayload,
    StockCountPayload,
    TransferPayload,
} from '../inventoryTypes';
import {
    label,
    localToday,
    quantity,
    RecordList,
    relation,
    runFormAction,
    useRecordAction,
    type WorkflowProps,
    WorkflowPanel,
} from './inventoryUi';

export function ReservationsTab({ data, loading, error, reload }: WorkflowProps) {
    const [form, setForm] = useState({ reservation_date: localToday(), quantity_reserved: '1.000000', notes: '' });
    const [item, setItem] = useState<NamedResource | null>(null);
    const [warehouse, setWarehouse] = useState<NamedResource | null>(null);
    const [busy, setBusy] = useState(false);
    const [actionError, setActionError] = useState<ApiError | null>(null);
    const recordAction = useRecordAction(reload, setActionError);
    const submit = (event: FormEvent) => void runFormAction(event, setBusy, setActionError, async () => {
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
                <div className="flex items-end">
                    <Button type="submit" loading={busy} disabled={!item || !warehouse}>Reserve</Button>
                </div>
            </form>
            <RecordList rows={data} columns={reservationColumns((row) => {
                const key = `release-reservation:${row.id}`;
                const actionable = ['active', 'partially_allocated'].includes(String(row.status ?? ''));

                return (
                    <Button
                        variant="secondary"
                        loading={recordAction.pendingKey === key}
                        disabled={!actionable || recordAction.pendingKey !== null}
                        onClick={() => void recordAction.run(key, () => releaseReservation(row.id))}
                    >
                        Unreserve
                    </Button>
                );
            })} />
        </WorkflowPanel>
    );
}

export function AllocationsTab({
    data,
    reservations,
    loading,
    error,
    reload,
}: WorkflowProps & { reservations: InventoryRecord[] }) {
    const [form, setForm] = useState({ allocation_date: localToday(), quantity_allocated: '1.000000', reservation: '' });
    const [item, setItem] = useState<NamedResource | null>(null);
    const [warehouse, setWarehouse] = useState<NamedResource | null>(null);
    const [busy, setBusy] = useState(false);
    const [actionError, setActionError] = useState<ApiError | null>(null);
    const recordAction = useRecordAction(reload, setActionError);
    const submit = (event: FormEvent) => void runFormAction(event, setBusy, setActionError, async () => {
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
                <Select
                    label="Reservation"
                    value={form.reservation}
                    error={fieldError(actionError, 'reservation_id')}
                    options={reservations
                        .filter((row) => ['active', 'partially_allocated'].includes(String(row.status)))
                        .map((row) => ({
                            value: String(row.id),
                            label: `${label(row, 'reservation_number')} / ${relation(row.item)} / ${quantityText(row.quantity_remaining)}`,
                        }))}
                    onChange={(event) => setForm({ ...form, reservation: event.target.value })}
                />
                <div className="flex items-end">
                    <Button type="submit" loading={busy} disabled={!item || !warehouse}>Allocate</Button>
                </div>
            </form>
            <RecordList rows={data} columns={allocationColumns((row) => {
                const issueKey = `issue-allocation:${row.id}`;
                const releaseKey = `release-allocation:${row.id}`;
                const actionable = String(row.status ?? '') === 'active';

                return (
                    <div className="flex flex-wrap gap-2">
                        <Button
                            variant="secondary"
                            loading={recordAction.pendingKey === issueKey}
                            disabled={!actionable || recordAction.pendingKey !== null}
                            onClick={() => void recordAction.run(issueKey, () => issueAllocation(row.id))}
                        >
                            Issue
                        </Button>
                        <Button
                            variant="ghost"
                            loading={recordAction.pendingKey === releaseKey}
                            disabled={!actionable || recordAction.pendingKey !== null}
                            onClick={() => void recordAction.run(releaseKey, () => releaseAllocation(row.id))}
                        >
                            Release
                        </Button>
                    </div>
                );
            })} />
        </WorkflowPanel>
    );
}

export function TransfersTab({ data, loading, error, reload }: WorkflowProps) {
    const [form, setForm] = useState({ transfer_date: localToday(), quantity: '1.000000', unit_cost: '0.000000' });
    const [item, setItem] = useState<NamedResource | null>(null);
    const [fromWarehouse, setFromWarehouse] = useState<NamedResource | null>(null);
    const [toWarehouse, setToWarehouse] = useState<NamedResource | null>(null);
    const [busy, setBusy] = useState(false);
    const [actionError, setActionError] = useState<ApiError | null>(null);
    const recordAction = useRecordAction(reload, setActionError);
    const submit = (event: FormEvent) => void runFormAction(event, setBusy, setActionError, async () => {
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
                <div className="flex items-end">
                    <Button
                        type="submit"
                        loading={busy}
                        disabled={!item || !fromWarehouse || !toWarehouse || fromWarehouse.id === toWarehouse.id}
                    >
                        Create
                    </Button>
                </div>
            </form>
            <RecordList rows={data} columns={transferColumns((row) => {
                const status = String(row.status ?? '');
                const dispatchKey = `dispatch-transfer:${row.id}`;
                const receiveKey = `receive-transfer:${row.id}`;
                const cancelKey = `cancel-transfer:${row.id}`;
                const canDispatch = ['pending', 'draft', 'approved'].includes(status);
                const canReceive = ['dispatched', 'in_transit'].includes(status);

                return (
                    <div className="flex flex-wrap gap-2">
                        <Button
                            variant="secondary"
                            loading={recordAction.pendingKey === dispatchKey}
                            disabled={!canDispatch || recordAction.pendingKey !== null}
                            onClick={() => void recordAction.run(dispatchKey, () => dispatchTransfer(row.id))}
                        >
                            Dispatch
                        </Button>
                        <Button
                            variant="secondary"
                            loading={recordAction.pendingKey === receiveKey}
                            disabled={!canReceive || recordAction.pendingKey !== null}
                            onClick={() => void recordAction.run(receiveKey, () => receiveTransfer(row.id))}
                        >
                            Receive
                        </Button>
                        <Button
                            variant="ghost"
                            loading={recordAction.pendingKey === cancelKey}
                            disabled={!canDispatch || recordAction.pendingKey !== null}
                            onClick={() => void recordAction.run(cancelKey, () => cancelTransfer(row.id))}
                        >
                            Cancel
                        </Button>
                    </div>
                );
            })} />
        </WorkflowPanel>
    );
}

export function CountsTab({ data, loading, error, reload }: WorkflowProps) {
    const [form, setForm] = useState({
        count_date: localToday(),
        counted_quantity: '0.000000',
        system_quantity: '',
        unit_cost: '0.000000',
        count_type: 'stock_count',
    });
    const [item, setItem] = useState<NamedResource | null>(null);
    const [warehouse, setWarehouse] = useState<NamedResource | null>(null);
    const [busy, setBusy] = useState(false);
    const [actionError, setActionError] = useState<ApiError | null>(null);
    const recordAction = useRecordAction(reload, setActionError);
    const submit = (event: FormEvent) => void runFormAction(event, setBusy, setActionError, async () => {
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
                <div className="flex items-end">
                    <Button type="submit" loading={busy} disabled={!item || !warehouse}>Create</Button>
                </div>
            </form>
            <RecordList rows={data} columns={countColumns((row) => {
                const status = String(row.status ?? '');
                const approveKey = `approve-count:${row.id}`;
                const postKey = `post-count:${row.id}`;

                return (
                    <div className="flex flex-wrap gap-2">
                        <Button
                            variant="secondary"
                            loading={recordAction.pendingKey === approveKey}
                            disabled={status !== 'draft' || recordAction.pendingKey !== null}
                            onClick={() => void recordAction.run(approveKey, () => approveStockCount(row.id))}
                        >
                            Approve
                        </Button>
                        <Button
                            variant="secondary"
                            loading={recordAction.pendingKey === postKey}
                            disabled={!['draft', 'approved'].includes(status) || recordAction.pendingKey !== null}
                            onClick={() => void recordAction.run(postKey, () => postStockCount(row.id))}
                        >
                            Post
                        </Button>
                    </div>
                );
            })} />
        </WorkflowPanel>
    );
}

function reservationColumns(actions: (row: InventoryRecord) => ReactNode) {
    return [
        { key: 'number', header: 'Reservation', render: (row: InventoryRecord) => label(row, 'reservation_number') },
        { key: 'item', header: 'Item', render: (row: InventoryRecord) => relation(row.item) },
        { key: 'warehouse', header: 'Warehouse', render: (row: InventoryRecord) => relation(row.warehouse) },
        { key: 'remaining', header: 'Remaining', render: (row: InventoryRecord) => quantity(row.quantity_remaining) },
        { key: 'status', header: 'Status', render: (row: InventoryRecord) => <StatusBadge status={String(row.status ?? '')} /> },
        { key: 'actions', header: '', render: actions },
    ];
}

function allocationColumns(actions: (row: InventoryRecord) => ReactNode) {
    return [
        { key: 'number', header: 'Allocation', render: (row: InventoryRecord) => label(row, 'allocation_number') },
        { key: 'item', header: 'Item', render: (row: InventoryRecord) => relation(row.item) },
        { key: 'warehouse', header: 'Warehouse', render: (row: InventoryRecord) => relation(row.warehouse) },
        { key: 'remaining', header: 'Remaining', render: (row: InventoryRecord) => quantity(row.quantity_remaining) },
        { key: 'issued', header: 'Issued', render: (row: InventoryRecord) => quantity(row.quantity_issued) },
        { key: 'status', header: 'Status', render: (row: InventoryRecord) => <StatusBadge status={String(row.status ?? '')} /> },
        { key: 'actions', header: '', render: actions },
    ];
}

function transferColumns(actions: (row: InventoryRecord) => ReactNode) {
    return [
        { key: 'number', header: 'Transfer', render: (row: InventoryRecord) => label(row, 'transfer_number') },
        { key: 'from', header: 'From', render: (row: InventoryRecord) => relation(row.from_warehouse) },
        { key: 'to', header: 'To', render: (row: InventoryRecord) => relation(row.to_warehouse) },
        { key: 'status', header: 'Status', render: (row: InventoryRecord) => <StatusBadge status={String(row.status ?? '')} /> },
        { key: 'actions', header: '', render: actions },
    ];
}

function countColumns(actions: (row: InventoryRecord) => ReactNode) {
    return [
        { key: 'number', header: 'Count', render: (row: InventoryRecord) => label(row, 'count_number') },
        { key: 'type', header: 'Type', render: (row: InventoryRecord) => humanize(String(row.count_type ?? '')) },
        { key: 'warehouse', header: 'Warehouse', render: (row: InventoryRecord) => relation(row.warehouse) },
        { key: 'status', header: 'Status', render: (row: InventoryRecord) => <StatusBadge status={String(row.status ?? '')} /> },
        { key: 'actions', header: '', render: actions },
    ];
}

function quantityText(value: unknown) {
    return String(value ?? '0.000000');
}
