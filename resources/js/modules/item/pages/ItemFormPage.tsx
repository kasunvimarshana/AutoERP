import { useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { createItem, getItem, listItemBrands, listItemCategories, updateItem } from '../itemApi';
import type { ItemPayload } from '../types';
import { ApiError, fieldError, toApiError } from '@/shared/api/apiError';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { Panel } from '@/shared/components/Panel';
import { Input } from '@/shared/components/Input';
import { Select } from '@/shared/components/Select';
import { Textarea } from '@/shared/components/Textarea';
import { Button } from '@/shared/components/Button';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { useApi } from '@/shared/hooks/useApi';
import { listUoms } from '@/shared/api/referenceApi';

const options = (values: string[]) => values.map((value) => ({ value, label: value.replaceAll('_', ' ') }));
const emptyForm: ItemPayload = {
    code: '', name: '', item_type: 'stock', tracking_type: 'none', costing_method: 'fifo',
    is_stockable: true, is_combo: false, is_active: true,
};

export default function ItemFormPage() {
    const { id } = useParams();
    const itemId = id ? Number(id) : null;
    const navigate = useNavigate();
    const [form, setForm] = useState<ItemPayload>(emptyForm);
    const [loading, setLoading] = useState(Boolean(itemId));
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    const [oneShot, setOneShot] = useState(false);
    const categories = useApi((signal) => listItemCategories(signal), []);
    const brands = useApi((signal) => listItemBrands(signal), []);
    const uoms = useApi((signal) => listUoms('', signal), []);

    useEffect(() => {
        if (!itemId) return;
        const controller = new AbortController();
        getItem(itemId, controller.signal)
            .then((item) => setForm({
                code: item.code ?? '', name: item.name, item_type: item.item_type,
                tracking_type: item.tracking_type, costing_method: item.costing_method,
                sku: item.sku ?? '', barcode: item.barcode ?? '', description: item.description ?? '',
                item_category_id: item.category?.id, item_brand_id: item.brand?.id, base_uom_id: item.base_uom?.id,
                is_stockable: item.is_stockable, is_combo: item.is_combo, is_active: item.is_active,
            }))
            .catch((requestError) => !controller.signal.aborted && setError(toApiError(requestError)))
            .finally(() => !controller.signal.aborted && setLoading(false));
        return () => controller.abort();
    }, [itemId]);

    const set = <K extends keyof ItemPayload>(key: K, value: ItemPayload[K]) => setForm((current) => ({ ...current, [key]: value }));
    if (loading) return <LoadingState />;
    return (
        <>
            <ContentHeader title={itemId ? 'Edit item' : 'New item'} description="Backend validation and domain rules remain authoritative." />
            <form className="space-y-5" onSubmit={async (event) => {
                event.preventDefault();
                setSubmitting(true);
                setError(null);
                try {
                    const saved = itemId ? await updateItem(itemId, form) : await createItem(form);
                    navigate(`/items/${saved.id}`);
                } catch (requestError) {
                    setError(toApiError(requestError));
                } finally {
                    setSubmitting(false);
                }
            }}>
                <ErrorAlert error={error} />
                <Panel title="Item identity">
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <Input label="Code" value={form.code} onChange={(event) => set('code', event.target.value)} error={fieldError(error, 'code')} required />
                        <Input label="Name" value={form.name} onChange={(event) => set('name', event.target.value)} error={fieldError(error, 'name')} required />
                        <Select label="Type" value={form.item_type} onChange={(event) => set('item_type', event.target.value)} options={options(['stock', 'non_stock', 'service', 'labour', 'asset', 'consumable', 'package', 'combo'])} />
                        <Select label="Tracking" value={form.tracking_type} onChange={(event) => set('tracking_type', event.target.value)} options={options(['none', 'batch', 'lot', 'serial'])} />
                        <Select label="Costing" value={form.costing_method} onChange={(event) => set('costing_method', event.target.value)} options={options(['fifo', 'weighted_average', 'standard', 'manual', 'none'])} />
                        <Input label="SKU" value={form.sku ?? ''} onChange={(event) => set('sku', event.target.value)} />
                        <Input label="Barcode" value={form.barcode ?? ''} onChange={(event) => set('barcode', event.target.value)} />
                        <Select label="Category" value={form.item_category_id ?? ''} onChange={(event) => set('item_category_id', event.target.value ? Number(event.target.value) : undefined)} options={(categories.data ?? []).map((entry) => ({ value: entry.id, label: entry.name }))} />
                        <Select label="Brand" value={form.item_brand_id ?? ''} onChange={(event) => set('item_brand_id', event.target.value ? Number(event.target.value) : undefined)} options={(brands.data ?? []).map((entry) => ({ value: entry.id, label: entry.name }))} />
                        <Select label="Base UOM" value={form.base_uom_id ?? ''} onChange={(event) => set('base_uom_id', event.target.value ? Number(event.target.value) : undefined)} options={(uoms.data ?? []).map((entry) => ({ value: entry.id, label: `${entry.name}${entry.code ? ` (${entry.code})` : ''}` }))} />
                    </div>
                    <div className="mt-4"><Textarea label="Description" value={form.description ?? ''} onChange={(event) => set('description', event.target.value)} /></div>
                    <div className="mt-4 flex flex-wrap gap-6 text-sm">
                        <label><input className="mr-2" type="checkbox" checked={form.is_stockable ?? false} onChange={(event) => set('is_stockable', event.target.checked)} />Stockable</label>
                        <label><input className="mr-2" type="checkbox" checked={form.is_combo ?? false} onChange={(event) => set('is_combo', event.target.checked)} />Combo</label>
                        <label><input className="mr-2" type="checkbox" checked={form.is_active ?? false} onChange={(event) => set('is_active', event.target.checked)} />Active</label>
                    </div>
                </Panel>
                {!itemId && (
                    <Panel title="One-shot item setup">
                        <label className="flex items-center gap-2 text-sm font-medium"><input type="checkbox" checked={oneShot} onChange={(event) => setOneShot(event.target.checked)} />Include initial unit, variant, price, code, and usage rule</label>
                        {oneShot && <OneShotFields form={form} setForm={setForm} error={error} />}
                    </Panel>
                )}
                <div className="flex justify-end gap-2">
                    <Button type="button" variant="secondary" onClick={() => navigate(-1)}>Cancel</Button>
                    <Button type="submit" loading={submitting}>{itemId ? 'Save item' : 'Create item'}</Button>
                </div>
            </form>
        </>
    );
}

function OneShotFields({ form, setForm, error }: { form: ItemPayload; setForm: (value: ItemPayload) => void; error: ApiError | null }) {
    const variant = form.variants?.[0] ?? { code: '', name: '' };
    const price = form.prices?.[0] ?? { price_type: 'sales', amount: '' };
    const code = form.codes?.[0] ?? { code_type: 'barcode', code: '', is_primary: true };
    const usage = form.usage_rules?.[0] ?? { module_code: 'sales', is_enabled: true };
    return (
        <div className="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <Input label="Unit UOM ID" type="number" min="1" value={form.units?.[0]?.uom_id ?? ''} onChange={(event) => setForm({ ...form, units: event.target.value ? [{ uom_id: Number(event.target.value), unit_role: 'base', conversion_factor: '1.000000', is_default: true }] : [] })} error={fieldError(error, 'units.0.uom_id')} />
            <Input label="Variant code" value={variant.code} onChange={(event) => setForm({ ...form, variants: [{ ...variant, code: event.target.value }] })} />
            <Input label="Variant name" value={variant.name} onChange={(event) => setForm({ ...form, variants: [{ ...variant, name: event.target.value }] })} />
            <Select label="Price type" value={price.price_type} onChange={(event) => setForm({ ...form, prices: [{ ...price, price_type: event.target.value }] })} options={options(['purchase', 'sales', 'service', 'rental', 'cost', 'standard'])} />
            <Input label="Price amount" type="number" min="0" step="0.000001" value={price.amount} onChange={(event) => setForm({ ...form, prices: [{ ...price, amount: event.target.value }] })} />
            <Select label="Code type" value={code.code_type} onChange={(event) => setForm({ ...form, codes: [{ ...code, code_type: event.target.value }] })} options={options(['sku', 'barcode', 'supplier_code', 'customer_code', 'internal_code', 'oem_code'])} />
            <Input label="Alternate code" value={code.code} onChange={(event) => setForm({ ...form, codes: [{ ...code, code: event.target.value }] })} />
            <Input label="Usage module" value={usage.module_code} onChange={(event) => setForm({ ...form, usage_rules: [{ ...usage, module_code: event.target.value }] })} />
        </div>
    );
}
