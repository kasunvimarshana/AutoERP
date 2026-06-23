import { useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { useMutationFormGuard } from '@/shared/hooks/useMutationFormGuard';
import type { NamedResource } from '@/shared/types/common';
import { getCustomer, updateCustomer } from './customerApi';
import type { CustomerPayload } from './customerTypes';
import { CustomerForm } from './components/CustomerForm';

export default function CustomerEditPage() {
    const customerId = Number(useParams().id);
    const navigate = useNavigate();
    const [form, setForm] = useState<CustomerPayload | null>(null);
    const [currency, setCurrency] = useState<NamedResource | null>(null);
    const [loading, setLoading] = useState(true);
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    const formGuard = useMutationFormGuard(submitting);
    useEffect(() => {
        const controller = new AbortController();
        getCustomer(customerId, controller.signal).then((customer) => {
            if (controller.signal.aborted) return;
            setForm({ customer_number: customer.customer_number, code: customer.code, name: customer.name, customer_type: customer.customer_type, legal_name: customer.legal_name, display_name: customer.display_name, email: customer.email, phone: customer.phone, mobile: customer.mobile, website: customer.website, default_currency_id: customer.default_currency ? Number(customer.default_currency.id) : null, tax_registration_number: customer.tax_registration_number, vat_number: customer.vat_number, svat_number: customer.svat_number, business_registration_number: customer.business_registration_number, credit_limit: customer.credit_limit, opening_balance: customer.opening_balance, is_credit_allowed: customer.is_credit_allowed, is_advance_allowed: customer.is_advance_allowed, is_tax_exempt: customer.is_tax_exempt, marketing_consent: customer.marketing_consent, preferred_communication_channel: customer.preferred_communication_channel ?? null, notes: customer.notes });
            setCurrency(customer.default_currency ?? null);
        }).catch((requestError) => !controller.signal.aborted && setError(toApiError(requestError))).finally(() => !controller.signal.aborted && setLoading(false));
        return () => controller.abort();
    }, [customerId]);
    if (loading) return <LoadingState />;
    if (!form) return <ErrorAlert error={error} />;
    async function save() {
        if (!form) return;
        setSubmitting(true); setError(null);
        try { const saved = await updateCustomer(customerId, form); formGuard.markSaved(); navigate(`/customers/${saved.id}`); }
        catch (requestError) { setError(toApiError(requestError)); }
        finally { setSubmitting(false); }
    }
    return <><ContentHeader title="Edit customer" description="Basic customer profile only; owned relations remain on-demand in detail tabs." /><ErrorAlert error={error} /><form className="space-y-5" onSubmit={(event) => { event.preventDefault(); void save(); }}><CustomerForm value={form} onChange={(next) => { formGuard.markDirty(); setForm(next); }} currency={currency} onCurrencyChange={(next) => { formGuard.markDirty(); setCurrency(next); }} error={error} /><div className="flex justify-end gap-2"><Button type="button" variant="secondary" onClick={() => navigate(-1)}>Cancel</Button><Button type="submit" loading={submitting}>Save customer</Button></div></form></>;
}
