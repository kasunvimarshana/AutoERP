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
import { approveStockCount, createStockCount, postStockCount } from '../../inventoryApi';
import type { InventoryRecord, StockCountPayload } from '../../inventoryTypes';
import {
    label,
    localToday,
    RecordList,
    relation,
    runFormAction,
    useRecordAction,
    type WorkflowProps,
    WorkflowPanel,
} from '../inventoryUi';

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
                <DecimalInput label="Counted (base)" value={form.counted_quantity} error={fieldError(actionError, 'lines.0.counted_quantity')} onChange={(event) => setForm({ ...form, counted_quantity: event.target.value })} />
                <DecimalInput label="System (base)" value={form.system_quantity} error={fieldError(actionError, 'lines.0.system_quantity')} onChange={(event) => setForm({ ...form, system_quantity: event.target.value })} />
                <DecimalInput label="Cost/base" value={form.unit_cost} error={fieldError(actionError, 'lines.0.unit_cost')} onChange={(event) => setForm({ ...form, unit_cost: event.target.value })} />
                <Input label="Date" type="date" value={form.count_date} error={fieldError(actionError, 'count_date')} onChange={(event) => setForm({ ...form, count_date: event.target.value })} />
                <Select label="Type" value={form.count_type} error={fieldError(actionError, 'count_type')} options={[{ value: 'stock_count', label: 'Stock count' }, { value: 'cycle_count', label: 'Cycle count' }]} onChange={(event) => setForm({ ...form, count_type: event.target.value })} />
                <div className="flex items-end"><Button type="submit" loading={busy} disabled={!item || !warehouse}>Create</Button></div>
            </form>
            <RecordList rows={data} columns={columns((row) => {
                const status = String(row.status ?? '');
                const approveKey = `approve-count:${row.id}`;
                const postKey = `post-count:${row.id}`;

                return (
                    <div className="flex flex-wrap gap-2">
                        <Button variant="secondary" loading={recordAction.pendingKey === approveKey} disabled={status !== 'draft' || recordAction.pendingKey !== null} onClick={() => void recordAction.run(approveKey, () => approveStockCount(row.id))}>Approve</Button>
                        <Button variant="secondary" loading={recordAction.pendingKey === postKey} disabled={!['draft', 'approved'].includes(status) || recordAction.pendingKey !== null} onClick={() => void recordAction.run(postKey, () => postStockCount(row.id))}>Post</Button>
                    </div>
                );
            })} />
        </WorkflowPanel>
    );
}

function columns(actions: (row: InventoryRecord) => ReactNode) {
    return [
        { key: 'number', header: 'Count', render: (row: InventoryRecord) => label(row, 'count_number') },
        { key: 'type', header: 'Type', render: (row: InventoryRecord) => humanize(String(row.count_type ?? '')) },
        { key: 'warehouse', header: 'Warehouse', render: (row: InventoryRecord) => relation(row.warehouse) },
        { key: 'status', header: 'Status', render: (row: InventoryRecord) => <StatusBadge status={String(row.status ?? '')} /> },
        { key: 'actions', header: '', render: actions },
    ];
}
