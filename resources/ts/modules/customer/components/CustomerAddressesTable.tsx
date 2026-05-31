import { FormEvent, useState } from 'react';
import { ApiError } from '../../../services/api/apiErrors';
import { StatusBadge } from '../../../shared/components/business/StatusBadge';
import { DataTable } from '../../../shared/components/data/DataTable';
import { FieldError } from '../../../shared/components/forms/FieldError';
import { Button } from '../../../shared/components/ui/Button';
import { Card } from '../../../shared/components/ui/Card';
import { Input } from '../../../shared/components/ui/Input';
import { Select } from '../../../shared/components/ui/Select';
import { customerApi } from '../services/customerApi';
import type { CustomerAddress } from '../types/customer.types';

type CustomerAddressesTableProps = {
    addresses: CustomerAddress[];
    customerId?: string;
    onSaved?: (address: CustomerAddress) => void;
};

export function CustomerAddressesTable({ addresses, customerId, onSaved }: CustomerAddressesTableProps) {
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
        const address: CustomerAddress = {
            city: String(formData.get('city') ?? ''),
            country: String(formData.get('country_name') ?? ''),
            customerId,
            id: '',
            isPrimary: formData.get('is_primary') === 'on',
            line1: String(formData.get('address_line_1') ?? ''),
            line2: String(formData.get('address_line_2') ?? ''),
            postalCode: String(formData.get('postal_code') ?? ''),
            type: String(formData.get('address_type') ?? 'billing') as CustomerAddress['type'],
        };

        try {
            const response = await customerApi.upsertAddress(customerId, address);
            onSaved?.(response.data);
            event.currentTarget.reset();
        } catch (caught) {
            if (caught instanceof ApiError) {
                setErrors(caught.errors);
                setFormError(caught.message);
            } else {
                setFormError('Unable to save address.');
            }
        } finally {
            setIsSubmitting(false);
        }
    }

    return (
        <div className="space-y-4">
            <DataTable
                columns={[
                    { header: 'Type', key: 'type' },
                    { header: 'Address', key: 'line1', render: (row) => [row.line1, row.line2, row.city, row.country].filter(Boolean).join(', ') },
                    { header: 'Primary', key: 'isPrimary', render: (row) => <StatusBadge status={row.isPrimary ? 'Yes' : 'No'} /> },
                ]}
                getRowKey={(row) => row.id}
                rows={addresses}
            />
            {customerId ? (
                <Card className="p-5">
                    <form className="grid gap-4 md:grid-cols-6" onSubmit={handleSubmit}>
                        {formError ? <div className="rounded-lg border border-red-100 bg-red-50 px-4 py-3 text-sm font-medium text-red-700 md:col-span-6">{formError}</div> : null}
                        <div className="space-y-2">
                            <Select
                                name="address_type"
                                options={[
                                    { label: 'Billing', value: 'billing' },
                                    { label: 'Delivery', value: 'delivery' },
                                    { label: 'Service', value: 'service' },
                                ]}
                            />
                            <FieldError message={errors.address_type?.[0]} />
                        </div>
                        <div className="space-y-2 md:col-span-2">
                            <Input name="address_line_1" placeholder="Address line 1" />
                            <FieldError message={errors.address_line_1?.[0]} />
                        </div>
                        <div className="space-y-2">
                            <Input name="city" placeholder="City" />
                            <FieldError message={errors.city?.[0]} />
                        </div>
                        <div className="space-y-2">
                            <Input name="postal_code" placeholder="Postal code" />
                            <FieldError message={errors.postal_code?.[0]} />
                        </div>
                        <div className="space-y-2">
                            <Input name="country_name" placeholder="Country" />
                            <FieldError message={errors.country_name?.[0]} />
                        </div>
                        <div className="space-y-2 md:col-span-2">
                            <Input name="address_line_2" placeholder="Address line 2" />
                            <FieldError message={errors.address_line_2?.[0]} />
                        </div>
                        <div className="flex items-center justify-between gap-3 md:col-span-4">
                            <label className="flex items-center gap-2 text-sm font-medium text-slate-600">
                                <input className="h-4 w-4 rounded border-slate-300" name="is_primary" type="checkbox" />
                                Primary
                            </label>
                            <Button disabled={isSubmitting} type="submit" variant="blue">Add address</Button>
                        </div>
                    </form>
                </Card>
            ) : null}
        </div>
    );
}
