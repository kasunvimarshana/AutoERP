import { useState } from 'react';
import { fieldError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { FormActions } from '@/shared/components/FormActions';
import { Input } from '@/shared/components/Input';
import { Panel } from '@/shared/components/Panel';
import { Textarea } from '@/shared/components/Textarea';
import type { ItemBrand, ItemBrandPayload } from '../itemTypes';

export function ItemBrandForm({
    initial,
    error,
    submitting,
    onCancel,
    onSubmit,
}: {
    initial?: ItemBrand | null;
    error: ApiError | null;
    submitting: boolean;
    onCancel: () => void;
    onSubmit: (payload: ItemBrandPayload) => void;
}) {
    const [form, setForm] = useState<ItemBrandPayload>({
        code: initial?.code ?? '',
        name: initial?.name ?? '',
        description: initial?.description ?? null,
        is_active: initial?.is_active ?? true,
    });

    return (
        <Panel title="Brand Details">
            <form className="space-y-5" onSubmit={(event) => { event.preventDefault(); onSubmit(form); }}>
                <div className="grid gap-4 md:grid-cols-2">
                    <Input label="Code" value={form.code} maxLength={50} onChange={(event) => setForm({ ...form, code: event.target.value })} error={fieldError(error, 'code')} required />
                    <Input label="Name" value={form.name} maxLength={255} onChange={(event) => setForm({ ...form, name: event.target.value })} error={fieldError(error, 'name')} required />
                </div>
                <Textarea label="Description" value={form.description ?? ''} onChange={(event) => setForm({ ...form, description: event.target.value || null })} error={fieldError(error, 'description')} />
                <label className="block text-sm text-slate-700"><input className="mr-2" type="checkbox" checked={form.is_active} onChange={(event) => setForm({ ...form, is_active: event.target.checked })} />Active</label>
                <FormActions>
                    <Button type="button" variant="secondary" onClick={onCancel}>Cancel</Button>
                    <Button type="submit" loading={submitting}>{initial ? 'Save Brand' : 'Create Brand'}</Button>
                </FormActions>
            </form>
        </Panel>
    );
}
