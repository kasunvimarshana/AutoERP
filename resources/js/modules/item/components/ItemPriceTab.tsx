import { useState } from 'react';
import { fieldError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { DecimalInput } from '@/shared/components/DecimalInput';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { FormDrawer } from '@/shared/components/Drawer';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Pagination } from '@/shared/components/Pagination';
import { Select } from '@/shared/components/Select';
import { StatusBadge } from '@/shared/components/StatusBadge';
import type { NamedResource } from '@/shared/types/common';
import { createItemPrice, deleteItemPrice, listItemPrices, updateItemPrice } from '../itemApi';
import { itemPriceTypes, type ItemPrice, type ItemPricePayload } from '../itemTypes';
import { ItemRelationHeader } from './ItemRelationHeader';
import { ItemUomSelect } from './ItemUomSelect';
import { useItemRelationCrud } from './useItemRelationCrud';

const list = (itemId: number, page: number, signal: AbortSignal) => listItemPrices(itemId, { page, per_page: 20 }, signal);

export default function ItemPriceTab({ itemId, readOnly = false }: { itemId: number; readOnly?: boolean }) {
    const crud = useItemRelationCrud({ itemId, list, create: createItemPrice, update: updateItemPrice, remove: deleteItemPrice });
    const columns: DataColumn<ItemPrice>[] = [
        { key: 'type', header: 'Price type', render: (row) => row.price_type },
        { key: 'amount', header: 'Amount', render: (row) => `${row.currency?.code ? `${row.currency.code} ` : ''}${row.amount}` },
        { key: 'uom', header: 'UOM', render: (row) => row.uom ? `${row.uom.code} - ${row.uom.name}` : '-' },
        { key: 'effective', header: 'Effective', render: (row) => `${row.effective_from ?? 'Any'} to ${row.effective_to ?? 'Open'}` },
        { key: 'status', header: 'Status', render: (row) => <StatusBadge status={row.is_active ? 'active' : 'inactive'} /> },
    ];
    if (!readOnly) {
        columns.push({ key: 'actions', header: '', className: 'text-right', render: (row) => <Actions edit={() => crud.startEdit(row)} remove={() => crud.destroy(row)} /> });
    }
    return <>
        <ItemRelationHeader title="Specific Prices" description="Maintain contextual purchase, sales, service, and rental prices." onAdd={readOnly ? undefined : crud.startCreate} />
        <ErrorAlert error={crud.actionError ?? crud.error} />
        {crud.loading ? <LoadingState /> : <DataTable rows={crud.data?.data ?? []} columns={columns} rowKey={(row) => row.id} mobileSummary={(row) => `${row.price_type}: ${row.amount}`} mobileDetails={(row) => <div className="grid grid-cols-2 gap-2 text-sm"><span>UOM: {row.uom?.code ?? '-'}</span><span>{row.is_active ? 'Active' : 'Inactive'}</span></div>} />}
        <Pagination meta={crud.data?.meta} onPageChange={crud.setPage} />
        {!readOnly && <FormDrawer open={crud.open} title={crud.editing ? 'Edit price' : 'Add price'} onClose={crud.close}>
            {crud.open && <PriceForm key={crud.editing?.id ?? 'new'} row={crud.editing} error={crud.actionError} submitting={crud.submitting} onCancel={crud.close} onSubmit={crud.submit} />}
        </FormDrawer>}
        {!readOnly && crud.confirmDialog}
    </>;
}

function PriceForm({ row, error, submitting, onCancel, onSubmit }: {
    row: ItemPrice | null;
    error: ApiError | null;
    submitting: boolean;
    onCancel: () => void;
    onSubmit: (payload: ItemPricePayload) => Promise<void>;
}) {
    const [uom, setUom] = useState<NamedResource | null>(row?.uom ?? null);
    const [form, setForm] = useState<ItemPricePayload>({
        price_type: row?.price_type ?? 'sales',
        amount: row?.amount ?? '0.000000',
        effective_from: row?.effective_from ?? null,
        effective_to: row?.effective_to ?? null,
        is_active: row?.is_active ?? true,
    });
    return <form className="space-y-4" onSubmit={(event) => {
        event.preventDefault();
        void onSubmit({ ...form, uom_id: uom ? Number(uom.id) : null });
    }}>
        <ErrorAlert error={error} />
        <div className="grid gap-4 sm:grid-cols-2">
            <Select label="Price type" value={form.price_type} onChange={(event) => setForm({ ...form, price_type: event.target.value })} options={itemPriceTypes.map((value) => ({ value, label: value }))} error={fieldError(error, 'price_type')} />
            <DecimalInput label="Amount" value={form.amount} onChange={(event) => setForm({ ...form, amount: event.target.value })} error={fieldError(error, 'amount')} required />
            <ItemUomSelect value={uom} onChange={setUom} error={fieldError(error, 'uom_id')} />
            <Input label="Effective from" type="date" value={form.effective_from ?? ''} onChange={(event) => setForm({ ...form, effective_from: event.target.value || null })} />
            <Input label="Effective to" type="date" value={form.effective_to ?? ''} onChange={(event) => setForm({ ...form, effective_to: event.target.value || null })} error={fieldError(error, 'effective_to')} />
        </div>
        <label className="block text-sm"><input className="mr-2" type="checkbox" checked={form.is_active} onChange={(event) => setForm({ ...form, is_active: event.target.checked })} />Active</label>
        <div className="flex justify-end gap-2"><Button type="button" variant="secondary" onClick={onCancel}>Cancel</Button><Button type="submit" loading={submitting}>Save</Button></div>
    </form>;
}

function Actions({ edit, remove }: { edit: () => void; remove: () => void }) {
    return <div className="flex justify-end gap-3"><button type="button" className="font-semibold text-sky-700" onClick={edit}>Edit</button><button type="button" className="font-semibold text-rose-600" onClick={remove}>Delete</button></div>;
}
