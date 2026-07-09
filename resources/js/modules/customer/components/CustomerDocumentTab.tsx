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
import { StatusBadge } from '@/shared/components/StatusBadge';
import { createCustomerDocument, deleteCustomerDocument, listCustomerDocuments, updateCustomerDocument } from '../customerApi';
import { customerDocumentStatuses, customerDocumentTypes, type CustomerDocument, type CustomerDocumentPayload } from '../customerTypes';
import { CustomerRelationHeader } from './CustomerRelationHeader';
import { useCustomerRelationCrud } from './useCustomerRelationCrud';

const list = (id: number, page: number, signal: AbortSignal) => listCustomerDocuments(id, { page, per_page: 20 }, signal);
const options = (values: readonly string[]) => values.map((value) => ({ value, label: value.replaceAll('_', ' ') }));
export default function CustomerDocumentTab({ customerId, canManage }: { customerId: number; canManage: boolean }) {
    const crud = useCustomerRelationCrud({ customerId, list, create: createCustomerDocument, update: updateCustomerDocument, remove: deleteCustomerDocument });
    const columns: DataColumn<CustomerDocument>[] = [
        { key: 'type', header: 'Type', render: (row) => row.document_type.replaceAll('_', ' ') },
        { key: 'number', header: 'Number', render: (row) => row.document_number ?? '-' },
        { key: 'dates', header: 'Validity', render: (row) => `${row.issued_date ?? '-'} to ${row.expiry_date ?? '-'}` },
        { key: 'status', header: 'Status', render: (row) => <StatusBadge status={row.status} /> },
        ...(canManage ? [{ key: 'actions', header: '', className: 'text-right', render: (row: CustomerDocument) => <Actions edit={() => crud.startEdit(row)} remove={() => crud.destroy(row)} /> }] : []),
    ];
    return <><CustomerRelationHeader title="Compliance documents" description="Customer registrations, certificates, contracts, and licenses." onAdd={canManage ? crud.startCreate : undefined} /><ErrorAlert error={crud.actionError ?? crud.error} />{crud.loading ? <LoadingState /> : <DataTable rows={crud.data?.data ?? []} columns={columns} rowKey={(row) => row.id} />}<Pagination meta={crud.data?.meta} onPageChange={crud.setPage} />{canManage && <FormDrawer open={crud.open} title={crud.editing ? 'Edit document' : 'Add document'} onClose={crud.close}>{crud.open && <DocumentForm key={crud.editing?.id ?? 'new'} row={crud.editing} error={crud.actionError} submitting={crud.submitting} onCancel={crud.close} onSubmit={crud.submit} />}</FormDrawer>}{canManage && crud.confirmDialog}</>;
}
function DocumentForm({ row, error, submitting, onCancel, onSubmit }: { row: CustomerDocument | null; error: ApiError | null; submitting: boolean; onCancel: () => void; onSubmit: (payload: CustomerDocumentPayload) => Promise<void> }) {
    const [form, setForm] = useState<CustomerDocumentPayload>(row ? { ...row } : { document_type: 'business_registration', status: 'pending' });
    const set = <K extends keyof CustomerDocumentPayload>(key: K, value: CustomerDocumentPayload[K]) => setForm((current) => ({ ...current, [key]: value }));
    return <form className="space-y-4" onSubmit={(event) => { event.preventDefault(); void onSubmit(form); }}><ErrorAlert error={error} /><div className="grid gap-4 sm:grid-cols-2">
        <Select label="Document type" value={form.document_type} onChange={(event) => set('document_type', event.target.value)} options={options(customerDocumentTypes)} />
        <Input label="Document number" value={form.document_number ?? ''} onChange={(event) => set('document_number', event.target.value || null)} />
        <Input label="Issued date" type="date" value={form.issued_date ?? ''} onChange={(event) => set('issued_date', event.target.value || null)} />
        <Input label="Expiry date" type="date" value={form.expiry_date ?? ''} onChange={(event) => set('expiry_date', event.target.value || null)} error={fieldError(error, 'expiry_date')} />
        <Input label="File reference" value={form.file_path ?? ''} onChange={(event) => set('file_path', event.target.value || null)} />
        <Select label="Status" value={form.status} onChange={(event) => set('status', event.target.value)} options={options(customerDocumentStatuses)} /></div><Footer submitting={submitting} onCancel={onCancel} /></form>;
}
function Footer({ submitting, onCancel }: { submitting: boolean; onCancel: () => void }) { return <div className="flex justify-end gap-2"><Button type="button" variant="secondary" onClick={onCancel}>Cancel</Button><Button type="submit" loading={submitting}>Save</Button></div>; }
function Actions({ edit, remove }: { edit: () => void; remove: () => void }) { return <div className="flex justify-end gap-3"><button type="button" className="font-semibold text-sky-700" onClick={edit}>Edit</button><button type="button" className="font-semibold text-rose-600" onClick={remove}>Delete</button></div>; }
