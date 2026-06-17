import { useState } from 'react';
import { fieldError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { FormActions } from '@/shared/components/FormActions';
import { Input } from '@/shared/components/Input';
import { Panel } from '@/shared/components/Panel';
import { Textarea } from '@/shared/components/Textarea';
import type { NamedResource } from '@/shared/types/common';
import type { ItemCategory, ItemCategoryPayload } from '../itemTypes';
import { ItemCategorySelect } from './ItemCategorySelect';

export function ItemCategoryForm({
    initial,
    error,
    submitting,
    onCancel,
    onSubmit,
}: {
    initial?: ItemCategory | null;
    error: ApiError | null;
    submitting: boolean;
    onCancel: () => void;
    onSubmit: (payload: ItemCategoryPayload) => void;
}) {
    const [form, setForm] = useState<ItemCategoryPayload>({
        code: initial?.code ?? '',
        name: initial?.name ?? '',
        parent_id: initial?.parent ? Number(initial.parent.id) : null,
        description: initial?.description ?? null,
        is_active: initial?.is_active ?? true,
        sort_order: initial?.sort_order ?? 0,
    });
    const [parent, setParent] = useState<NamedResource | null>(initial?.parent ?? null);

    return (
        <Panel title="Category Details">
            <form className="space-y-5" onSubmit={(event) => { event.preventDefault(); onSubmit(form); }}>
                <div className="grid gap-4 md:grid-cols-2">
                    <Input label="Code" value={form.code} maxLength={50} onChange={(event) => setForm({ ...form, code: event.target.value })} error={fieldError(error, 'code')} required />
                    <Input label="Name" value={form.name} maxLength={255} onChange={(event) => setForm({ ...form, name: event.target.value })} error={fieldError(error, 'name')} required />
                    <ItemCategorySelect
                        value={parent}
                        onChange={(next) => {
                            setParent(next);
                            setForm({ ...form, parent_id: next ? Number(next.id) : null });
                        }}
                        error={fieldError(error, 'parent_id')}
                    />
                    <Input label="Sort Order" type="number" min={0} value={form.sort_order} onChange={(event) => setForm({ ...form, sort_order: Number(event.target.value) })} error={fieldError(error, 'sort_order')} />
                </div>
                <Textarea label="Description" value={form.description ?? ''} onChange={(event) => setForm({ ...form, description: event.target.value || null })} error={fieldError(error, 'description')} />
                <label className="block text-sm text-slate-700"><input className="mr-2" type="checkbox" checked={form.is_active} onChange={(event) => setForm({ ...form, is_active: event.target.checked })} />Active</label>
                <FormActions>
                    <Button type="button" variant="secondary" onClick={onCancel}>Cancel</Button>
                    <Button type="submit" loading={submitting}>{initial ? 'Save Category' : 'Create Category'}</Button>
                </FormActions>
            </form>
        </Panel>
    );
}
