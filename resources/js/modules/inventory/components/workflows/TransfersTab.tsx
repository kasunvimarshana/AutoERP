import { useState, type FormEvent, type ReactNode } from 'react';
import { Button } from '@/shared/components/Button';
import { DecimalInput } from '@/shared/components/DecimalInput';
import { Input } from '@/shared/components/Input';
import { LookupSelect } from '@/shared/components/LookupSelect';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { fieldError, type ApiError } from '@/shared/api/apiError';
import { lookupApi } from '@/shared/api/lookupApi';
import { searchWarehouses } from '@/shared/api/referenceApi';
import { compactObject } from '@/shared/utils/object';
import type { NamedResource } from '@/shared/types/common';
import { cancelTransfer, createTransfer, dispatchTransfer, receiveTransfer } from '../../inventoryApi';
import type { InventoryRecord, TransferPayload } from '../../inventoryTypes';
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
                <LookupSelect label="From warehouse" value={fromWarehouse} onChange={setFromWarehouse} search={searchWarehouses} error={fieldError(actionError, 'from_warehouse_id')} loadOnOpen minSearchLength={0} />
                <LookupSelect label="To warehouse" value={toWarehouse} onChange={setToWarehouse} search={searchWarehouses} error={fieldError(actionError, 'to_warehouse_id')} loadOnOpen minSearchLength={0} />
                <DecimalInput label="Qty (base)" value={form.quantity} error={fieldError(actionError, 'lines.0.quantity')} onChange={(event) => setForm({ ...form, quantity: event.target.value })} />
                <DecimalInput label="Cost/base" value={form.unit_cost} error={fieldError(actionError, 'lines.0.unit_cost')} onChange={(event) => setForm({ ...form, unit_cost: event.target.value })} />
                <Input label="Date" type="date" value={form.transfer_date} error={fieldError(actionError, 'transfer_date')} onChange={(event) => setForm({ ...form, transfer_date: event.target.value })} />
                <div className="flex items-end">
                    <Button type="submit" loading={busy} disabled={!item || !fromWarehouse || !toWarehouse || fromWarehouse.id === toWarehouse.id}>Create</Button>
                </div>
            </form>
            <RecordList rows={data} columns={columns((row) => {
                const status = String(row.status ?? '');
                const dispatchKey = `dispatch-transfer:${row.id}`;
                const receiveKey = `receive-transfer:${row.id}`;
                const cancelKey = `cancel-transfer:${row.id}`;
                const canDispatch = ['pending', 'draft', 'approved'].includes(status);
                const canReceive = ['dispatched', 'in_transit'].includes(status);

                return (
                    <div className="flex flex-wrap gap-2">
                        <Button variant="secondary" loading={recordAction.pendingKey === dispatchKey} disabled={!canDispatch || recordAction.pendingKey !== null} onClick={() => void recordAction.run(dispatchKey, () => dispatchTransfer(row.id))}>Dispatch</Button>
                        <Button variant="secondary" loading={recordAction.pendingKey === receiveKey} disabled={!canReceive || recordAction.pendingKey !== null} onClick={() => void recordAction.run(receiveKey, () => receiveTransfer(row.id))}>Receive</Button>
                        <Button variant="ghost" loading={recordAction.pendingKey === cancelKey} disabled={!canDispatch || recordAction.pendingKey !== null} onClick={() => void recordAction.run(cancelKey, () => cancelTransfer(row.id))}>Cancel</Button>
                    </div>
                );
            })} />
        </WorkflowPanel>
    );
}

function columns(actions: (row: InventoryRecord) => ReactNode) {
    return [
        { key: 'number', header: 'Transfer', render: (row: InventoryRecord) => label(row, 'transfer_number') },
        { key: 'from', header: 'From', render: (row: InventoryRecord) => relation(row.from_warehouse) },
        { key: 'to', header: 'To', render: (row: InventoryRecord) => relation(row.to_warehouse) },
        { key: 'status', header: 'Status', render: (row: InventoryRecord) => <StatusBadge status={String(row.status ?? '')} /> },
        { key: 'actions', header: '', render: actions },
    ];
}
