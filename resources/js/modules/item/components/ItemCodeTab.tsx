import { useState } from 'react';
import { fieldError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Modal } from '@/shared/components/Modal';
import { Pagination } from '@/shared/components/Pagination';
import { Select } from '@/shared/components/Select';
import { createItemCode, deleteItemCode, listItemCodes, updateItemCode } from '../itemApi';
import { itemCodeTypes, type ItemCode, type ItemCodePayload } from '../itemTypes';
import { ItemRelationHeader } from './ItemRelationHeader';
import { useItemRelationCrud } from './useItemRelationCrud';

const list = (itemId: number, page: number, signal: AbortSignal) => listItemCodes(itemId, { page, per_page: 20 }, signal);

export default function ItemCodeTab({ itemId }: { itemId: number }) {
    const crud = useItemRelationCrud({ itemId, list, create: createItemCode, update: updateItemCode, remove: deleteItemCode });
    const columns: DataColumn<ItemCode>[] = [
        { key: 'type', header: 'Code type', render: (row) => row.code_type },
        { key: 'code', header: 'Code', render: (row) => <span className="font-medium">{row.code}</span> },
        { key: 'variant', header: 'Variant', render: (row) => row.variant ? `${row.variant.code} - ${row.variant.name}` : 'All variants' },
        { key: 'primary', header: 'Primary', render: (row) => row.is_primary ? 'Yes' : 'No' },
        { key: 'actions', header: '', className: 'text-right', render: (row) => <Actions edit={() => crud.startEdit(row)} remove={() => crud.destroy(row)} /> },
    ];
    return <>
        <ItemRelationHeader title="Alternative codes" description="Maintain readable SKU, barcode, OEM, and party-facing references." onAdd={crud.startCreate} />
        <ErrorAlert error={crud.actionError ?? crud.error} />
        {crud.loading ? <LoadingState /> : <DataTable rows={crud.data?.data ?? []} columns={columns} rowKey={(row) => row.id} />}
        <Pagination meta={crud.data?.meta} onPageChange={crud.setPage} />
        <Modal open={crud.open} title={crud.editing ? 'Edit code' : 'Add code'} onClose={crud.close}>
            {crud.open && <CodeForm key={crud.editing?.id ?? 'new'} row={crud.editing} error={crud.actionError} submitting={crud.submitting} onCancel={crud.close} onSubmit={crud.submit} />}
        </Modal>
    </>;
}

function CodeForm({ row, error, submitting, onCancel, onSubmit }: {
    row: ItemCode | null;
    error: ApiError | null;
    submitting: boolean;
    onCancel: () => void;
    onSubmit: (payload: ItemCodePayload) => Promise<void>;
}) {
    const [form, setForm] = useState<ItemCodePayload>({
        code_type: row?.code_type ?? 'internal_code',
        code: row?.code ?? '',
        party_type: row?.party_type ?? null,
        is_primary: row?.is_primary ?? false,
    });
    return <form className="space-y-4" onSubmit={(event) => { event.preventDefault(); void onSubmit(form); }}>
        <ErrorAlert error={error} />
        <Select label="Code type" value={form.code_type} onChange={(event) => setForm({ ...form, code_type: event.target.value })} options={itemCodeTypes.map((value) => ({ value, label: value.replaceAll('_', ' ') }))} error={fieldError(error, 'code_type')} />
        <Input label="Code" value={form.code} onChange={(event) => setForm({ ...form, code: event.target.value })} error={fieldError(error, 'code')} required />
        <label className="block text-sm"><input className="mr-2" type="checkbox" checked={form.is_primary} onChange={(event) => setForm({ ...form, is_primary: event.target.checked })} />Primary code</label>
        <div className="flex justify-end gap-2"><Button type="button" variant="secondary" onClick={onCancel}>Cancel</Button><Button type="submit" loading={submitting}>Save</Button></div>
    </form>;
}

function Actions({ edit, remove }: { edit: () => void; remove: () => void }) {
    return <div className="flex justify-end gap-3"><button type="button" className="font-semibold text-sky-700" onClick={edit}>Edit</button><button type="button" className="font-semibold text-rose-600" onClick={remove}>Delete</button></div>;
}
