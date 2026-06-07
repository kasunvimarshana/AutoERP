import { useState } from 'react';
import { fieldError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { FormDrawer } from '@/shared/components/Drawer';
import { Pagination } from '@/shared/components/Pagination';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { createItemVariant, deleteItemVariant, listItemVariants, updateItemVariant } from '../itemApi';
import type { ItemVariant, ItemVariantPayload } from '../itemTypes';
import { ItemRelationHeader } from './ItemRelationHeader';
import { useItemRelationCrud } from './useItemRelationCrud';

const list = (itemId: number, page: number, signal: AbortSignal) => listItemVariants(itemId, { page, per_page: 20 }, signal);

export default function ItemVariantTab({ itemId }: { itemId: number }) {
    const crud = useItemRelationCrud({ itemId, list, create: createItemVariant, update: updateItemVariant, remove: deleteItemVariant });
    const columns: DataColumn<ItemVariant>[] = [
        { key: 'variant', header: 'Variant', render: (row) => <span className="font-medium">{row.name}<span className="block text-xs text-slate-500">{row.code}</span></span> },
        { key: 'sku', header: 'SKU', render: (row) => row.sku ?? '-' },
        { key: 'barcode', header: 'Barcode', render: (row) => row.barcode ?? '-' },
        { key: 'status', header: 'Status', render: (row) => <StatusBadge status={row.is_active ? 'active' : 'inactive'} /> },
        { key: 'actions', header: '', className: 'text-right', render: (row) => <Actions edit={() => crud.startEdit(row)} remove={() => crud.destroy(row)} /> },
    ];
    return (
        <>
            <ItemRelationHeader title="Variants" description="Item-specific identities such as size, color, or configuration." onAdd={crud.startCreate} />
            <ErrorAlert error={crud.actionError ?? crud.error} />
            {crud.loading ? <LoadingState /> : <DataTable rows={crud.data?.data ?? []} columns={columns} rowKey={(row) => row.id} />}
            <Pagination meta={crud.data?.meta} onPageChange={crud.setPage} />
            <FormDrawer open={crud.open} title={crud.editing ? 'Edit variant' : 'Add variant'} onClose={crud.close}>
                {crud.open && <VariantForm key={crud.editing?.id ?? 'new'} row={crud.editing} error={crud.actionError} submitting={crud.submitting} onCancel={crud.close} onSubmit={crud.submit} />}
            </FormDrawer>
            {crud.confirmDialog}
        </>
    );
}

function VariantForm({ row, error, submitting, onCancel, onSubmit }: {
    row: ItemVariant | null;
    error: ApiError | null;
    submitting: boolean;
    onCancel: () => void;
    onSubmit: (payload: ItemVariantPayload) => Promise<void>;
}) {
    const [form, setForm] = useState<ItemVariantPayload>({
        code: row?.code ?? '',
        name: row?.name ?? '',
        sku: row?.sku ?? null,
        barcode: row?.barcode ?? null,
        is_active: row?.is_active ?? true,
    });
    return <form className="space-y-4" onSubmit={(event) => { event.preventDefault(); void onSubmit(form); }}>
        <ErrorAlert error={error} />
        <div className="grid gap-4 sm:grid-cols-2">
            <Input label="Code" value={form.code} onChange={(event) => setForm({ ...form, code: event.target.value })} error={fieldError(error, 'code')} required />
            <Input label="Name" value={form.name} onChange={(event) => setForm({ ...form, name: event.target.value })} error={fieldError(error, 'name')} required />
            <Input label="SKU" value={form.sku ?? ''} onChange={(event) => setForm({ ...form, sku: event.target.value || null })} error={fieldError(error, 'sku')} />
            <Input label="Barcode" value={form.barcode ?? ''} onChange={(event) => setForm({ ...form, barcode: event.target.value || null })} error={fieldError(error, 'barcode')} />
        </div>
        <label className="block text-sm"><input className="mr-2" type="checkbox" checked={form.is_active} onChange={(event) => setForm({ ...form, is_active: event.target.checked })} />Active</label>
        <div className="flex justify-end gap-2"><Button type="button" variant="secondary" onClick={onCancel}>Cancel</Button><Button type="submit" loading={submitting}>Save</Button></div>
    </form>;
}

function Actions({ edit, remove }: { edit: () => void; remove: () => void }) {
    return <div className="flex justify-end gap-3"><button type="button" className="font-semibold text-sky-700" onClick={edit}>Edit</button><button type="button" className="font-semibold text-rose-600" onClick={remove}>Delete</button></div>;
}
