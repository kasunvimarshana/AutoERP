import { fieldError, type ApiError } from '@/shared/api/apiError';
import { Input } from '@/shared/components/Input';
import { Panel } from '@/shared/components/Panel';
import { Select } from '@/shared/components/Select';
import { Textarea } from '@/shared/components/Textarea';
import type { NamedResource } from '@/shared/types/common';
import { customerStatuses, customerTypes, preferredCommunicationChannels, type CustomerPayload } from '../customerTypes';
import { CustomerCurrencySelect } from './CustomerCurrencySelect';

export function CustomerForm({ value, onChange, currency, onCurrencyChange, error, creating = false }: {
    value: CustomerPayload;
    onChange: (value: CustomerPayload) => void;
    currency: NamedResource | null;
    onCurrencyChange: (value: NamedResource | null) => void;
    error: ApiError | null;
    creating?: boolean;
}) {
    const set = <K extends keyof CustomerPayload>(key: K, next: CustomerPayload[K]) => onChange({ ...value, [key]: next });
    const options = (entries: readonly string[]) => entries.map((entry) => ({ value: entry, label: entry.replaceAll('_', ' ') }));

    return <div className="space-y-5">
        <Panel title="Customer identity">
            <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                {!creating && <Input label="Customer number" value={value.customer_number ?? ''} onChange={(event) => set('customer_number', event.target.value || null)} error={fieldError(error, 'customer_number') ?? fieldError(error, 'customer.customer_number')} />}
                <Input label="Code" value={value.code} onChange={(event) => set('code', event.target.value)} error={fieldError(error, 'code') ?? fieldError(error, 'customer.code')} required />
                <Input label="Name" value={value.name} onChange={(event) => set('name', event.target.value)} error={fieldError(error, 'name') ?? fieldError(error, 'customer.name')} required />
                <Select label="Customer type" value={value.customer_type} onChange={(event) => set('customer_type', event.target.value)} options={options(customerTypes)} error={fieldError(error, 'customer_type') ?? fieldError(error, 'customer.customer_type')} />
                {creating && <Select label="Initial status" value={value.status ?? 'pending_approval'} onChange={(event) => set('status', event.target.value)} options={options(customerStatuses)} error={fieldError(error, 'status') ?? fieldError(error, 'customer.status')} />}
                {!creating && <Input label="Legal name" value={value.legal_name ?? ''} onChange={(event) => set('legal_name', event.target.value || null)} />}
                <Input label="Display name" value={value.display_name ?? ''} onChange={(event) => set('display_name', event.target.value || null)} />
            </div>
        </Panel>
        <Panel title="Contact and commercial defaults">
            <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <Input label="Email" type="email" value={value.email ?? ''} onChange={(event) => set('email', event.target.value || null)} error={fieldError(error, 'email') ?? fieldError(error, 'customer.email')} />
                <Input label="Phone" value={value.phone ?? ''} onChange={(event) => set('phone', event.target.value || null)} />
                <Input label="WhatsApp Number" value={value.mobile ?? ''} onChange={(event) => set('mobile', event.target.value || null)} />
                {!creating && <Input label="Website" type="url" value={value.website ?? ''} onChange={(event) => set('website', event.target.value || null)} />}
                <CustomerCurrencySelect value={currency} onChange={(next) => { onCurrencyChange(next); set('default_currency_id', next ? Number(next.id) : null); }} error={fieldError(error, 'default_currency_id') ?? fieldError(error, 'customer.default_currency_id')} />
                <Select label="Preferred channel" value={value.preferred_communication_channel ?? ''} onChange={(event) => set('preferred_communication_channel', event.target.value || null)} options={options(preferredCommunicationChannels)} error={fieldError(error, 'preferred_communication_channel') ?? fieldError(error, 'customer.preferred_communication_channel')} />
            </div>
            <p className="mt-3 text-sm text-slate-500">Credit and advance rules are maintained in the dedicated Credit Profile workspace. Opening balances are created through governed financial documents.</p>
            <div className="mt-4 flex flex-wrap gap-6 text-sm">
                <label><input className="mr-2" type="checkbox" checked={value.is_tax_exempt} onChange={(event) => set('is_tax_exempt', event.target.checked)} />Tax exempt</label>
                <label><input className="mr-2" type="checkbox" checked={value.marketing_consent} onChange={(event) => set('marketing_consent', event.target.checked)} />Marketing consent</label>
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
