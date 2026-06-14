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
import { createReservation, releaseReservation } from '../../inventoryApi';
import type { InventoryRecord, ReservationPayload } from '../../inventoryTypes';
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
                <DecimalInput label="Quantity (base)" value={form.quantity_reserved} error={fieldError(actionError, 'quantity_reserved')} onChange={(event) => setForm({ ...form, quantity_reserved: event.target.value })} />
                <Input label="Date" type="date" value={form.reservation_date} error={fieldError(actionError, 'reservation_date')} onChange={(event) => setForm({ ...form, reservation_date: event.target.value })} />
                <div className="flex items-end"><Button type="submit" loading={busy} disabled={!item || !warehouse}>Reserve</Button></div>
            </form>
            <RecordList rows={data} columns={columns((row) => {
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

function columns(actions: (row: InventoryRecord) => ReactNode) {
    return [
        { key: 'number', header: 'Reservation', render: (row: InventoryRecord) => label(row, 'reservation_number') },
        { key: 'item', header: 'Item', render: (row: InventoryRecord) => relation(row.item) },
        { key: 'warehouse', header: 'Warehouse', render: (row: InventoryRecord) => relation(row.warehouse) },
        { key: 'remaining', header: 'Remaining (base)', render: (row: InventoryRecord) => quantity(row.quantity_remaining) },
        { key: 'status', header: 'Status', render: (row: InventoryRecord) => <StatusBadge status={String(row.status ?? '')} /> },
        { key: 'actions', header: '', render: actions },
    ];
}
