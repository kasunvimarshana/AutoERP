import { FormEvent, useState } from 'react';
import { ApiError } from '../../../services/api/apiErrors';
import { StatusBadge } from '../../../shared/components/business/StatusBadge';
import { DataTable } from '../../../shared/components/data/DataTable';
import { FieldError } from '../../../shared/components/forms/FieldError';
import { Button } from '../../../shared/components/ui/Button';
import { Card } from '../../../shared/components/ui/Card';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import { Input } from '../../../shared/components/ui/Input';
import { supplierApi } from '../services/supplierApi';
import type { SupplierBankAccount } from '../types/supplier.types';

type SupplierBankAccountsTableProps = {
    accounts: SupplierBankAccount[];
    onSaved?: (account: SupplierBankAccount) => void;
    supplierId?: string;
};

export function SupplierBankAccountsTable({ accounts, onSaved, supplierId }: SupplierBankAccountsTableProps) {
    const [errors, setErrors] = useState<Record<string, string[]>>({});
    const [formError, setFormError] = useState('');
    const [isSubmitting, setIsSubmitting] = useState(false);

    async function handleSubmit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        if (!supplierId) {
            return;
        }

        setErrors({});
        setFormError('');
        setIsSubmitting(true);

        const formData = new FormData(event.currentTarget);
        const account: SupplierBankAccount = {
            accountName: String(formData.get('account_name') ?? ''),
            accountNumber: String(formData.get('account_number') ?? ''),
            bankName: String(formData.get('bank_name') ?? ''),
            branchName: String(formData.get('branch_name') ?? ''),
            currency: '',
            id: '',
            isActive: true,
            isPrimary: formData.get('is_primary') === 'on',
            supplierId,
        };

        try {
            const response = await supplierApi.upsertBankAccount(supplierId, account);
            onSaved?.(response.data);
            event.currentTarget.reset();
        } catch (caught) {
            if (caught instanceof ApiError) {
                setErrors(caught.errors);
                setFormError(caught.message);
            } else {
                setFormError('Unable to save supplier bank account.');
            }
        } finally {
            setIsSubmitting(false);
        }
    }

    return (
        <div className="space-y-4">
            {accounts.length ? (
                <DataTable
                    columns={[
                        { header: 'Account Name', key: 'accountName' },
                        { header: 'Account Number', key: 'accountNumber' },
                        { header: 'Bank', key: 'bankName' },
                        { header: 'Currency', key: 'currency' },
                        { header: 'Primary', key: 'isPrimary', render: (row) => <StatusBadge status={row.isPrimary ? 'Primary' : 'Secondary'} /> },
                        { header: 'Status', key: 'isActive', render: (row) => <StatusBadge status={row.isActive ? 'Active' : 'Inactive'} /> },
                    ]}
                    getRowKey={(row) => row.id}
                    rows={accounts}
                />
            ) : (
                <EmptyState description="Bank accounts are collected here and validated by backend finance/payment workflows." title="No bank accounts" />
            )}
            {supplierId ? (
                <Card className="p-5">
                    <form className="grid gap-4 md:grid-cols-5" onSubmit={handleSubmit}>
                        {formError ? <div className="rounded-lg border border-red-100 bg-red-50 px-4 py-3 text-sm font-medium text-red-700 md:col-span-5">{formError}</div> : null}
                        <div className="space-y-2">
                            <Input name="account_name" placeholder="Account name" />
                            <FieldError message={errors.account_name?.[0]} />
                        </div>
                        <div className="space-y-2">
                            <Input name="account_number" placeholder="Account number" />
                            <FieldError message={errors.account_number?.[0]} />
                        </div>
                        <div className="space-y-2">
                            <Input name="bank_name" placeholder="Bank" />
                            <FieldError message={errors.bank_name?.[0]} />
                        </div>
                        <div className="space-y-2">
                            <Input name="branch_name" placeholder="Branch" />
                            <FieldError message={errors.branch_name?.[0]} />
                        </div>
                        <div className="flex items-center justify-between gap-3">
                            <label className="flex items-center gap-2 text-sm font-medium text-slate-600">
                                <input className="h-4 w-4 rounded border-slate-300" name="is_primary" type="checkbox" />
                                Primary
                            </label>
                            <Button disabled={isSubmitting} type="submit" variant="blue">Add</Button>
                        </div>
                    </form>
                </Card>
            ) : null}
        </div>
    );
}
