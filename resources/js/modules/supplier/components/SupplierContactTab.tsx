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
import { createSupplierContact, deleteSupplierContact, listSupplierContacts, updateSupplierContact } from '../supplierApi';
import type { SupplierContact, SupplierContactPayload } from '../supplierTypes';
import { SupplierRelationHeader } from './SupplierRelationHeader';
import { useSupplierRelationCrud } from './useSupplierRelationCrud';

const list = (id: number, page: number, signal: AbortSignal) => listSupplierContacts(id, { page, per_page: 20 }, signal);

export default function SupplierContactTab({ supplierId, canManage }: { supplierId: number; canManage: boolean }) {
    const crud = useSupplierRelationCrud({ supplierId, list, create: createSupplierContact, update: updateSupplierContact, remove: deleteSupplierContact });
    const columns: DataColumn<SupplierContact>[] = [
        { key: 'name', header: 'Contact', render: (row) => <>{row.contact_name}<span className="block text-xs text-slate-500">{row.designation ?? row.department ?? ''}</span></> },
        { key: 'email', header: 'Email', render: (row) => row.email ?? '-' },
        { key: 'phone', header: 'Phone', render: (row) => row.phone ?? row.mobile ?? '-' },
        { key: 'primary', header: 'Primary', render: (row) => row.is_primary ? 'Yes' : 'No' },
        { key: 'status', header: 'Status', render: (row) => <StatusBadge status={row.is_active ? 'active' : 'inactive'} /> },
        ...(canManage ? [{ key: 'actions', header: '', className: 'text-right', render: (row: SupplierContact) => <Actions edit={() => crud.startEdit(row)} remove={() => crud.destroy(row)} /> }] : []),
    ];
    return <><SupplierRelationHeader title="Contacts" description="Supplier people and communication details." onAdd={canManage ? crud.startCreate : undefined} />
        <ErrorAlert error={crud.actionError ?? crud.error} />
        {crud.loading ? <LoadingState /> : <DataTable rows={crud.data?.data ?? []} columns={columns} rowKey={(row) => row.id} />}
        <Pagination meta={crud.data?.meta} onPageChange={crud.setPage} />
        {canManage && <FormDrawer open={crud.open} title={crud.editing ? 'Edit contact' : 'Add contact'} onClose={crud.close}>
            {crud.open && <ContactForm key={crud.editing?.id ?? 'new'} row={crud.editing} error={crud.actionError} submitting={crud.submitting} onCancel={crud.close} onSubmit={crud.submit} />}
        </FormDrawer>}
        {canManage && crud.confirmDialog}
    </>;
}

function ContactForm({ row, error, submitting, onCancel, onSubmit }: { row: SupplierContact | null; error: ApiError | null; submitting: boolean; onCancel: () => void; onSubmit: (payload: SupplierContactPayload) => Promise<void> }) {
    const [form, setForm] = useState<SupplierContactPayload>(row ? { ...row } : { contact_name: '', is_primary: false, is_active: true });
    const set = <K extends keyof SupplierContactPayload>(key: K, value: SupplierContactPayload[K]) => setForm((current) => ({ ...current, [key]: value }));
    return <form className="space-y-4" onSubmit={(event) => { event.preventDefault(); void onSubmit(form); }}>
        <ErrorAlert error={error} />
        <div className="grid gap-4 sm:grid-cols-2">
            <Input label="Contact name" value={form.contact_name} onChange={(event) => set('contact_name', event.target.value)} error={fieldError(error, 'contact_name')} required />
            <Input label="Designation" value={form.designation ?? ''} onChange={(event) => set('designation', event.target.value || null)} />
            <Input label="Department" value={form.department ?? ''} onChange={(event) => set('department', event.target.value || null)} />
            <Input label="Email" type="email" value={form.email ?? ''} onChange={(event) => set('email', event.target.value || null)} />
            <Input label="Phone" value={form.phone ?? ''} onChange={(event) => set('phone', event.target.value || null)} />
            <Input label="Mobile" value={form.mobile ?? ''} onChange={(event) => set('mobile', event.target.value || null)} />
        </div>
        <Checks primary={form.is_primary} active={form.is_active} onPrimary={(value) => set('is_primary', value)} onActive={(value) => set('is_active', value)} />
        <Footer submitting={submitting} onCancel={onCancel} />
    </form>;
}

function Checks({ primary, active, onPrimary, onActive }: { primary: boolean; active: boolean; onPrimary: (value: boolean) => void; onActive: (value: boolean) => void }) {
    return <div className="flex gap-6 text-sm"><label><input className="mr-2" type="checkbox" checked={primary} onChange={(event) => onPrimary(event.target.checked)} />Primary</label><label><input className="mr-2" type="checkbox" checked={active} onChange={(event) => onActive(event.target.checked)} />Active</label></div>;
}
function Footer({ submitting, onCancel }: { submitting: boolean; onCancel: () => void }) { return <div className="flex justify-end gap-2"><Button type="button" variant="secondary" onClick={onCancel}>Cancel</Button><Button type="submit" loading={submitting}>Save</Button></div>; }
function Actions({ edit, remove }: { edit: () => void; remove: () => void }) { return <div className="flex justify-end gap-3"><button type="button" className="font-semibold text-sky-700" onClick={edit}>Edit</button><button type="button" className="font-semibold text-rose-600" onClick={remove}>Delete</button></div>; }
