import { useState } from 'react';
import { fieldError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Modal } from '@/shared/components/Modal';
import { Pagination } from '@/shared/components/Pagination';
import { StatusBadge } from '@/shared/components/StatusBadge';
import type { NamedResource } from '@/shared/types/common';
import { createSupplierItemMapping, deleteSupplierItemMapping, listSupplierItemMappings, updateSupplierItemMapping } from '../supplierApi';
import type { SupplierItemMapping, SupplierItemMappingPayload } from '../supplierTypes';
import { SupplierItemSelect } from './SupplierItemSelect';
import { SupplierItemVariantSelect } from './SupplierItemVariantSelect';
import { SupplierRelationHeader } from './SupplierRelationHeader';
import { SupplierUomSelect } from './SupplierUomSelect';
import { useSupplierRelationCrud } from './useSupplierRelationCrud';

const list = (id: number, page: number, signal: AbortSignal) => listSupplierItemMappings(id, { page, per_page: 20 }, signal);
export default function SupplierItemMappingTab({ supplierId }: { supplierId: number }) {
    const crud = useSupplierRelationCrud({ supplierId, list, create: createSupplierItemMapping, update: updateSupplierItemMapping, remove: deleteSupplierItemMapping });
    const columns: DataColumn<SupplierItemMapping>[] = [
        { key: 'item', header: 'Item', render: (row) => row.item ? `${row.item.code} - ${row.item.name}` : '-' },
        { key: 'supplier-code', header: 'Supplier reference', render: (row) => <>{row.supplier_item_code ?? '-'}<span className="block text-xs text-slate-500">{row.supplier_item_name ?? ''}</span></> },
        { key: 'uom', header: 'Purchase UOM', render: (row) => row.default_purchase_uom ? `${row.default_purchase_uom.code} - ${row.default_purchase_uom.name}` : '-' },
        { key: 'minimum', header: 'Minimum quantity', render: (row) => row.minimum_order_quantity },
        { key: 'preferred', header: 'Preferred', render: (row) => row.is_preferred ? 'Yes' : 'No' },
        { key: 'status', header: 'Status', render: (row) => <StatusBadge status={row.is_active ? 'active' : 'inactive'} /> },
        { key: 'actions', header: '', className: 'text-right', render: (row) => <Actions edit={() => crud.startEdit(row)} remove={() => crud.destroy(row)} /> },
    ];
    return <><SupplierRelationHeader title="Item mappings" description="Supplier-specific item codes, names, UOMs, and ordering references." onAdd={crud.startCreate} /><ErrorAlert error={crud.actionError ?? crud.error} />{crud.loading ? <LoadingState /> : <DataTable rows={crud.data?.data ?? []} columns={columns} rowKey={(row) => row.id} />}<Pagination meta={crud.data?.meta} onPageChange={crud.setPage} /><Modal open={crud.open} title={crud.editing ? 'Edit item mapping' : 'Add item mapping'} onClose={crud.close}>{crud.open && <MappingForm key={crud.editing?.id ?? 'new'} row={crud.editing} error={crud.actionError} submitting={crud.submitting} onCancel={crud.close} onSubmit={crud.submit} />}</Modal></>;
}
function MappingForm({ row, error, submitting, onCancel, onSubmit }: { row: SupplierItemMapping | null; error: ApiError | null; submitting: boolean; onCancel: () => void; onSubmit: (payload: SupplierItemMappingPayload) => Promise<void> }) {
    const [item, setItem] = useState<NamedResource | null>(row?.item ?? null);
    const [variant, setVariant] = useState<NamedResource | null>(row?.variant ?? null);
    const [uom, setUom] = useState<NamedResource | null>(row?.default_purchase_uom ?? null);
    const [form, setForm] = useState<SupplierItemMappingPayload>(row ? { item_id: Number(row.item?.id), item_variant_id: row.variant ? Number(row.variant.id) : null, supplier_item_code: row.supplier_item_code, supplier_item_name: row.supplier_item_name, default_purchase_uom_id: row.default_purchase_uom ? Number(row.default_purchase_uom.id) : null, minimum_order_quantity: row.minimum_order_quantity, lead_time_days: row.lead_time_days, is_preferred: row.is_preferred, is_active: row.is_active } : { item_id: 0, minimum_order_quantity: '0.000000', is_preferred: false, is_active: true });
    const set = <K extends keyof SupplierItemMappingPayload>(key: K, value: SupplierItemMappingPayload[K]) => setForm((current) => ({ ...current, [key]: value }));
    return <form className="space-y-4" onSubmit={(event) => { event.preventDefault(); if (item) void onSubmit(form); }}><ErrorAlert error={error} /><div className="grid gap-4 sm:grid-cols-2">
        <SupplierItemSelect value={item} onChange={(next) => { setItem(next); setVariant(null); set('item_id', next ? Number(next.id) : 0); set('item_variant_id', null); }} error={fieldError(error, 'item_id')} />
        <SupplierItemVariantSelect item={item} value={variant} onChange={(next) => { setVariant(next); set('item_variant_id', next ? Number(next.id) : null); }} error={fieldError(error, 'item_variant_id')} />
        <SupplierUomSelect value={uom} onChange={(next) => { setUom(next); set('default_purchase_uom_id', next ? Number(next.id) : null); }} error={fieldError(error, 'default_purchase_uom_id')} />
        <Input label="Supplier item code" value={form.supplier_item_code ?? ''} onChange={(event) => set('supplier_item_code', event.target.value || null)} />
        <Input label="Supplier item name" value={form.supplier_item_name ?? ''} onChange={(event) => set('supplier_item_name', event.target.value || null)} />
        <Input label="Minimum order quantity" value={form.minimum_order_quantity} onChange={(event) => set('minimum_order_quantity', event.target.value)} error={fieldError(error, 'minimum_order_quantity')} />
        <Input label="Lead time days" type="number" min="0" value={form.lead_time_days ?? ''} onChange={(event) => set('lead_time_days', event.target.value ? Number(event.target.value) : null)} /></div>
        <div className="flex gap-6 text-sm"><label><input className="mr-2" type="checkbox" checked={form.is_preferred} onChange={(event) => set('is_preferred', event.target.checked)} />Preferred</label><label><input className="mr-2" type="checkbox" checked={form.is_active} onChange={(event) => set('is_active', event.target.checked)} />Active</label></div><div className="flex justify-end gap-2"><Button type="button" variant="secondary" onClick={onCancel}>Cancel</Button><Button type="submit" loading={submitting} disabled={!item}>Save</Button></div></form>;
}
function Actions({ edit, remove }: { edit: () => void; remove: () => void }) { return <div className="flex justify-end gap-3"><button className="font-semibold text-sky-700" onClick={edit}>Edit</button><button className="font-semibold text-rose-600" onClick={remove}>Delete</button></div>; }
