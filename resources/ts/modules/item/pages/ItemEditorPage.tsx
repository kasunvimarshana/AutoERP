import { useEffect, useState } from 'react';
import { useLocation, useNavigate, useParams } from 'react-router-dom';
import { Spinner } from '../../../shared/components/ui/Spinner';
import type { UomLookup } from '../../uom/types/uom.types';
import { uomApi } from '../../uom/services/uomApi';
import { ItemForm } from '../components/ItemForm';
import { itemApi } from '../services/itemApi';
import type { Item, ItemInput } from '../types/item.types';

function toInput(item: Item): ItemInput {
    return {
        barcode: item.barcode,
        baseUomId: item.baseUom.id,
        costPrice: item.costPrice,
        description: item.description,
        displayName: item.displayName,
        isServiceItem: item.isServiceItem,
        isStockItem: item.isStockItem,
        itemCode: item.itemCode,
        itemType: item.itemType,
        name: item.name,
        notes: item.notes,
        purchaseUomId: item.purchaseUom?.id,
        reorderLevel: item.reorderLevel,
        reorderQuantity: item.reorderQuantity,
        salesPrice: item.salesPrice,
        salesUomId: item.salesUom?.id,
        sku: item.sku,
        status: item.status,
        trackInventory: item.trackInventory,
    };
}

function includeSelected(lookup: UomLookup[], item: Item | null) {
    const selected = item ? [item.baseUom, item.purchaseUom, item.salesUom].filter((entry): entry is UomLookup => entry !== null) : [];
    return [...selected, ...lookup].filter((entry, index, values) => values.findIndex((candidate) => candidate.id === entry.id) === index);
}

export function ItemEditorPage({ mode }: { mode: 'create' | 'edit' }) {
    const navigate = useNavigate();
    const location = useLocation();
    const { id } = useParams();
    const stateItem = (location.state as { item?: Item } | null)?.item;
    const [item, setItem] = useState<Item | null>(stateItem?.id === Number(id) ? stateItem : null);
    const [uoms, setUoms] = useState<UomLookup[] | null>(null);
    const [error, setError] = useState('');
    const editing = mode === 'edit';

    useEffect(() => {
        let active = true;
        const itemRequest = editing && id && !item ? itemApi.get(Number(id)) : Promise.resolve(item);
        void Promise.all([itemRequest, uomApi.lookup()])
            .then(([loadedItem, lookup]) => {
                if (!active) return;
                if (loadedItem) setItem(loadedItem);
                setUoms(includeSelected(lookup, loadedItem));
            })
            .catch((requestError) => {
                if (active) setError(requestError instanceof Error ? requestError.message : 'Unable to load the item form.');
            });

        return () => {
            active = false;
        };
    }, [editing, id, item]);

    async function submit(input: ItemInput) {
        const saved = editing && id ? await itemApi.update(Number(id), input) : await itemApi.create(input);
        navigate(`/items/${saved.id}`, { replace: true, state: { item: saved } });
    }

    if ((!uoms || (editing && !item)) && !error) return <div className="flex items-center justify-center p-16 text-sm font-semibold text-slate-500"><Spinner /><span className="ml-3">Loading item form</span></div>;

    return <div className="mx-auto max-w-5xl space-y-5"><header><p className="text-xs font-bold uppercase tracking-[0.18em] text-blue-600">{editing ? 'Edit record' : 'New record'}</p><h1 className="mt-1 text-3xl font-bold text-slate-950">{editing ? 'Edit item' : 'Create item'}</h1></header>{error ? <div className="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{error}</div> : null}{!error && uoms ? <ItemForm initialValue={item ? toInput(item) : undefined} onCancel={() => navigate('/items')} onSubmit={submit} submitLabel={editing ? 'Update item' : 'Create item'} uoms={uoms} /> : null}</div>;
}
