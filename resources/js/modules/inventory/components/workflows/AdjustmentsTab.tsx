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
import { compactObject } from '@/shared/utils/object';
import type { NamedResource } from '@/shared/types/common';
import { createAdjustment, postAdjustment } from '../../inventoryApi';
import type { AdjustmentPayload, InventoryRecord } from '../../inventoryTypes';
import {
    label,
    localToday,
    RecordList,
    relation,
    runFormAction,
    subtractDecimals,
    useRecordAction,
    type WorkflowProps,
    WorkflowPanel,
} from '../inventoryUi';

export function AdjustmentsTab({ data, loading, error, reload }: WorkflowProps) {
    const [form, setForm] = useState({
        adjustment_date: localToday(),
        adjustment_type: 'recount',
        system_quantity: '0.000000',
        counted_quantity: '0.000000',
        unit_cost: '0.000000',
        reason: '',
    });
    const [item, setItem] = useState<NamedResource | null>(null);
    const [warehouse, setWarehouse] = useState<NamedResource | null>(null);
    const [busy, setBusy] = useState(false);
    const [actionError, setActionError] = useState<ApiError | null>(null);
    const recordAction = useRecordAction(reload, setActionError);
    const submit = (event: FormEvent) => void runFormAction(event, setBusy, setActionError, async () => {
        await createAdjustment(compactObject({
            adjustment_date: form.adjustment_date,
            adjustment_type: form.adjustment_type,
            warehouse_id: warehouse?.id ?? 0,
            reason: form.reason,
            lines: [{
                item_id: item?.id ?? 0,
                system_quantity: form.system_quantity,
                counted_quantity: form.counted_quantity,
                adjustment_quantity: subtractDecimals(form.counted_quantity, form.system_quantity),
                unit_cost: form.unit_cost,
                reason: form.reason,
            }],
        }) as AdjustmentPayload);
        reload();
    });

    return (
        <WorkflowPanel title="Stock adjustment workflow" loading={loading} error={error} actionError={actionError}>
            <form className="grid gap-4 xl:grid-cols-[1fr_1fr_10rem_9rem_9rem_9rem_1fr_1fr_auto]" onSubmit={submit}>
                <LookupSelect label="Item" value={item} onChange={setItem} search={lookupApi.stockableItems} error={fieldError(actionError, 'lines.0.item_id')} />
                <LookupSelect label="Warehouse" value={warehouse} onChange={setWarehouse} search={searchWarehouses} error={fieldError(actionError, 'warehouse_id')} loadOnOpen minSearchLength={0} />
                <Select
                    label="Type"
                    value={form.adjustment_type}
                    options={['recount', 'increase', 'decrease', 'damage', 'expiry', 'opening_balance'].map((value) => ({ value, label: value.replaceAll('_', ' ') }))}
                    onChange={(event) => setForm({ ...form, adjustment_type: event.target.value })}
                />
                <DecimalInput label="System (base)" value={form.system_quantity} error={fieldError(actionError, 'lines.0.system_quantity')} onChange={(event) => setForm({ ...form, system_quantity: event.target.value })} />
                <DecimalInput label="Counted (base)" value={form.counted_quantity} error={fieldError(actionError, 'lines.0.counted_quantity')} onChange={(event) => setForm({ ...form, counted_quantity: event.target.value })} />
                <DecimalInput label="Cost/base" value={form.unit_cost} error={fieldError(actionError, 'lines.0.unit_cost')} onChange={(event) => setForm({ ...form, unit_cost: event.target.value })} />
                <Input label="Reason" value={form.reason} error={fieldError(actionError, 'reason')} onChange={(event) => setForm({ ...form, reason: event.target.value })} />
                <Input label="Date" type="date" value={form.adjustment_date} error={fieldError(actionError, 'adjustment_date')} onChange={(event) => setForm({ ...form, adjustment_date: event.target.value })} />
                <div className="flex items-end"><Button type="submit" loading={busy} disabled={!item || !warehouse}>Create</Button></div>
            </form>
            <RecordList rows={data} columns={columns((row) => {
                const key = `post-adjustment:${row.id}`;

                return (
                    <Button
                        variant="secondary"
                        loading={recordAction.pendingKey === key}
                        disabled={!['draft', 'approved'].includes(String(row.status ?? '')) || recordAction.pendingKey !== null}
                        onClick={() => void recordAction.run(key, () => postAdjustment(row.id))}
                    >
                        Post
                    </Button>
                );
            })} />
        </WorkflowPanel>
    );
}

function columns(actions: (row: InventoryRecord) => ReactNode) {
    return [
        { key: 'number', header: 'Adjustment', render: (row: InventoryRecord) => label(row, 'adjustment_number') },
        { key: 'type', header: 'Type', render: (row: InventoryRecord) => String(row.adjustment_type ?? '-') },
        { key: 'warehouse', header: 'Warehouse', render: (row: InventoryRecord) => relation(row.warehouse) },
        { key: 'status', header: 'Status', render: (row: InventoryRecord) => <StatusBadge status={String(row.status ?? '')} /> },
        { key: 'actions', header: '', render: actions },
    ];
}
