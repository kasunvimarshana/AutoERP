import { useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import { hasPermission } from '@/modules/auth/accessControl';
import { useAuth } from '@/modules/auth/AuthProvider';
import { fieldError, toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Panel } from '@/shared/components/Panel';
import { Select } from '@/shared/components/Select';
import { useApi } from '@/shared/hooks/useApi';
import { addTaxRate, getTax, getTaxLookups, updateTax } from '../taxApi';
import { taxPermissions } from '../taxPermissions';
import type { Tax, TaxCalculationMethod, TaxLookups, TaxPayload, TaxRate } from '../taxTypes';

function taxPayload(tax: Tax): TaxPayload {
    return {
        code: tax.code,
        name: tax.name,
        description: tax.description ?? '',
        tax_type: tax.tax_type,
        calculation_method: tax.calculation_method,
        is_withholding: tax.is_withholding,
        recoverable: tax.recoverable,
        payable: tax.payable,
        receivable: tax.receivable,
        active: tax.active,
    };
}

export default function TaxEditPage() {
    const auth = useAuth();
    const canViewTax = hasPermission(auth, taxPermissions.taxesView);
    const canViewLookups = hasPermission(auth, taxPermissions.lookupsView);
    const id = Number(useParams().id);
    const tax = useApi((signal) => getTax(id, signal), [id], canViewTax);
    const lookups = useApi((signal) => getTaxLookups(signal), [], canViewLookups);

    if (!canViewTax) {
        return (
            <>
                <ContentHeader title="Edit tax" actions={<Link className="text-sm font-semibold text-sky-700 hover:underline" to="/tax/taxes">Back to taxes</Link>} />
                <Panel title="Tax view permission required">
                    <p className="text-sm text-slate-600">Tax details must be viewable before they can be edited.</p>
                </Panel>
            </>
        );
    }

    if (tax.loading && !tax.data) return <LoadingState />;
    if (!tax.data) {
        return (
            <>
                <ContentHeader title="Edit tax" actions={<Link className="text-sm font-semibold text-sky-700 hover:underline" to="/tax/taxes">Back to taxes</Link>} />
                <ErrorAlert error={tax.error} />
            </>
        );
    }

    return (
        <TaxEditor
            key={tax.data.id}
            tax={tax.data}
            lookups={lookups.data}
            loadError={tax.error ?? lookups.error}
            reload={tax.reload}
        />
    );
}

function TaxEditor({ tax, lookups, loadError, reload }: {
    tax: Tax;
    lookups: TaxLookups | null;
    loadError: ApiError | null;
    reload: () => void;
}) {
    const navigate = useNavigate();
    const [form, setForm] = useState<TaxPayload>(() => taxPayload(tax));
    const [rate, setRate] = useState({ rate: '0.000000', effective_from: '', effective_to: '', active: true });
    const [error, setError] = useState<ApiError | null>(null);
    const [saving, setSaving] = useState(false);
    const [addingRate, setAddingRate] = useState(false);

    const set = <K extends keyof TaxPayload>(key: K, value: TaxPayload[K]) => setForm((current) => ({ ...current, [key]: value }));
    const rateColumns: DataColumn<TaxRate>[] = [
        { key: 'rate', header: 'Rate', render: (row) => row.rate },
        { key: 'from', header: 'From', render: (row) => row.effective_from },
        { key: 'to', header: 'To', render: (row) => row.effective_to ?? 'Open' },
        { key: 'status', header: 'Status', render: (row) => row.active ? 'Active' : 'Inactive' },
    ];

    async function saveTax() {
        setSaving(true);
        setError(null);
        try {
            await updateTax(tax.id, form);
            navigate('/tax/taxes');
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setSaving(false);
        }
    }

    async function saveRate() {
        setAddingRate(true);
        setError(null);
        try {
            await addTaxRate(tax.id, { ...rate, effective_to: rate.effective_to || null });
            setRate({ rate: '0.000000', effective_from: '', effective_to: '', active: true });
            reload();
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setAddingRate(false);
        }
    }

    return (
        <>
            <ContentHeader title={`Edit ${tax.code}`} description="Maintain tax metadata and effective-dated rates." actions={<Link className="text-sm font-semibold text-sky-700 hover:underline" to="/tax/taxes">Back to taxes</Link>} />
            <ErrorAlert error={error ?? loadError} />
            <Panel title="Tax details">
                <div className="grid gap-4 md:grid-cols-2">
                    <Input label="Code" value={form.code} error={fieldError(error, 'code')} onChange={(event) => set('code', event.target.value)} />
                    <Input label="Name" value={form.name} error={fieldError(error, 'name')} onChange={(event) => set('name', event.target.value)} />
                    <Input label="Tax type" value={form.tax_type} error={fieldError(error, 'tax_type')} onChange={(event) => set('tax_type', event.target.value)} />
                    <Select label="Calculation method" value={form.calculation_method} error={fieldError(error, 'calculation_method')} options={(lookups?.calculation_methods ?? ['percentage', 'fixed', 'inclusive', 'exclusive', 'compound']).map((value) => ({ value, label: value }))} onChange={(event) => set('calculation_method', event.target.value as TaxCalculationMethod)} />
                    <label className="flex items-center gap-2 text-sm"><input type="checkbox" checked={form.is_withholding} onChange={(event) => set('is_withholding', event.target.checked)} /> Withholding tax</label>
                    <label className="flex items-center gap-2 text-sm"><input type="checkbox" checked={form.recoverable} onChange={(event) => set('recoverable', event.target.checked)} /> Recoverable</label>
                    <label className="flex items-center gap-2 text-sm"><input type="checkbox" checked={form.payable} onChange={(event) => set('payable', event.target.checked)} /> Payable</label>
                    <label className="flex items-center gap-2 text-sm"><input type="checkbox" checked={form.receivable} onChange={(event) => set('receivable', event.target.checked)} /> Receivable</label>
                    <label className="flex items-center gap-2 text-sm"><input type="checkbox" checked={form.active} onChange={(event) => set('active', event.target.checked)} /> Active</label>
                </div>
                <div className="mt-4"><Button loading={saving} onClick={() => void saveTax()}>Update tax</Button></div>
            </Panel>
            <Panel title="Rates" className="mt-4">
                <DataTable rows={tax.rates ?? []} columns={rateColumns} rowKey={(row) => row.id} />
                <div className="mt-4 grid gap-4 md:grid-cols-4">
                    <Input label="New rate" value={rate.rate} error={fieldError(error, 'rate')} onChange={(event) => setRate({ ...rate, rate: event.target.value })} />
                    <Input label="Effective from" type="date" value={rate.effective_from} error={fieldError(error, 'effective_from')} onChange={(event) => setRate({ ...rate, effective_from: event.target.value })} />
                    <Input label="Effective to" type="date" value={rate.effective_to} onChange={(event) => setRate({ ...rate, effective_to: event.target.value })} />
                    <Button className="mt-7" loading={addingRate} onClick={() => void saveRate()}>Add rate</Button>
                </div>
            </Panel>
        </>
    );
}
