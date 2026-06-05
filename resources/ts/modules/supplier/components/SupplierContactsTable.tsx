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
import type { SupplierContact } from '../types/supplier.types';

type SupplierContactsTableProps = {
    contacts: SupplierContact[];
    onSaved?: (contact: SupplierContact) => void;
    supplierId?: string;
};

export function SupplierContactsTable({ contacts, onSaved, supplierId }: SupplierContactsTableProps) {
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
        const contact: SupplierContact = {
            department: String(formData.get('department') ?? ''),
            designation: String(formData.get('designation') ?? ''),
            email: String(formData.get('email') ?? ''),
            id: '',
            isPrimary: formData.get('is_primary') === 'on',
            name: String(formData.get('name') ?? ''),
            phone: String(formData.get('phone') ?? ''),
            supplierId,
        };

        try {
            const response = await supplierApi.upsertContact(supplierId, contact);
            onSaved?.(response.data);
            event.currentTarget.reset();
        } catch (caught) {
            if (caught instanceof ApiError) {
                setErrors(caught.errors);
                setFormError(caught.message);
            } else {
                setFormError('Unable to save supplier contact.');
            }
        } finally {
            setIsSubmitting(false);
        }
    }

    return (
        <div className="space-y-4">
            {contacts.length ? (
                <DataTable
                    columns={[
                        { header: 'Name', key: 'name' },
                        { header: 'Designation', key: 'designation' },
                        { header: 'Email', key: 'email' },
                        { header: 'Phone', key: 'phone' },
                        { header: 'Primary', key: 'isPrimary', render: (row) => <StatusBadge status={row.isPrimary ? 'Primary' : 'Secondary'} /> },
                    ]}
                    getRowKey={(row) => row.id}
                    rows={contacts}
                />
            ) : (
                <EmptyState description="Add procurement, accounts, or operations contacts after the supplier is saved." title="No supplier contacts" />
            )}
            {supplierId ? (
                <Card className="p-5">
                    <form className="grid gap-4 md:grid-cols-6" onSubmit={handleSubmit}>
                        {formError ? <div className="rounded-lg border border-red-100 bg-red-50 px-4 py-3 text-sm font-medium text-red-700 md:col-span-6">{formError}</div> : null}
                        <div className="space-y-2">
                            <Input name="name" placeholder="Contact name" />
                            <FieldError message={errors.name?.[0]} />
                        </div>
                        <div className="space-y-2">
                            <Input name="designation" placeholder="Designation" />
                            <FieldError message={errors.designation?.[0]} />
                        </div>
                        <div className="space-y-2">
                            <Input name="department" placeholder="Department" />
                            <FieldError message={errors.department?.[0]} />
                        </div>
                        <div className="space-y-2">
                            <Input name="email" placeholder="Email" type="email" />
                            <FieldError message={errors.email?.[0]} />
                        </div>
                        <div className="space-y-2">
                            <Input name="phone" placeholder="Phone" />
                            <FieldError message={errors.phone?.[0]} />
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
