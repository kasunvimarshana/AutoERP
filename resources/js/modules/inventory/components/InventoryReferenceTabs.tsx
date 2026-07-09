import { useState, type FormEvent, type ReactNode } from 'react';
import { Link } from 'react-router-dom';
import { Button } from '@/shared/components/Button';
import { DecimalInput } from '@/shared/components/DecimalInput';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Panel } from '@/shared/components/Panel';
import { Select } from '@/shared/components/Select';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { fieldError, type ApiError } from '@/shared/api/apiError';
import { compactObject, humanize } from '@/shared/utils/object';
import {
    createCostAdjustment,
    listBatches,
    listSerials,
    listStateChanges,
    postCostAdjustment,
} from '../inventoryApi';
import type { CostAdjustmentPayload, InventoryRecord } from '../inventoryTypes';
import {
    type ApiResult,
    label,
    localToday,
    quantity,
    RecordList,
    relation,
    runFormAction,
    useRecordAction,
    type WorkflowProps,
    WorkflowPanel,
    zeroDecimal,
} from './inventoryUi';

export function CostingTab({
    data,
    layers,
    layersLoading,
    layersError,
    loading,
    error,
    reload,
    canManage,
    canPost,
}: WorkflowProps & {
    layers: InventoryRecord[];
    layersLoading: boolean;
    layersError: ApiError | null;
    canManage: boolean;
    canPost: boolean;
}) {
    const [form, setForm] = useState({
        adjustment_date: localToday(),
        valuation_layer: '',
        adjustment_amount: '0.000000',
        reason: '',
    });
    const [busy, setBusy] = useState(false);
    const [actionError, setActionError] = useState<ApiError | null>(null);
    const recordAction = useRecordAction(reload, setActionError);
    const submit = (event: FormEvent) => void runFormAction(event, setBusy, setActionError, async () => {
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
                {canManage && (
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
                        <div className="flex items-end">
                            <Button type="submit" loading={busy} disabled={!form.valuation_layer}>Create</Button>
                        </div>
                    </form>
                )}
                <RecordList rows={data} columns={costAdjustmentColumns((row) => {
                    if (!canPost) return null;

                    const key = `post-cost-adjustment:${row.id}`;

                    return (
                        <Button
                            variant="secondary"
                            loading={recordAction.pendingKey === key}
                            disabled={String(row.status ?? '') !== 'draft' || recordAction.pendingKey !== null}
                            onClick={() => void recordAction.run(key, () => postCostAdjustment(row.id))}
                        >
                            Post
                        </Button>
                    );
                })} />
            </WorkflowPanel>
            <Panel title="Open valuation layers">
                <ErrorAlert error={layersError} />
                {layersLoading ? <LoadingState /> : <RecordList rows={layers} columns={valuationLayerColumns()} />}
            </Panel>
        </div>
    );
}

export function TrackingTab({
    batches,
    serials,
}: {
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
                    { key: 'location', header: 'Location', render: (row) => relation(row.warehouse_location) },
                    { key: 'status', header: 'Status', render: (row) => <StatusBadge status={String(row.status ?? '')} /> },
                ]} />}
            </Panel>
        </div>
    );
}

export function AuditTab({ states }: { states: ApiResult<Awaited<ReturnType<typeof listStateChanges>>> }) {
    return (
        <Panel title="State audit history">
            <ErrorAlert error={states.error} />
            {states.loading ? <LoadingState /> : (
                <RecordList rows={states.data?.data ?? []} columns={[
                    { key: 'item', header: 'Item', render: (row) => relation(row.item) },
                    { key: 'warehouse', header: 'Warehouse', render: (row) => relation(row.warehouse) },
                    { key: 'location', header: 'Location', render: (row) => relation(row.warehouse_location) },
                    { key: 'state', header: 'State', render: (row) => `${humanize(String(row.from_state ?? ''))} -> ${humanize(String(row.to_state ?? ''))}` },
                    { key: 'qty', header: 'Qty', render: (row) => quantity(row.quantity) },
                    { key: 'source', header: 'Source', render: (row) => humanize(String(row.source_type ?? '-')) },
                    { key: 'date', header: 'Changed', render: (row) => String(row.occurred_at ?? row.created_at ?? '-') },
                ]} />
            )}
        </Panel>
    );
}

export function ReportsTab() {
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
                    <Link
                        key={key}
                        to={`/reports/${key}`}
                        className="rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-800 hover:border-sky-300 hover:text-sky-700"
                    >
                        {title}
                    </Link>
                ))}
            </div>
        </Panel>
    );
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
        { key: 'remaining', header: 'Remaining', render: (row: InventoryRecord) => quantity(row.remaining_quantity) },
        { key: 'unit_cost', header: 'Unit cost', render: (row: InventoryRecord) => String(row.unit_cost ?? zeroDecimal) },
        { key: 'remaining_value', header: 'Remaining value', render: (row: InventoryRecord) => String(row.remaining_value ?? zeroDecimal) },
        { key: 'status', header: 'Status', render: (row: InventoryRecord) => <StatusBadge status={String(row.status ?? '')} /> },
    ];
}

function valuationLayerLabel(layer: InventoryRecord) {
    return `${relation(layer.item)} / ${relation(layer.warehouse)} / ${String(layer.remaining_quantity ?? zeroDecimal)} @ ${String(layer.unit_cost ?? zeroDecimal)}`;
}
