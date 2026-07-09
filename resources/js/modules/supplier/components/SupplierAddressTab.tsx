import { useState } from 'react';
import { fieldError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { FormDrawer } from '@/shared/components/Drawer';
import { Pagination } from '@/shared/components/Pagination';
import { Select } from '@/shared/components/Select';
import { createSupplierAddress, deleteSupplierAddress, listSupplierAddresses, updateSupplierAddress } from '../supplierApi';
import { supplierAddressTypes, type SupplierAddress, type SupplierAddressPayload } from '../supplierTypes';
import { SupplierRelationHeader } from './SupplierRelationHeader';
import { useSupplierRelationCrud } from './useSupplierRelationCrud';

const list = (id: number, page: number, signal: AbortSignal) => listSupplierAddresses(id, { page, per_page: 20 }, signal);
const options = supplierAddressTypes.map((value) => ({ value, label: value.replaceAll('_', ' ') }));

export default function SupplierAddressTab({ supplierId, canManage }: { supplierId: number; canManage: boolean }) {
    const crud = useSupplierRelationCrud({ supplierId, list, create: createSupplierAddress, update: updateSupplierAddress, remove: deleteSupplierAddress });
    const columns: DataColumn<SupplierAddress>[] = [
        { key: 'type', header: 'Type', render: (row) => row.address_type },
        { key: 'address', header: 'Address', render: (row) => <>{row.address_line_1}<span className="block text-xs text-slate-500">{[row.city, row.state, row.country].filter(Boolean).join(', ')}</span></> },
        { key: 'primary', header: 'Primary', render: (row) => row.is_primary ? 'Yes' : 'No' },
        ...(canManage ? [{ key: 'actions', header: '', className: 'text-right', render: (row: SupplierAddress) => <Actions edit={() => crud.startEdit(row)} remove={() => crud.destroy(row)} /> }] : []),
    ];
    return <><SupplierRelationHeader title="Addresses" description="Billing, shipping, registered, and warehouse addresses." onAdd={canManage ? crud.startCreate : undefined} />
        <ErrorAlert error={crud.actionError ?? crud.error} />{crud.loading ? <LoadingState /> : <DataTable rows={crud.data?.data ?? []} columns={columns} rowKey={(row) => row.id} />}<Pagination meta={crud.data?.meta} onPageChange={crud.setPage} />
        {canManage && <FormDrawer open={crud.open} title={crud.editing ? 'Edit address' : 'Add address'} onClose={crud.close}>{crud.open && <AddressForm key={crud.editing?.id ?? 'new'} row={crud.editing} error={crud.actionError} submitting={crud.submitting} onCancel={crud.close} onSubmit={crud.submit} />}</FormDrawer>}
        {canManage && crud.confirmDialog}
    </>;
}
function AddressForm({ row, error, submitting, onCancel, onSubmit }: { row: SupplierAddress | null; error: ApiError | null; submitting: boolean; onCancel: () => void; onSubmit: (payload: SupplierAddressPayload) => Promise<void> }) {
    const [form, setForm] = useState<SupplierAddressPayload>(row ? { ...row } : { address_type: 'billing', address_line_1: '', is_primary: false, is_active: true });
    const set = <K extends keyof SupplierAddressPayload>(key: K, value: SupplierAddressPayload[K]) => setForm((current) => ({ ...current, [key]: value }));
    return <form className="space-y-4" onSubmit={(event) => { event.preventDefault(); void onSubmit(form); }}><ErrorAlert error={error} />
        <div className="grid gap-4 sm:grid-cols-2"><Select label="Address type" value={form.address_type} onChange={(event) => set('address_type', event.target.value)} options={options} />
            <Input label="Address line 1" value={form.address_line_1} onChange={(event) => set('address_line_1', event.target.value)} error={fieldError(error, 'address_line_1')} required />
            <Input label="Address line 2" value={form.address_line_2 ?? ''} onChange={(event) => set('address_line_2', event.target.value || null)} />
            <Input label="City" value={form.city ?? ''} onChange={(event) => set('city', event.target.value || null)} />
            <Input label="State" value={form.state ?? ''} onChange={(event) => set('state', event.target.value || null)} />
            <Input label="Postal code" value={form.postal_code ?? ''} onChange={(event) => set('postal_code', event.target.value || null)} />
            <Input label="Country" value={form.country ?? ''} onChange={(event) => set('country', event.target.value || null)} /></div>
        <div className="flex gap-6 text-sm"><label><input className="mr-2" type="checkbox" checked={form.is_primary} onChange={(event) => set('is_primary', event.target.checked)} />Primary for this type</label><label><input className="mr-2" type="checkbox" checked={form.is_active} onChange={(event) => set('is_active', event.target.checked)} />Active</label></div>
        <Footer submitting={submitting} onCancel={onCancel} /></form>;
}
function Footer({ submitting, onCancel }: { submitting: boolean; onCancel: () => void }) { return <div className="flex justify-end gap-2"><Button type="button" variant="secondary" onClick={onCancel}>Cancel</Button><Button type="submit" loading={submitting}>Save</Button></div>; }
function Actions({ edit, remove }: { edit: () => void; remove: () => void }) { return <div className="flex justify-end gap-3"><button type="button" className="font-semibold text-sky-700" onClick={edit}>Edit</button><button type="button" className="font-semibold text-rose-600" onClick={remove}>Delete</button></div>; }
