import { useEffect, useState } from 'react';
import { Link, useLocation, useParams } from 'react-router-dom';
import { Spinner } from '../../../shared/components/ui/Spinner';
import { itemApi } from '../services/itemApi';
import type { Item } from '../types/item.types';

export function ItemDetailPage() {
    const { id } = useParams();
    const location = useLocation();
    const stateItem = (location.state as { item?: Item } | null)?.item;
    const [item, setItem] = useState<Item | null>(stateItem?.id === Number(id) ? stateItem : null);
    const [error, setError] = useState('');

    useEffect(() => {
        if (item || !id) return;
        let active = true;
        void itemApi.get(Number(id)).then((response) => {
            if (active) setItem(response);
        }).catch((requestError) => {
            if (active) setError(requestError instanceof Error ? requestError.message : 'Unable to load this item.');
        });

        return () => {
            active = false;
        };
    }, [id, item]);

    if (!item && !error) return <div className="flex items-center justify-center p-16 text-sm font-semibold text-slate-500"><Spinner /><span className="ml-3">Loading item</span></div>;
    if (!item) return <div className="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{error}</div>;

    return <div className="mx-auto max-w-5xl space-y-5"><header className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"><div><p className="text-xs font-bold uppercase tracking-[0.18em] text-blue-600">{item.itemCode}</p><h1 className="mt-1 text-3xl font-bold text-slate-950">{item.name}</h1><p className="mt-1 text-sm text-slate-500">{item.displayName || item.itemType || 'Item record'}</p></div><div className="flex gap-2"><Link className="inline-flex h-10 items-center rounded-lg border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50" to="/items">Back</Link><Link className="inline-flex h-10 items-center rounded-lg bg-blue-600 px-4 text-sm font-semibold text-white hover:bg-blue-700" state={{ item }} to={`/items/${item.id}/edit`}>Edit</Link></div></header><div className="grid gap-5 lg:grid-cols-3">
        <section className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm lg:col-span-2"><h2 className="font-bold text-slate-950">Identity & classification</h2><dl className="mt-4 grid gap-4 sm:grid-cols-2"><Info label="Status" value={item.status} /><Info label="Item type" value={item.itemType} /><Info label="SKU" value={item.sku} /><Info label="Barcode" value={item.barcode} /><Info label="Base UOM" value={`${item.baseUom.uomCode} - ${item.baseUom.name}`} /><Info label="Purchase UOM" value={item.purchaseUom ? `${item.purchaseUom.uomCode} - ${item.purchaseUom.name}` : null} /><Info label="Sales UOM" value={item.salesUom ? `${item.salesUom.uomCode} - ${item.salesUom.name}` : null} /></dl></section>
        <section className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm"><h2 className="font-bold text-slate-950">Pricing</h2><dl className="mt-4 space-y-4"><Info label="Cost price" value={Number(item.costPrice).toLocaleString(undefined, { maximumFractionDigits: 4 })} /><Info label="Sales price" value={Number(item.salesPrice).toLocaleString(undefined, { maximumFractionDigits: 4 })} /></dl></section>
        <section className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm lg:col-span-2"><h2 className="font-bold text-slate-950">Inventory settings</h2><dl className="mt-4 grid gap-4 sm:grid-cols-2"><Info label="Track inventory" value={item.trackInventory ? 'Yes' : 'No'} /><Info label="Stock item" value={item.isStockItem ? 'Yes' : 'No'} /><Info label="Service item" value={item.isServiceItem ? 'Yes' : 'No'} /><Info label="Reorder level" value={item.reorderLevel} /><Info label="Reorder quantity" value={item.reorderQuantity} /></dl></section>
        <section className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm"><h2 className="font-bold text-slate-950">Notes</h2><p className="mt-4 whitespace-pre-wrap text-sm leading-6 text-slate-600">{item.description || 'No description.'}</p><p className="mt-4 whitespace-pre-wrap border-t border-slate-100 pt-4 text-sm leading-6 text-slate-600">{item.notes || 'No internal notes.'}</p></section>
    </div></div>;
}

function Info({ label, value }: { label: string; value?: string | number | null }) {
    return <div><dt className="text-xs font-bold uppercase tracking-wide text-slate-400">{label}</dt><dd className="mt-1 text-sm font-semibold text-slate-800">{value === null || value === undefined || value === '' ? 'Not provided' : value}</dd></div>;
}
