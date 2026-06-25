import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { ApiError, fieldError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { useMutationFormGuard } from '@/shared/hooks/useMutationFormGuard';
import { Input } from '@/shared/components/Input';
import { Panel } from '@/shared/components/Panel';
import { Select } from '@/shared/components/Select';
import { Textarea } from '@/shared/components/Textarea';
import { createUom } from './uomApi';
import type { UomPayload } from './uomTypes';
import { uomCategories, uomTypes } from './uomTypes';

const initialForm: UomPayload = {
    code: '',
    name: '',
    symbol: '',
    type: 'unit',
    category: 'quantity',
    decimal_precision: 0,
    allow_fractional_quantity: false,
    is_base: false,
    is_active: true,
    description: '',
};

export default function UomCreatePage() {
    const navigate = useNavigate();
    const [form, setForm] = useState<UomPayload>(initialForm);
    const [error, setError] = useState<ApiError | null>(null);
    const [saving, setSaving] = useState(false);
    const formGuard = useMutationFormGuard(saving);

    return (
        <>
            <ContentHeader title="Create UOM" description="Add a generic unit definition." />
            <Panel>
                <ErrorAlert error={error} />
                <form className="mt-4 space-y-4" onSubmit={async (event) => {
                    event.preventDefault();
                    setSaving(true);
                    setError(null);
                    try {
                        const created = await createUom(form);
                        formGuard.markSaved();
                        navigate(`/uoms/${created.id}`);
                    } catch (nextError) {
                        setError(nextError instanceof ApiError ? nextError : new ApiError('Unable to save UOM.', null));
                    } finally {
                        setSaving(false);
                    }
                }}>
                    <UomFields form={form} setForm={(next) => { formGuard.markDirty(); setForm(next); }} error={error} />
                    <div className="flex justify-end gap-2">
                        <Button type="button" variant="secondary" onClick={() => navigate('/uoms')}>Cancel</Button>
                        <Button type="submit" loading={saving}>Create UOM</Button>
                    </div>
                </form>
            </Panel>
        </>
    );
}

export function UomFields({ form, setForm, error }: {
    form: UomPayload;
    setForm: (next: UomPayload) => void;
    error: ApiError | null;
}) {
    const set = <K extends keyof UomPayload>(key: K, value: UomPayload[K]) => setForm({ ...form, [key]: value });

    return (
        <>
            <div className="grid gap-4 md:grid-cols-3">
                <Input label="Code" value={form.code} onChange={(event) => set('code', event.target.value.toUpperCase())} error={fieldError(error, 'code')} required />
                <Input label="Name" value={form.name} onChange={(event) => set('name', event.target.value)} error={fieldError(error, 'name')} required />
                <Input label="Symbol" value={form.symbol} onChange={(event) => set('symbol', event.target.value)} error={fieldError(error, 'symbol')} required />
            </div>
            <div className="grid gap-4 md:grid-cols-3">
                <Select label="Type" value={form.type} onChange={(event) => set('type', event.target.value as UomPayload['type'])} options={uomTypes.map((value) => ({ value, label: value }))} error={fieldError(error, 'type')} />
                <Select label="Category" value={form.category} onChange={(event) => set('category', event.target.value as UomPayload['category'])} options={uomCategories.map((value) => ({ value, label: value }))} error={fieldError(error, 'category')} />
                <Input label="Decimal precision" type="number" min="0" max="8" value={form.decimal_precision} onChange={(event) => set('decimal_precision', Number(event.target.value))} error={fieldError(error, 'decimal_precision')} />
            </div>
            <div className="flex flex-wrap gap-4 text-sm text-slate-700">
                <label className="flex items-center gap-2"><input type="checkbox" checked={form.allow_fractional_quantity} onChange={(event) => set('allow_fractional_quantity', event.target.checked)} /> Allow fractional quantity</label>
                <label className="flex items-center gap-2"><input type="checkbox" checked={form.is_base} onChange={(event) => set('is_base', event.target.checked)} /> Base UOM</label>
                <label className="flex items-center gap-2"><input type="checkbox" checked={form.is_active} onChange={(event) => set('is_active', event.target.checked)} /> Active</label>
            </div>
            <Textarea label="Description" value={form.description ?? ''} onChange={(event) => set('description', event.target.value)} error={fieldError(error, 'description')} />
        </>
    );
}
