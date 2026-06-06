import { useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import type { NamedResource } from '@/shared/types/common';
import { getItem, updateItem } from './itemApi';
import type { ItemPayload } from './itemTypes';
import { ItemForm } from './components/ItemForm';

export default function ItemEditPage() {
    const itemId = Number(useParams().id);
    const navigate = useNavigate();
    const [form, setForm] = useState<ItemPayload | null>(null);
    const [category, setCategory] = useState<NamedResource | null>(null);
    const [brand, setBrand] = useState<NamedResource | null>(null);
    const [baseUom, setBaseUom] = useState<NamedResource | null>(null);
    const [loading, setLoading] = useState(true);
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);

    useEffect(() => {
        const controller = new AbortController();
        getItem(itemId, controller.signal)
            .then((item) => {
                if (controller.signal.aborted) return;
                setForm({
                    code: item.code,
                    name: item.name,
                    item_type: item.item_type,
                    tracking_type: item.tracking_type,
                    costing_method: item.costing_method,
                    item_category_id: item.category ? Number(item.category.id) : null,
                    item_brand_id: item.brand ? Number(item.brand.id) : null,
                    base_uom_id: item.base_uom ? Number(item.base_uom.id) : null,
                    sku: item.sku ?? null,
                    barcode: item.barcode ?? null,
                    description: item.description ?? null,
                    is_stockable: item.is_stockable,
                    is_combo: item.is_combo,
                    is_active: item.is_active,
                });
                setCategory(item.category ?? null);
                setBrand(item.brand ?? null);
                setBaseUom(item.base_uom ?? null);
            })
            .catch((requestError) => !controller.signal.aborted && setError(toApiError(requestError)))
            .finally(() => !controller.signal.aborted && setLoading(false));
        return () => controller.abort();
    }, [itemId]);

    if (loading) return <LoadingState />;
    if (!form) return <ErrorAlert error={error} />;
    return <>
        <ContentHeader title="Edit item" description="Relations remain on-demand in the item detail workspace." />
        <ErrorAlert error={error} />
        <form className="space-y-5" onSubmit={(event) => { event.preventDefault(); void save(); }}>
            <ItemForm value={form} onChange={setForm} category={category} onCategoryChange={setCategory} brand={brand} onBrandChange={setBrand} baseUom={baseUom} onBaseUomChange={setBaseUom} error={error} />
            <div className="flex justify-end gap-2"><Button type="button" variant="secondary" onClick={() => navigate(-1)}>Cancel</Button><Button type="submit" loading={submitting}>Save item</Button></div>
        </form>
    </>;

    async function save() {
        if (!form) return;
        setSubmitting(true);
        setError(null);
        try {
            const saved = await updateItem(itemId, form);
            navigate(`/items/${saved.id}`);
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setSubmitting(false);
        }
    }
}
