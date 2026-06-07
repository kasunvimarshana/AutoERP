import { useEffect, useState } from 'react';
import { fieldError, toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { DecimalInput } from '@/shared/components/DecimalInput';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { getCustomerCreditProfile, updateCustomerCreditProfile } from '../customerApi';
import type { CustomerCreditProfile } from '../customerTypes';
import { CustomerRelationHeader } from './CustomerRelationHeader';

const empty: CustomerCreditProfile = { credit_limit: '0.000000', credit_period_days: null, warning_threshold_percent: '80.000000', allow_over_credit: false, allow_partial_payment: true, is_active: true };
export default function CustomerCreditProfileTab({ customerId }: { customerId: number }) {
    const [form, setForm] = useState<CustomerCreditProfile>(empty);
    const [loading, setLoading] = useState(true);
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    useEffect(() => {
        const controller = new AbortController();
        getCustomerCreditProfile(customerId, controller.signal).then((profile) => { if (!controller.signal.aborted && profile) setForm(profile); }).catch((requestError) => !controller.signal.aborted && setError(toApiError(requestError))).finally(() => !controller.signal.aborted && setLoading(false));
        return () => controller.abort();
    }, [customerId]);
    const set = <K extends keyof CustomerCreditProfile>(key: K, value: CustomerCreditProfile[K]) => setForm((current) => ({ ...current, [key]: value }));
    if (loading) return <LoadingState />;
    return <><CustomerRelationHeader title="Credit profile" description="Customer credit policy reference only; balances remain owned by Invoice, Payment, and Finance." /><ErrorAlert error={error} /><form className="max-w-3xl space-y-4" onSubmit={(event) => { event.preventDefault(); void save(); }}><div className="grid gap-4 sm:grid-cols-2">
        <DecimalInput label="Credit limit" value={form.credit_limit} onChange={(event) => set('credit_limit', event.target.value)} error={fieldError(error, 'credit_limit')} />
        <Input label="Credit period days" type="number" min="0" value={form.credit_period_days ?? ''} onChange={(event) => set('credit_period_days', event.target.value ? Number(event.target.value) : null)} />
        <DecimalInput label="Warning threshold percent" value={form.warning_threshold_percent} onChange={(event) => set('warning_threshold_percent', event.target.value)} error={fieldError(error, 'warning_threshold_percent')} /></div>
        <div className="flex flex-wrap gap-6 text-sm"><label><input className="mr-2" type="checkbox" checked={form.allow_over_credit} onChange={(event) => set('allow_over_credit', event.target.checked)} />Allow over credit</label><label><input className="mr-2" type="checkbox" checked={form.allow_partial_payment} onChange={(event) => set('allow_partial_payment', event.target.checked)} />Allow partial payment</label><label><input className="mr-2" type="checkbox" checked={form.is_active} onChange={(event) => set('is_active', event.target.checked)} />Active</label></div><Button type="submit" loading={submitting}>Save credit profile</Button></form></>;
    async function save() { setSubmitting(true); setError(null); try { setForm(await updateCustomerCreditProfile(customerId, form)); } catch (requestError) { setError(toApiError(requestError)); } finally { setSubmitting(false); } }
}
