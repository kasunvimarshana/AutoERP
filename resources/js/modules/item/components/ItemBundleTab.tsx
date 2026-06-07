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
import type { NamedResource } from '@/shared/types/common';
import { createItemBundle, deleteItemBundle, listItemBundles, updateItemBundle } from '../itemApi';
import { bundleLineTypes, type ItemBundle, type ItemBundlePayload, type ItemSummary } from '../itemTypes';
import { ItemLookupSelect } from './ItemLookupSelect';
import { ItemRelationHeader } from './ItemRelationHeader';
import { ItemUomSelect } from './ItemUomSelect';
import { useItemRelationCrud } from './useItemRelationCrud';

const list = (itemId: number, page: number, signal: AbortSignal) => listItemBundles(itemId, { page, per_page: 20 }, signal);

export default function ItemBundleTab({ itemId, canBundle }: { itemId: number; canBundle: boolean }) {
    const crud = useItemRelationCrud({ itemId, list, create: createItemBundle, update: updateItemBundle, remove: deleteItemBundle });
    const columns: DataColumn<ItemBundle>[] = [
        { key: 'child', header: 'Item', render: (row) => row.child_item ? `${row.child_item.code} - ${row.child_item.name}` : '-' },
        { key: 'quantity', header: 'Quantity', render: (row) => row.quantity },
        { key: 'uom', header: 'UOM', render: (row) => row.uom ? `${row.uom.code} - ${row.uom.name}` : '-' },
        { key: 'actions', header: '', className: 'text-right', render: (row) => <Actions edit={() => crud.startEdit(row)} remove={() => crud.destroy(row)} /> },
    ];
    return (
        <>
            <ItemRelationHeader title="Bundle composition" description={canBundle ? 'Define readable child item composition.' : 'Only combo or package items can own bundle lines.'} onAdd={crud.startCreate} disabled={!canBundle} />
            {!canBundle && <p className="mb-4 rounded-lg bg-amber-50 p-3 text-sm text-amber-800">Change the item type to combo or package before adding bundle lines.</p>}
            <ErrorAlert error={crud.actionError ?? crud.error} />
            {crud.loading ? <LoadingState /> : <DataTable rows={crud.data?.data ?? []} columns={columns} rowKey={(row) => row.id} />}
            <Pagination meta={crud.data?.meta} onPageChange={crud.setPage} />
            <Modal open={crud.open} title={crud.editing ? 'Edit bundle line' : 'Add bundle line'} onClose={crud.close}>
                {crud.open && <BundleForm key={crud.editing?.id ?? 'new'} row={crud.editing} itemId={itemId} error={crud.actionError} submitting={crud.submitting} onCancel={crud.close} onSubmit={crud.submit} />}
            </Modal>
        </>
    );
}

function BundleForm({ row, itemId, error, submitting, onCancel, onSubmit }: {
    row: ItemBundle | null;
    itemId: number;
    error: ApiError | null;
    submitting: boolean;
    onCancel: () => void;
    onSubmit: (payload: ItemBundlePayload) => Promise<void>;
}) {
    const [child, setChild] = useState<ItemSummary | null>(row?.child_item ?? null);
    const [uom, setUom] = useState<NamedResource | null>(row?.uom ?? null);
    const [quantity, setQuantity] = useState(row?.quantity ?? '1.000000');
    const [lineType, setLineType] = useState(row?.line_type ?? 'stock');
    const [required, setRequired] = useState(row?.is_required ?? true);
    const [sortOrder, setSortOrder] = useState(row?.sort_order ?? 0);
    return <form className="space-y-4" onSubmit={(event) => {
        event.preventDefault();
        if (!child) return;
        void onSubmit({
            child_item_id: Number(child.id),
            quantity,
            uom_id: uom ? Number(uom.id) : null,
            line_type: lineType,
            is_required: required,
            sort_order: sortOrder,
        });
    }}>
        <ErrorAlert error={error} />
        <h3 className="font-semibold text-slate-900">Basic Details</h3>
        <ItemLookupSelect label="Child item" value={child} onChange={setChild} excludeId={itemId} error={fieldError(error, 'child_item_id')} />
        <div className="grid gap-4 sm:grid-cols-2">
            <Input label="Quantity" value={quantity} onChange={(event) => setQuantity(event.target.value)} error={fieldError(error, 'quantity')} required />
            <ItemUomSelect value={uom} onChange={setUom} error={fieldError(error, 'uom_id')} />
        </div>
        <details className="rounded-lg border border-slate-200 bg-slate-50 p-4">
            <summary className="cursor-pointer font-semibold text-slate-800">Advanced</summary>
            <div className="mt-4 grid gap-4 sm:grid-cols-2">
                <Select label="Line type" value={lineType} onChange={(event) => setLineType(event.target.value)} options={bundleLineTypes.map((value) => ({ value, label: value.replaceAll('_', ' ') }))} error={fieldError(error, 'line_type')} />
                <Input label="Sort order" type="number" min="0" value={sortOrder} onChange={(event) => setSortOrder(Number(event.target.value))} />
            </div>
            <label className="mt-4 block text-sm"><input className="mr-2" type="checkbox" checked={required} onChange={(event) => setRequired(event.target.checked)} />Required line</label>
        </details>
        <div className="flex justify-end gap-2"><Button type="button" variant="secondary" onClick={onCancel}>Cancel</Button><Button type="submit" loading={submitting} disabled={!child}>Save line</Button></div>
    </form>;
}

function Actions({ edit, remove }: { edit: () => void; remove: () => void }) {
    return <div className="flex justify-end gap-3"><button type="button" className="font-semibold text-sky-700" onClick={edit}>Edit line</button><button type="button" className="font-semibold text-rose-600" onClick={remove}>Remove line</button></div>;
}
