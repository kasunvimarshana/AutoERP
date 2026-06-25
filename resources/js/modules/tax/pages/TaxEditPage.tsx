import { useEffect, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';
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
import type { Tax, TaxCalculationMethod, TaxPayload, TaxRate } from '../taxTypes';

const emptyPayload: TaxPayload = {
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

export default function TaxEditPage() {
    const id = Number(useParams().id);
    const navigate = useNavigate();
    const tax = useApi((signal) => getTax(id, signal), [id]);
    const lookups = useApi((signal) => getTaxLookups(signal), []);
    const [form, setForm] = useState<TaxPayload>(emptyPayload);
    const [rate, setRate] = useState({ rate: '0.000000', effective_from: '', effective_to: '', active: true });
    const [error, setError] = useState<ApiError | null>(null);
    const [saving, setSaving] = useState(false);

    useEffect(() => {
        if (!tax.data) return;
        const row = tax.data;
        setForm({
            code: row.code,
            name: row.name,
            description: row.description ?? '',
            tax_type: row.tax_type,
            calculation_method: row.calculation_method,
            is_withholding: row.is_withholding,
            recoverable: row.recoverable,
            payable: row.payable,
            receivable: row.receivable,
            active: row.active,
        });
    }, [tax.data]);

    const set = <K extends keyof TaxPayload>(key: K, value: TaxPayload[K]) => setForm((current) => ({ ...current, [key]: value }));
    const rateColumns: DataColumn<TaxRate>[] = [
        { key: 'rate', header: 'Rate', render: (row) => row.rate },
        { key: 'from', header: 'From', render: (row) => row.effective_from },
        { key: 'to', header: 'To', render: (row) => row.effective_to ?? 'Open' },
        { key: 'status', header: 'Status', render: (row) => row.active ? 'Active' : 'Inactive' },
    ];

    if (tax.loading) return <LoadingState />;

    return (
        <>
            <ContentHeader title={`Edit ${tax.data?.code ?? 'tax'}`} description="Maintain tax metadata and effective-dated rates." actions={<Link className="text-sm font-semibold text-sky-700 hover:underline" to="/tax/taxes">Back to taxes</Link>} />
            <ErrorAlert error={error ?? tax.error ?? lookups.error} />
            <Panel title="Tax details">
                <div className="grid gap-4 md:grid-cols-2">
                    <Input label="Code" value={form.code} error={fieldError(error, 'code')} onChange={(event) => set('code', event.target.value)} />
                    <Input label="Name" value={form.name} error={fieldError(error, 'name')} onChange={(event) => set('name', event.target.value)} />
                    <Input label="Tax type" value={form.tax_type} error={fieldError(error, 'tax_type')} onChange={(event) => set('tax_type', event.target.value)} />
                    <Select label="Calculation method" value={form.calculation_method} error={fieldError(error, 'calculation_method')} options={(lookups.data?.calculation_methods ?? ['percentage', 'fixed', 'inclusive', 'exclusive', 'compound']).map((value) => ({ value, label: value }))} onChange={(event) => set('calculation_method', event.target.value as TaxCalculationMethod)} />
                    <label className="flex items-center gap-2 text-sm"><input type="checkbox" checked={form.is_withholding} onChange={(event) => set('is_withholding', event.target.checked)} /> Withholding tax</label>
                    <label className="flex items-center gap-2 text-sm"><input type="checkbox" checked={form.recoverable} onChange={(event) => set('recoverable', event.target.checked)} /> Recoverable</label>
                    <label className="flex items-center gap-2 text-sm"><input type="checkbox" checked={form.payable} onChange={(event) => set('payable', event.target.checked)} /> Payable</label>
                    <label className="flex items-center gap-2 text-sm"><input type="checkbox" checked={form.receivable} onChange={(event) => set('receivable', event.target.checked)} /> Receivable</label>
                    <label className="flex items-center gap-2 text-sm"><input type="checkbox" checked={form.active} onChange={(event) => set('active', event.target.checked)} /> Active</label>
                </div>
                <div className="mt-4">
                    <Button loading={saving} onClick={async () => {
                        setSaving(true);
                        setError(null);
                        try {
                            await updateTax(id, form);
                            navigate('/tax/taxes');
                        } catch (requestError) {
                            setError(toApiError(requestError));
                        } finally {
                            setSaving(false);
                        }
                    }}>Update tax</Button>
                </div>
            </Panel>
            <Panel title="Rates" className="mt-4">
                <DataTable rows={(tax.data as Tax | undefined)?.rates ?? []} columns={rateColumns} rowKey={(row) => row.id} />
                <div className="mt-4 grid gap-4 md:grid-cols-4">
                    <Input label="New rate" value={rate.rate} error={fieldError(error, 'rate')} onChange={(event) => setRate({ ...rate, rate: event.target.value })} />
                    <Input label="Effective from" type="date" value={rate.effective_from} error={fieldError(error, 'effective_from')} onChange={(event) => setRate({ ...rate, effective_from: event.target.value })} />
                    <Input label="Effective to" type="date" value={rate.effective_to} onChange={(event) => setRate({ ...rate, effective_to: event.target.value })} />
                    <Button className="mt-7" onClick={async () => {
                        setError(null);
                        try {
                            await addTaxRate(id, { ...rate, effective_to: rate.effective_to || null });
                            navigate(0);
                        } catch (requestError) {
                            setError(toApiError(requestError));
                        }
                    }}>Add rate</Button>
                </div>
            </Panel>
        </>
    );
}
