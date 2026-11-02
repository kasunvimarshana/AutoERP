import { fieldError, type ApiError } from '@/shared/api/apiError';
import { Input } from '@/shared/components/Input';
import { Panel } from '@/shared/components/Panel';
import { Select } from '@/shared/components/Select';
import { Textarea } from '@/shared/components/Textarea';
import type { NamedResource } from '@/shared/types/common';
import { supplierStatuses, supplierTypes, type SupplierPayload } from '../supplierTypes';
import { SupplierCurrencySelect } from './SupplierCurrencySelect';

export function SupplierForm({ value, onChange, currency, onCurrencyChange, error, creating = false }: {
    value: SupplierPayload;
    onChange: (value: SupplierPayload) => void;
    currency: NamedResource | null;
    onCurrencyChange: (value: NamedResource | null) => void;
    error: ApiError | null;
    creating?: boolean;
}) {
    const set = <K extends keyof SupplierPayload>(key: K, next: SupplierPayload[K]) => onChange({ ...value, [key]: next });
    const options = (entries: readonly string[]) => entries.map((entry) => ({ value: entry, label: entry.replaceAll('_', ' ') }));

    return <div className="space-y-5">
        <Panel title="Supplier identity">
            <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <Input label="Supplier number" value={value.supplier_number ?? ''} onChange={(event) => set('supplier_number', event.target.value || null)} error={fieldError(error, 'supplier_number') ?? fieldError(error, 'supplier.supplier_number')} />
                <Input label="Code" value={value.code} onChange={(event) => set('code', event.target.value)} error={fieldError(error, 'code') ?? fieldError(error, 'supplier.code')} required />
                <Input label="Name" value={value.name} onChange={(event) => set('name', event.target.value)} error={fieldError(error, 'name') ?? fieldError(error, 'supplier.name')} required />
                <Select label="Supplier type" value={value.supplier_type} onChange={(event) => set('supplier_type', event.target.value)} options={options(supplierTypes)} error={fieldError(error, 'supplier_type') ?? fieldError(error, 'supplier.supplier_type')} />
                {creating && <Select label="Initial status" value={value.status ?? 'pending_approval'} onChange={(event) => set('status', event.target.value)} options={options(supplierStatuses)} error={fieldError(error, 'status') ?? fieldError(error, 'supplier.status')} />}
                <Input label="Legal name" value={value.legal_name ?? ''} onChange={(event) => set('legal_name', event.target.value || null)} />
                <Input label="Display name" value={value.display_name ?? ''} onChange={(event) => set('display_name', event.target.value || null)} />
            </div>
        </Panel>
        <Panel title="Contact and commercial defaults">
            <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <Input label="Email" type="email" value={value.email ?? ''} onChange={(event) => set('email', event.target.value || null)} error={fieldError(error, 'email') ?? fieldError(error, 'supplier.email')} />
                <Input label="Phone" value={value.phone ?? ''} onChange={(event) => set('phone', event.target.value || null)} />
                <Input label="Mobile" value={value.mobile ?? ''} onChange={(event) => set('mobile', event.target.value || null)} />
                <Input label="Website" type="url" value={value.website ?? ''} onChange={(event) => set('website', event.target.value || null)} />
                <SupplierCurrencySelect value={currency} onChange={(next) => { onCurrencyChange(next); set('default_currency_id', next ? Number(next.id) : null); }} error={fieldError(error, 'default_currency_id') ?? fieldError(error, 'supplier.default_currency_id')} />
                <Input label="Reference credit limit" value={value.credit_limit ?? '0.000000'} onChange={(event) => set('credit_limit', event.target.value)} error={fieldError(error, 'credit_limit') ?? fieldError(error, 'supplier.credit_limit')} />
            </div>
            <div className="mt-4 flex flex-wrap gap-6 text-sm">
                <label><input className="mr-2" type="checkbox" checked={value.is_credit_allowed} onChange={(event) => set('is_credit_allowed', event.target.checked)} />Credit allowed</label>
                <label><input className="mr-2" type="checkbox" checked={value.is_advance_allowed} onChange={(event) => set('is_advance_allowed', event.target.checked)} />Advance allowed</label>
            </div>
        </Panel>
        <Panel title="Registration">
            <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <Input label="Tax registration" value={value.tax_registration_number ?? ''} onChange={(event) => set('tax_registration_number', event.target.value || null)} />
                <Input label="VAT number" value={value.vat_number ?? ''} onChange={(event) => set('vat_number', event.target.value || null)} />
                <Input label="SVAT number" value={value.svat_number ?? ''} onChange={(event) => set('svat_number', event.target.value || null)} />
                <Input label="Business registration" value={value.business_registration_number ?? ''} onChange={(event) => set('business_registration_number', event.target.value || null)} />
            </div>
            <div className="mt-4"><Textarea label="Notes" value={value.notes ?? ''} onChange={(event) => set('notes', event.target.value || null)} /></div>
        </Panel>
    </div>;
}
