import { FormEvent, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { ApiError } from '../../../services/api/apiErrors';
import { PreviewPanel } from '../../../shared/components/business/PreviewPanel';
import { FieldError } from '../../../shared/components/forms/FieldError';
import { FormSection } from '../../../shared/components/forms/FormSection';
import { Button } from '../../../shared/components/ui/Button';
import { Input } from '../../../shared/components/ui/Input';
import { Select } from '../../../shared/components/ui/Select';
import { Textarea } from '../../../shared/components/ui/Textarea';
import { supplierApi } from '../services/supplierApi';
import type { Supplier, SupplierFormInput, SupplierStatus } from '../types/supplier.types';

function FieldLabel({ children }: { children: string }) {
    return <label className="text-xs font-bold uppercase tracking-wide text-slate-500">{children}</label>;
}

const statusOptions = [
    { label: 'Draft', value: 'draft' },
    { label: 'Pending Approval', value: 'pending_approval' },
    { label: 'Active', value: 'active' },
    { label: 'Inactive', value: 'inactive' },
    { label: 'Blocked', value: 'blocked' },
];

export function SupplierForm({ mode, supplier }: { mode: 'create' | 'edit'; supplier?: Supplier }) {
    const navigate = useNavigate();
    const [errors, setErrors] = useState<Record<string, string[]>>({});
    const [formError, setFormError] = useState('');
    const [isSubmitting, setIsSubmitting] = useState(false);

    async function handleSubmit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        setErrors({});
        setFormError('');
        setIsSubmitting(true);

        const formData = new FormData(event.currentTarget);
        const input: SupplierFormInput = {
            category: String(formData.get('category') ?? ''),
            code: String(formData.get('supplier_code') ?? ''),
            displayName: String(formData.get('display_name') ?? ''),
            email: String(formData.get('email') ?? ''),
            legalName: String(formData.get('legal_name') ?? ''),
            mobile: String(formData.get('mobile') ?? ''),
            name: String(formData.get('supplier_name') ?? ''),
            notes: String(formData.get('notes') ?? ''),
            phone: String(formData.get('phone') ?? ''),
            registrationNumber: String(formData.get('registration_number') ?? ''),
            status: String(formData.get('status') ?? 'draft') as SupplierStatus,
            supplierType: String(formData.get('supplier_type') ?? ''),
            taxNumber: String(formData.get('tax_number') ?? ''),
            vatNumber: String(formData.get('vat_number') ?? ''),
            website: String(formData.get('website') ?? ''),
        };

        try {
            const response =
                mode === 'edit' && supplier
                    ? await supplierApi.updateSupplier(supplier.id, input)
                    : await supplierApi.createSupplier(input);

            navigate(`/suppliers/${response.data.id}`);
        } catch (error) {
            if (error instanceof ApiError) {
                setErrors(error.errors);
                setFormError(error.message);
            } else {
                setFormError('Unable to save supplier.');
            }
        } finally {
            setIsSubmitting(false);
        }
    }

    return (
        <form className="grid gap-5 xl:grid-cols-[1fr_340px]" onSubmit={handleSubmit}>
            <div className="space-y-5">
                {formError ? <div className="rounded-lg border border-red-100 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">{formError}</div> : null}

                <FormSection description="Supplier profiles are saved independently from user login access. User access is optional and managed after save." title="Basic Information">
                    <div className="grid gap-4 md:grid-cols-2">
                        <div className="space-y-2">
                            <FieldLabel>Supplier code</FieldLabel>
                            <Input defaultValue={supplier?.code} name="supplier_code" placeholder="SUP-0001" />
                            <FieldError message={errors.supplier_code?.[0]} />
                        </div>
                        <div className="space-y-2">
                            <FieldLabel>Supplier name</FieldLabel>
                            <Input defaultValue={supplier?.name} name="supplier_name" placeholder="Legal or trading name" />
                            <FieldError message={errors.supplier_name?.[0]} />
                        </div>
                        <div className="space-y-2">
                            <FieldLabel>Legal name</FieldLabel>
                            <Input defaultValue={supplier?.legalName} name="legal_name" placeholder="Registered legal name" />
                            <FieldError message={errors.legal_name?.[0]} />
                        </div>
                        <div className="space-y-2">
                            <FieldLabel>Display name</FieldLabel>
                            <Input defaultValue={supplier?.displayName} name="display_name" placeholder="Name shown in UI" />
                            <FieldError message={errors.display_name?.[0]} />
                        </div>
                        <div className="space-y-2">
                            <FieldLabel>Supplier type</FieldLabel>
                            <Select
                                defaultValue={supplier?.supplierType}
                                name="supplier_type"
                                options={[
                                    { label: 'Stock supplier', value: 'stock' },
                                    { label: 'Service provider', value: 'service' },
                                    { label: 'Fleet provider', value: 'fleet' },
                                    { label: 'General supplier', value: 'general' },
                                ]}
                                placeholder="Select type"
                            />
                            <FieldError message={errors.supplier_type?.[0]} />
                        </div>
                        <div className="space-y-2">
                            <FieldLabel>Category</FieldLabel>
                            <Select
                                defaultValue={supplier?.category}
                                name="category"
                                options={[
                                    { label: 'Parts Supplier', value: 'Parts Supplier' },
                                    { label: 'External Service Provider', value: 'External Service Provider' },
                                    { label: 'Fleet Provider', value: 'Fleet Provider' },
                                ]}
                                placeholder="Select category"
                            />
                        </div>
                        <div className="space-y-2">
                            <FieldLabel>Registration number</FieldLabel>
                            <Input defaultValue={supplier?.registrationNumber} name="registration_number" placeholder="Business registration" />
                            <FieldError message={errors.registration_number?.[0]} />
                        </div>
                        <div className="space-y-2">
                            <FieldLabel>Status</FieldLabel>
                            <Select defaultValue={supplier?.status ?? 'draft'} name="status" options={statusOptions} />
                            <FieldError message={errors.status?.[0]} />
                        </div>
                        <div className="space-y-2">
                            <FieldLabel>Tax number</FieldLabel>
                            <Input defaultValue={supplier?.taxNumber} name="tax_number" placeholder="TIN" />
                            <FieldError message={errors.tax_number?.[0]} />
                        </div>
                        <div className="space-y-2">
                            <FieldLabel>VAT number</FieldLabel>
                            <Input defaultValue={supplier?.vatNumber} name="vat_number" placeholder="VAT registration" />
                            <FieldError message={errors.vat_number?.[0]} />
                        </div>
                        <div className="space-y-2">
                            <FieldLabel>Email</FieldLabel>
                            <Input defaultValue={supplier?.email} name="email" placeholder="accounts@supplier.example" type="email" />
                            <FieldError message={errors.email?.[0]} />
                        </div>
                        <div className="space-y-2">
                            <FieldLabel>Phone</FieldLabel>
                            <Input defaultValue={supplier?.phone} name="phone" placeholder="+94..." />
                            <FieldError message={errors.phone?.[0]} />
                        </div>
                        <div className="space-y-2">
                            <FieldLabel>Mobile</FieldLabel>
                            <Input defaultValue={supplier?.mobile} name="mobile" placeholder="+94..." />
                            <FieldError message={errors.mobile?.[0]} />
                        </div>
                        <div className="space-y-2">
                            <FieldLabel>Website</FieldLabel>
                            <Input defaultValue={supplier?.website} name="website" placeholder="https://supplier.example" />
                            <FieldError message={errors.website?.[0]} />
                        </div>
                        <div className="space-y-2 md:col-span-2">
                            <FieldLabel>Notes</FieldLabel>
                            <Textarea name="notes" placeholder="Procurement notes, compliance notes, preferred delivery windows." />
                            <FieldError message={errors.notes?.[0]} />
                        </div>
                    </div>
                </FormSection>

                <FormSection description="These values are collected for backend validation. Authoritative payable balances, taxes, finance accounts, and purchase totals stay in backend." title="Finance, Tax, and Optional Setup">
                    <div className="grid gap-4 md:grid-cols-2">
                        {['Finance Defaults', 'Primary Contact', 'Primary Address', 'Primary Bank Account', 'Tax Profile', 'Optional User Access'].map((label) => (
                            <div className="rounded-lg border border-slate-200 bg-slate-50 p-4" key={label}>
                                <p className="text-sm font-semibold text-slate-900">{label}</p>
                                <p className="mt-1 text-sm text-slate-500">Managed in a separate detail tab after supplier save.</p>
                            </div>
                        ))}
                    </div>
                </FormSection>

                <div className="flex justify-end gap-3">
                    <Link to="/suppliers">
                        <Button variant="secondary">Cancel</Button>
                    </Link>
                    <Button disabled={isSubmitting}>Save Draft</Button>
                    <Button disabled={isSubmitting} type="submit" variant="blue">{mode === 'edit' ? 'Update Supplier' : 'Create Supplier'}</Button>
                </div>
            </div>

            <PreviewPanel
                rows={[
                    { label: 'Tenant validation', value: 'Backend-owned' },
                    { label: 'Payable balance', value: 'Readonly backend value later' },
                    { label: 'User account', value: 'Optional, never automatic' },
                ]}
                title="Supplier validation preview"
            />
        </form>
    );
}
