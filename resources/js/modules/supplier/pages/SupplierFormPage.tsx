import { useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { createSupplier, getSupplier, updateSupplier } from '../supplierApi';
import type { SupplierPayload } from '../types';
import { ApiError, fieldError, toApiError } from '@/shared/api/apiError';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { Panel } from '@/shared/components/Panel';
import { Input } from '@/shared/components/Input';
import { Select } from '@/shared/components/Select';
import { Textarea } from '@/shared/components/Textarea';
import { Button } from '@/shared/components/Button';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';

const supplierTypes = ['company', 'individual', 'government', 'internal', 'foreign', 'other'].map((value) => ({ value, label: value.replaceAll('_', ' ') }));
const statuses = ['pending_approval', 'active', 'inactive', 'on_hold', 'blacklisted'].map((value) => ({ value, label: value.replaceAll('_', ' ') }));
const emptyForm: SupplierPayload = { code: '', name: '', supplier_type: 'company', status: 'pending_approval', is_credit_allowed: true, is_advance_allowed: true };

export default function SupplierFormPage() {
    const { id } = useParams();
    const supplierId = id ? Number(id) : null;
    const navigate = useNavigate();
    const [form, setForm] = useState<SupplierPayload>(emptyForm);
    const [loading, setLoading] = useState(Boolean(supplierId));
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    const [oneShot, setOneShot] = useState(false);

    useEffect(() => {
        if (!supplierId) return;
        const controller = new AbortController();
        getSupplier(supplierId, controller.signal)
            .then((supplier) => setForm({
                code: supplier.code ?? '',
                name: supplier.name,
                supplier_type: supplier.supplier_type,
                legal_name: supplier.legal_name ?? '',
                display_name: supplier.display_name ?? '',
                email: supplier.email ?? '',
                phone: supplier.phone ?? '',
                mobile: supplier.mobile ?? '',
                website: supplier.website ?? '',
                credit_limit: supplier.credit_limit ?? '',
                opening_balance: supplier.opening_balance ?? '',
                is_credit_allowed: supplier.is_credit_allowed,
                is_advance_allowed: supplier.is_advance_allowed,
                notes: supplier.notes ?? '',
            }))
            .catch((requestError) => !controller.signal.aborted && setError(toApiError(requestError)))
            .finally(() => !controller.signal.aborted && setLoading(false));
        return () => controller.abort();
    }, [supplierId]);

    const set = <K extends keyof SupplierPayload>(key: K, value: SupplierPayload[K]) => setForm((current) => ({ ...current, [key]: value }));

    if (loading) return <LoadingState />;
    return (
        <>
            <ContentHeader title={supplierId ? 'Edit supplier' : 'New supplier'} description={supplierId ? 'Updates use the standalone supplier endpoint.' : 'Create a standalone supplier or include initial relations in one transaction.'} />
            <form
                className="space-y-5"
                onSubmit={async (event) => {
                    event.preventDefault();
                    setSubmitting(true);
                    setError(null);
                    try {
                        const saved = supplierId ? await updateSupplier(supplierId, form) : await createSupplier(form);
                        navigate(`/suppliers/${saved.id}`);
                    } catch (requestError) {
                        setError(toApiError(requestError));
                    } finally {
                        setSubmitting(false);
                    }
                }}
            >
                <ErrorAlert error={error} />
                <Panel title="Identity">
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <Input label="Code" value={form.code} onChange={(event) => set('code', event.target.value)} error={fieldError(error, 'code')} required />
                        <Input label="Name" value={form.name} onChange={(event) => set('name', event.target.value)} error={fieldError(error, 'name')} required />
                        <Select label="Type" value={form.supplier_type} onChange={(event) => set('supplier_type', event.target.value)} options={supplierTypes} error={fieldError(error, 'supplier_type')} />
                        {!supplierId && <Select label="Initial status" value={form.status} onChange={(event) => set('status', event.target.value)} options={statuses} />}
                        <Input label="Legal name" value={form.legal_name ?? ''} onChange={(event) => set('legal_name', event.target.value)} />
                        <Input label="Display name" value={form.display_name ?? ''} onChange={(event) => set('display_name', event.target.value)} />
                    </div>
                </Panel>
                <Panel title="Contact and credit">
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <Input label="Email" type="email" value={form.email ?? ''} onChange={(event) => set('email', event.target.value)} error={fieldError(error, 'email')} />
                        <Input label="Phone" value={form.phone ?? ''} onChange={(event) => set('phone', event.target.value)} />
                        <Input label="Mobile" value={form.mobile ?? ''} onChange={(event) => set('mobile', event.target.value)} />
                        <Input label="Website" type="url" value={form.website ?? ''} onChange={(event) => set('website', event.target.value)} />
                        <Input label="Credit limit" type="number" min="0" step="0.000001" value={form.credit_limit ?? ''} onChange={(event) => set('credit_limit', event.target.value)} error={fieldError(error, 'credit_limit')} />
                        <Input label="Opening balance" type="number" min="0" step="0.000001" value={form.opening_balance ?? ''} onChange={(event) => set('opening_balance', event.target.value)} />
                    </div>
                    <div className="mt-4 flex flex-wrap gap-6 text-sm">
                        <label><input className="mr-2" type="checkbox" checked={form.is_credit_allowed ?? false} onChange={(event) => set('is_credit_allowed', event.target.checked)} />Credit allowed</label>
                        <label><input className="mr-2" type="checkbox" checked={form.is_advance_allowed ?? false} onChange={(event) => set('is_advance_allowed', event.target.checked)} />Advance allowed</label>
                    </div>
                    <div className="mt-4"><Textarea label="Notes" value={form.notes ?? ''} onChange={(event) => set('notes', event.target.value)} /></div>
                </Panel>
                {!supplierId && (
                    <Panel title="One-shot relations">
                        <label className="flex items-center gap-2 text-sm font-medium"><input type="checkbox" checked={oneShot} onChange={(event) => setOneShot(event.target.checked)} />Include initial contact and address</label>
                        {oneShot && <InitialRelations form={form} setForm={setForm} error={error} />}
                    </Panel>
                )}
                <div className="flex justify-end gap-2">
                    <Button type="button" variant="secondary" onClick={() => navigate(-1)}>Cancel</Button>
                    <Button type="submit" loading={submitting}>{supplierId ? 'Save supplier' : 'Create supplier'}</Button>
                </div>
            </form>
        </>
    );
}

function InitialRelations({ form, setForm, error }: { form: SupplierPayload; setForm: (value: SupplierPayload) => void; error: ApiError | null }) {
    const contact = form.contacts?.[0] ?? { contact_name: '', is_primary: true };
    const address = form.addresses?.[0] ?? { address_type: 'registered', address_line_1: '', is_primary: true };
    return (
        <div className="mt-4 grid gap-6 lg:grid-cols-2">
            <div className="space-y-3">
                <h3 className="text-sm font-semibold">Primary contact</h3>
                <Input label="Contact name" value={contact.contact_name} onChange={(event) => setForm({ ...form, contacts: [{ ...contact, contact_name: event.target.value }] })} error={fieldError(error, 'contacts.0.contact_name')} />
                <Input label="Contact email" type="email" value={contact.email ?? ''} onChange={(event) => setForm({ ...form, contacts: [{ ...contact, email: event.target.value }] })} />
            </div>
            <div className="space-y-3">
                <h3 className="text-sm font-semibold">Registered address</h3>
                <Input label="Address line 1" value={address.address_line_1} onChange={(event) => setForm({ ...form, addresses: [{ ...address, address_line_1: event.target.value }] })} error={fieldError(error, 'addresses.0.address_line_1')} />
                <Input label="City" value={address.city ?? ''} onChange={(event) => setForm({ ...form, addresses: [{ ...address, city: event.target.value }] })} />
            </div>
        </div>
    );
}
