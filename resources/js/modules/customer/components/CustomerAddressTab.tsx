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
import { createCustomerAddress, deleteCustomerAddress, listCustomerAddresses, updateCustomerAddress } from '../customerApi';
import { customerAddressTypes, type CustomerAddress, type CustomerAddressPayload } from '../customerTypes';
import { CustomerRelationHeader } from './CustomerRelationHeader';
import { useCustomerRelationCrud } from './useCustomerRelationCrud';

const list = (id: number, page: number, signal: AbortSignal) => listCustomerAddresses(id, { page, per_page: 20 }, signal);
const options = customerAddressTypes.map((value) => ({ value, label: value.replaceAll('_', ' ') }));

export default function CustomerAddressTab({ customerId }: { customerId: number }) {
    const crud = useCustomerRelationCrud({ customerId, list, create: createCustomerAddress, update: updateCustomerAddress, remove: deleteCustomerAddress });
    const columns: DataColumn<CustomerAddress>[] = [
        { key: 'type', header: 'Type', render: (row) => row.address_type },
        { key: 'address', header: 'Address', render: (row) => <>{row.address_line_1}<span className="block text-xs text-slate-500">{[row.city, row.state, row.country].filter(Boolean).join(', ')}</span></> },
        { key: 'primary', header: 'Primary', render: (row) => row.is_primary ? 'Yes' : 'No' },
        { key: 'actions', header: '', className: 'text-right', render: (row) => <Actions edit={() => crud.startEdit(row)} remove={() => crud.destroy(row)} /> },
    ];
    return <><CustomerRelationHeader title="Addresses" description="Billing, shipping, registered, and service addresses." onAdd={crud.startCreate} />
        <ErrorAlert error={crud.actionError ?? crud.error} />{crud.loading ? <LoadingState /> : <DataTable rows={crud.data?.data ?? []} columns={columns} rowKey={(row) => row.id} />}<Pagination meta={crud.data?.meta} onPageChange={crud.setPage} />
        <Modal open={crud.open} title={crud.editing ? 'Edit address' : 'Add address'} onClose={crud.close}>{crud.open && <AddressForm key={crud.editing?.id ?? 'new'} row={crud.editing} error={crud.actionError} submitting={crud.submitting} onCancel={crud.close} onSubmit={crud.submit} />}</Modal>
    </>;
}
function AddressForm({ row, error, submitting, onCancel, onSubmit }: { row: CustomerAddress | null; error: ApiError | null; submitting: boolean; onCancel: () => void; onSubmit: (payload: CustomerAddressPayload) => Promise<void> }) {
    const [form, setForm] = useState<CustomerAddressPayload>(row ? { ...row } : { address_type: 'billing', address_line_1: '', is_primary: false, is_active: true });
    const set = <K extends keyof CustomerAddressPayload>(key: K, value: CustomerAddressPayload[K]) => setForm((current) => ({ ...current, [key]: value }));
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
