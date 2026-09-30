import { useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { useMutationFormGuard } from '@/shared/hooks/useMutationFormGuard';
import type { NamedResource } from '@/shared/types/common';
import { getSupplier, updateSupplier } from './supplierApi';
import type { SupplierPayload } from './supplierTypes';
import { SupplierForm } from './components/SupplierForm';

export default function SupplierEditPage() {
    const supplierId = Number(useParams().id);
    const navigate = useNavigate();
    const [form, setForm] = useState<SupplierPayload | null>(null);
    const [currency, setCurrency] = useState<NamedResource | null>(null);
    const [loading, setLoading] = useState(true);
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    const formGuard = useMutationFormGuard(submitting);
    useEffect(() => {
        const controller = new AbortController();
        getSupplier(supplierId, controller.signal).then((supplier) => {
            if (controller.signal.aborted) return;
            setForm({ row_version: supplier.row_version, code: supplier.code, name: supplier.legal_name ?? supplier.name, supplier_type: supplier.supplier_type, display_name: supplier.display_name, email: supplier.email, phone: supplier.phone, mobile: supplier.mobile, website: supplier.website, default_currency_id: supplier.default_currency ? Number(supplier.default_currency.id) : null, tax_registration_number: supplier.tax_registration_number, vat_number: supplier.vat_number, svat_number: supplier.svat_number, business_registration_number: supplier.business_registration_number, credit_limit: supplier.credit_limit, is_credit_allowed: supplier.is_credit_allowed, is_advance_allowed: supplier.is_advance_allowed, notes: supplier.notes });
            setCurrency(supplier.default_currency ?? null);
        }).catch((requestError) => !controller.signal.aborted && setError(toApiError(requestError))).finally(() => !controller.signal.aborted && setLoading(false));
        return () => controller.abort();
    }, [supplierId]);
    if (loading) return <LoadingState />;
    if (!form) return <ErrorAlert error={error} />;
    async function save() {
        if (!form) return;
        setSubmitting(true); setError(null);
        try { const saved = await updateSupplier(supplierId, form); formGuard.markSaved(); navigate(`/suppliers/${saved.id}`); }
        catch (requestError) { setError(toApiError(requestError)); }
        finally { setSubmitting(false); }
    }
    return <><ContentHeader title="Edit supplier" description="Basic supplier profile only; owned relations remain on-demand in detail tabs." /><ErrorAlert error={error} /><form className="space-y-5" onSubmit={(event) => { event.preventDefault(); void save(); }}><SupplierForm value={form} onChange={(next) => { formGuard.markDirty(); setForm(next); }} currency={currency} onCurrencyChange={(next) => { formGuard.markDirty(); setCurrency(next); }} error={error} /><div className="flex justify-end gap-2"><Button type="button" variant="secondary" onClick={() => navigate(-1)}>Cancel</Button><Button type="submit" loading={submitting}>Save supplier</Button></div></form></>;
}
