import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { hasPermission } from '@/modules/auth/accessControl';
import { useAuth } from '@/modules/auth/AuthProvider';
import { fieldError, toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { Panel } from '@/shared/components/Panel';
import { Select } from '@/shared/components/Select';
import { Textarea } from '@/shared/components/Textarea';
import { useApi } from '@/shared/hooks/useApi';
import { createTax, addTaxRate, getTaxLookups } from '../taxApi';
import { taxPermissions } from '../taxPermissions';
import type { TaxCalculationMethod, TaxPayload } from '../taxTypes';

const blank: TaxPayload = {
    code: '',
    name: '',
    description: '',
    tax_type: '',
    calculation_method: 'percentage',
    is_withholding: false,
    recoverable: false,
    payable: false,
    receivable: false,
    active: true,
};

export default function TaxCreatePage() {
    const auth = useAuth();
    const canViewLookups = hasPermission(auth, taxPermissions.lookupsView);
    const navigate = useNavigate();
    const lookups = useApi((signal) => getTaxLookups(signal), [], canViewLookups);
    const [form, setForm] = useState<TaxPayload>(blank);
    const [rate, setRate] = useState({ rate: '0.000000', effective_from: '', effective_to: '', active: true });
    const [error, setError] = useState<ApiError | null>(null);
    const [saving, setSaving] = useState(false);

    const set = <K extends keyof TaxPayload>(key: K, value: TaxPayload[K]) => setForm((current) => ({ ...current, [key]: value }));

    return (
        <>
            <ContentHeader title="Create tax" description="Tax rules are configurable master data; rates are effective-dated." />
            <ErrorAlert error={error ?? lookups.error} />
            <Panel title="Tax details">
                <div className="grid gap-4 md:grid-cols-2">
                    <Input label="Code" value={form.code} error={fieldError(error, 'code')} onChange={(event) => set('code', event.target.value)} />
                    <Input label="Name" value={form.name} error={fieldError(error, 'name')} onChange={(event) => set('name', event.target.value)} />
                    <Input label="Tax type" value={form.tax_type} error={fieldError(error, 'tax_type')} onChange={(event) => set('tax_type', event.target.value)} placeholder="VAT, GST, WHT, custom..." />
                    <Select label="Calculation method" value={form.calculation_method} error={fieldError(error, 'calculation_method')} options={(lookups.data?.calculation_methods ?? ['percentage', 'fixed', 'inclusive', 'exclusive', 'compound']).map((value) => ({ value, label: value }))} onChange={(event) => set('calculation_method', event.target.value as TaxCalculationMethod)} />
                    <Textarea label="Description" value={form.description ?? ''} onChange={(event) => set('description', event.target.value)} className="md:col-span-2" />
                    <label className="flex items-center gap-2 text-sm"><input type="checkbox" checked={form.is_withholding} onChange={(event) => set('is_withholding', event.target.checked)} /> Withholding tax</label>
                    <label className="flex items-center gap-2 text-sm"><input type="checkbox" checked={form.recoverable} onChange={(event) => set('recoverable', event.target.checked)} /> Recoverable</label>
                    <label className="flex items-center gap-2 text-sm"><input type="checkbox" checked={form.payable} onChange={(event) => set('payable', event.target.checked)} /> Payable</label>
                    <label className="flex items-center gap-2 text-sm"><input type="checkbox" checked={form.receivable} onChange={(event) => set('receivable', event.target.checked)} /> Receivable</label>
                    <label className="flex items-center gap-2 text-sm"><input type="checkbox" checked={form.active} onChange={(event) => set('active', event.target.checked)} /> Active</label>
                </div>
            </Panel>
            <Panel title="Initial rate" className="mt-4">
                <div className="grid gap-4 md:grid-cols-4">
                    <Input label="Rate" value={rate.rate} error={fieldError(error, 'rate')} onChange={(event) => setRate({ ...rate, rate: event.target.value })} />
                    <Input label="Effective from" type="date" value={rate.effective_from} error={fieldError(error, 'effective_from')} onChange={(event) => setRate({ ...rate, effective_from: event.target.value })} />
                    <Input label="Effective to" type="date" value={rate.effective_to} onChange={(event) => setRate({ ...rate, effective_to: event.target.value })} />
                    <label className="mt-8 flex items-center gap-2 text-sm"><input type="checkbox" checked={rate.active} onChange={(event) => setRate({ ...rate, active: event.target.checked })} /> Active rate</label>
                </div>
            </Panel>
            <div className="mt-4">
                <Button loading={saving} onClick={async () => {
                    setSaving(true);
                    setError(null);
                    try {
                        const tax = await createTax(form);
                        if (rate.effective_from) await addTaxRate(tax.id, { ...rate, effective_to: rate.effective_to || null });
                        navigate('/tax/taxes');
                    } catch (requestError) {
                        setError(toApiError(requestError));
                    } finally {
                        setSaving(false);
                    }
                }}>Save tax</Button>
            </div>
        </>
    );
}
