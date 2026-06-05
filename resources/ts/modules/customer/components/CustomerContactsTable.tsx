import { FormEvent, useState } from 'react';
import { ApiError } from '../../../services/api/apiErrors';
import { StatusBadge } from '../../../shared/components/business/StatusBadge';
import { DataTable } from '../../../shared/components/data/DataTable';
import { FieldError } from '../../../shared/components/forms/FieldError';
import { Button } from '../../../shared/components/ui/Button';
import { Card } from '../../../shared/components/ui/Card';
import { Input } from '../../../shared/components/ui/Input';
import { customerApi } from '../services/customerApi';
import type { CustomerContact } from '../types/customer.types';

type CustomerContactsTableProps = {
    contacts: CustomerContact[];
    customerId?: string;
    onSaved?: (contact: CustomerContact) => void;
};

export function CustomerContactsTable({ contacts, customerId, onSaved }: CustomerContactsTableProps) {
    const [errors, setErrors] = useState<Record<string, string[]>>({});
    const [formError, setFormError] = useState('');
    const [isSubmitting, setIsSubmitting] = useState(false);

    async function handleSubmit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        if (!customerId) {
            return;
        }

        setErrors({});
        setFormError('');
        setIsSubmitting(true);

        const formData = new FormData(event.currentTarget);
        const contact: CustomerContact = {
            customerId,
            email: String(formData.get('email') ?? ''),
            id: '',
            isPrimary: formData.get('is_primary') === 'on',
            name: String(formData.get('contact_name') ?? ''),
            phone: String(formData.get('phone') ?? ''),
            role: String(formData.get('designation') ?? ''),
        };

        try {
            const response = await customerApi.upsertContact(customerId, contact);
            onSaved?.(response.data);
            event.currentTarget.reset();
        } catch (caught) {
            if (caught instanceof ApiError) {
                setErrors(caught.errors);
                setFormError(caught.message);
            } else {
                setFormError('Unable to save contact.');
            }
        } finally {
            setIsSubmitting(false);
        }
    }

    return (
        <div className="space-y-4">
            <DataTable
                columns={[
                    { header: 'Name', key: 'name' },
                    { header: 'Role', key: 'role' },
                    { header: 'Email', key: 'email' },
                    { header: 'Phone', key: 'phone' },
                    { header: 'Primary', key: 'isPrimary', render: (row) => <StatusBadge status={row.isPrimary ? 'Yes' : 'No'} /> },
                ]}
                getRowKey={(row) => row.id}
                rows={contacts}
            />
            {customerId ? (
                <Card className="p-5">
                    <form className="grid gap-4 md:grid-cols-5" onSubmit={handleSubmit}>
                        {formError ? <div className="rounded-lg border border-red-100 bg-red-50 px-4 py-3 text-sm font-medium text-red-700 md:col-span-5">{formError}</div> : null}
                        <div className="space-y-2">
                            <Input name="contact_name" placeholder="Contact name" />
                            <FieldError message={errors.contact_name?.[0]} />
                        </div>
                        <div className="space-y-2">
                            <Input name="designation" placeholder="Role" />
                            <FieldError message={errors.designation?.[0]} />
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
