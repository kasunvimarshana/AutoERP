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
import { createAllocation, issueAllocation, releaseAllocation } from '../../inventoryApi';
import type { AllocationPayload, InventoryRecord } from '../../inventoryTypes';
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
} from '../inventoryUi';

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
                <LookupSelect label="Warehouse" value={warehouse} onChange={setWarehouse} search={searchWarehouses} error={fieldError(actionError, 'warehouse_id')} loadOnOpen minSearchLength={0} />
                <DecimalInput label="Quantity (base)" value={form.quantity_allocated} error={fieldError(actionError, 'quantity_allocated')} onChange={(event) => setForm({ ...form, quantity_allocated: event.target.value })} />
                <Input label="Date" type="date" value={form.allocation_date} error={fieldError(actionError, 'allocation_date')} onChange={(event) => setForm({ ...form, allocation_date: event.target.value })} />
                <Select
                    label="Reservation"
                    value={form.reservation}
                    error={fieldError(actionError, 'reservation_id')}
                    options={reservations
                        .filter((row) => ['active', 'partially_allocated'].includes(String(row.status)))
                        .map((row) => ({
                            value: String(row.id),
                            label: `${label(row, 'reservation_number')} / ${relation(row.item)} / ${String(row.quantity_remaining ?? '0.000000')}`,
                        }))}
                    onChange={(event) => setForm({ ...form, reservation: event.target.value })}
                />
                <div className="flex items-end"><Button type="submit" loading={busy} disabled={!item || !warehouse}>Allocate</Button></div>
            </form>
            <RecordList rows={data} columns={columns((row) => {
                const issueKey = `issue-allocation:${row.id}`;
                const releaseKey = `release-allocation:${row.id}`;
                const actionable = String(row.status ?? '') === 'active';

                return (
                    <div className="flex flex-wrap gap-2">
                        <Button variant="secondary" loading={recordAction.pendingKey === issueKey} disabled={!actionable || recordAction.pendingKey !== null} onClick={() => void recordAction.run(issueKey, () => issueAllocation(row.id))}>Issue</Button>
                        <Button variant="ghost" loading={recordAction.pendingKey === releaseKey} disabled={!actionable || recordAction.pendingKey !== null} onClick={() => void recordAction.run(releaseKey, () => releaseAllocation(row.id))}>Release</Button>
                    </div>
                );
            })} />
        </WorkflowPanel>
    );
}

function columns(actions: (row: InventoryRecord) => ReactNode) {
    return [
        { key: 'number', header: 'Allocation', render: (row: InventoryRecord) => label(row, 'allocation_number') },
        { key: 'item', header: 'Item', render: (row: InventoryRecord) => relation(row.item) },
        { key: 'warehouse', header: 'Warehouse', render: (row: InventoryRecord) => relation(row.warehouse) },
        { key: 'remaining', header: 'Remaining (base)', render: (row: InventoryRecord) => quantity(row.quantity_remaining) },
        { key: 'issued', header: 'Issued (base)', render: (row: InventoryRecord) => quantity(row.quantity_issued) },
        { key: 'status', header: 'Status', render: (row: InventoryRecord) => <StatusBadge status={String(row.status ?? '')} /> },
        { key: 'actions', header: '', render: actions },
    ];
}
