import { useState } from 'react';
import { fieldError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { FormDrawer } from '@/shared/components/Drawer';
import { Pagination } from '@/shared/components/Pagination';
import type { NamedResource } from '@/shared/types/common';
import { readableRelation } from '@/shared/utils/object';
import { createSupplierBankAccount, deleteSupplierBankAccount, listSupplierBankAccounts, updateSupplierBankAccount } from '../supplierApi';
import type { SupplierBankAccount, SupplierBankAccountPayload } from '../supplierTypes';
import { SupplierCurrencySelect } from './SupplierCurrencySelect';
import { SupplierRelationHeader } from './SupplierRelationHeader';
import { useSupplierRelationCrud } from './useSupplierRelationCrud';

const list = (id: number, page: number, signal: AbortSignal) => listSupplierBankAccounts(id, { page, per_page: 20 }, signal);
export default function SupplierBankAccountTab({ supplierId }: { supplierId: number }) {
    const crud = useSupplierRelationCrud({ supplierId, list, create: createSupplierBankAccount, update: updateSupplierBankAccount, remove: deleteSupplierBankAccount });
    const columns: DataColumn<SupplierBankAccount>[] = [
        { key: 'bank', header: 'Bank', render: (row) => <>{row.bank_name}<span className="block text-xs text-slate-500">{row.branch_name ?? ''}</span></> },
        { key: 'account', header: 'Account', render: (row) => <>{row.account_name}<span className="block text-xs text-slate-500">{row.account_number}</span></> },
        { key: 'currency', header: 'Currency', render: (row) => readableRelation(row.currency) },
        { key: 'primary', header: 'Primary', render: (row) => row.is_primary ? 'Yes' : 'No' },
        { key: 'actions', header: '', className: 'text-right', render: (row) => <Actions edit={() => crud.startEdit(row)} remove={() => crud.destroy(row)} /> },
    ];
    return <><SupplierRelationHeader title="Bank accounts" description="Payment destination reference accounts owned by the supplier master." onAdd={crud.startCreate} /><ErrorAlert error={crud.actionError ?? crud.error} />{crud.loading ? <LoadingState /> : <DataTable rows={crud.data?.data ?? []} columns={columns} rowKey={(row) => row.id} />}<Pagination meta={crud.data?.meta} onPageChange={crud.setPage} /><FormDrawer open={crud.open} title={crud.editing ? 'Edit bank account' : 'Add bank account'} onClose={crud.close}>{crud.open && <BankForm key={crud.editing?.id ?? 'new'} row={crud.editing} error={crud.actionError} submitting={crud.submitting} onCancel={crud.close} onSubmit={crud.submit} />}</FormDrawer>{crud.confirmDialog}</>;
}
function BankForm({ row, error, submitting, onCancel, onSubmit }: { row: SupplierBankAccount | null; error: ApiError | null; submitting: boolean; onCancel: () => void; onSubmit: (payload: SupplierBankAccountPayload) => Promise<void> }) {
    const [currency, setCurrency] = useState<NamedResource | null>(row?.currency ?? null);
    const [form, setForm] = useState<SupplierBankAccountPayload>(row ? { bank_name: row.bank_name, branch_name: row.branch_name, account_name: row.account_name, account_number: row.account_number, swift_code: row.swift_code, iban: row.iban, currency_id: row.currency ? Number(row.currency.id) : null, is_primary: row.is_primary, is_active: row.is_active, notes: row.notes } : { bank_name: '', account_name: '', account_number: '', is_primary: false, is_active: true });
    const set = <K extends keyof SupplierBankAccountPayload>(key: K, value: SupplierBankAccountPayload[K]) => setForm((current) => ({ ...current, [key]: value }));
    return <form className="space-y-4" onSubmit={(event) => { event.preventDefault(); void onSubmit(form); }}><ErrorAlert error={error} /><div className="grid gap-4 sm:grid-cols-2">
        <Input label="Bank name" value={form.bank_name} onChange={(event) => set('bank_name', event.target.value)} error={fieldError(error, 'bank_name')} required />
        <Input label="Branch" value={form.branch_name ?? ''} onChange={(event) => set('branch_name', event.target.value || null)} />
        <Input label="Account name" value={form.account_name} onChange={(event) => set('account_name', event.target.value)} error={fieldError(error, 'account_name')} required />
        <Input label="Account number" value={form.account_number} onChange={(event) => set('account_number', event.target.value)} error={fieldError(error, 'account_number')} required />
        <Input label="SWIFT code" value={form.swift_code ?? ''} onChange={(event) => set('swift_code', event.target.value || null)} />
        <Input label="IBAN" value={form.iban ?? ''} onChange={(event) => set('iban', event.target.value || null)} />
        <SupplierCurrencySelect value={currency} onChange={(next) => { setCurrency(next); set('currency_id', next ? Number(next.id) : null); }} error={fieldError(error, 'currency_id')} /></div>
        <div className="flex gap-6 text-sm"><label><input className="mr-2" type="checkbox" checked={form.is_primary} onChange={(event) => set('is_primary', event.target.checked)} />Primary</label><label><input className="mr-2" type="checkbox" checked={form.is_active} onChange={(event) => set('is_active', event.target.checked)} />Active</label></div><Footer submitting={submitting} onCancel={onCancel} /></form>;
}
function Footer({ submitting, onCancel }: { submitting: boolean; onCancel: () => void }) { return <div className="flex justify-end gap-2"><Button type="button" variant="secondary" onClick={onCancel}>Cancel</Button><Button type="submit" loading={submitting}>Save</Button></div>; }
function Actions({ edit, remove }: { edit: () => void; remove: () => void }) { return <div className="flex justify-end gap-3"><button type="button" className="font-semibold text-sky-700" onClick={edit}>Edit</button><button type="button" className="font-semibold text-rose-600" onClick={remove}>Delete</button></div>; }
