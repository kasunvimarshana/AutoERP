import { FormEvent, useState } from 'react';
import { ApiError } from '../../../services/api/apiErrors';
import { StatusBadge } from '../../../shared/components/business/StatusBadge';
import { DataTable } from '../../../shared/components/data/DataTable';
import { FieldError } from '../../../shared/components/forms/FieldError';
import { Button } from '../../../shared/components/ui/Button';
import { Card } from '../../../shared/components/ui/Card';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import { Input } from '../../../shared/components/ui/Input';
import { Select } from '../../../shared/components/ui/Select';
import { supplierApi } from '../services/supplierApi';
import type { SupplierAddress } from '../types/supplier.types';

type SupplierAddressesTableProps = {
    addresses: SupplierAddress[];
    onSaved?: (address: SupplierAddress) => void;
    supplierId?: string;
};

export function SupplierAddressesTable({ addresses, onSaved, supplierId }: SupplierAddressesTableProps) {
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
        const address: SupplierAddress = {
            city: String(formData.get('city') ?? ''),
            country: '',
            id: '',
            isDefault: formData.get('is_default') === 'on',
            line1: String(formData.get('address_line1') ?? ''),
            line2: String(formData.get('address_line2') ?? ''),
            postalCode: String(formData.get('postal_code') ?? ''),
            supplierId,
            type: String(formData.get('type') ?? 'billing') as SupplierAddress['type'],
        };

        try {
            const response = await supplierApi.upsertAddress(supplierId, address);
            onSaved?.(response.data);
            event.currentTarget.reset();
        } catch (caught) {
            if (caught instanceof ApiError) {
                setErrors(caught.errors);
                setFormError(caught.message);
            } else {
                setFormError('Unable to save supplier address.');
            }
        } finally {
            setIsSubmitting(false);
        }
    }

    return (
        <div className="space-y-4">
            {addresses.length ? (
                <DataTable
                    columns={[
                        { header: 'Type', key: 'type' },
                        { header: 'Address', key: 'line1', render: (row) => [row.line1, row.line2, row.city, row.country].filter(Boolean).join(', ') },
                        { header: 'Default', key: 'isDefault', render: (row) => <StatusBadge status={row.isDefault ? 'Default' : 'Optional'} /> },
                    ]}
                    getRowKey={(row) => row.id}
                    rows={addresses}
                />
            ) : (
                <EmptyState description="Registered, billing, and shipping addresses can be added after save." title="No supplier addresses" />
            )}
            {supplierId ? (
                <Card className="p-5">
                    <form className="grid gap-4 md:grid-cols-6" onSubmit={handleSubmit}>
                        {formError ? <div className="rounded-lg border border-red-100 bg-red-50 px-4 py-3 text-sm font-medium text-red-700 md:col-span-6">{formError}</div> : null}
                        <div className="space-y-2">
                            <Select
                                name="type"
                                options={[
                                    { label: 'Billing', value: 'billing' },
                                    { label: 'Registered', value: 'registered' },
                                    { label: 'Shipping', value: 'shipping' },
                                ]}
                            />
                            <FieldError message={errors.type?.[0]} />
                        </div>
                        <div className="space-y-2 md:col-span-2">
                            <Input name="address_line1" placeholder="Address line 1" />
                            <FieldError message={errors.address_line1?.[0]} />
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
                            <Input name="address_line2" placeholder="Address line 2" />
                            <FieldError message={errors.address_line2?.[0]} />
                        </div>
                        <div className="flex items-center justify-between gap-3 md:col-span-6">
                            <label className="flex items-center gap-2 text-sm font-medium text-slate-600">
                                <input className="h-4 w-4 rounded border-slate-300" name="is_default" type="checkbox" />
                                Default
                            </label>
                            <Button disabled={isSubmitting} type="submit" variant="blue">Add address</Button>
                        </div>
                    </form>
                </Card>
            ) : null}
        </div>
    );
}
