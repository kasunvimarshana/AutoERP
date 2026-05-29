import { useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { StatusBadge } from '../../../shared/components/business/StatusBadge';
import { DataTable } from '../../../shared/components/data/DataTable';
import { SearchFilterBar } from '../../../shared/components/data/SearchFilterBar';
import { Button } from '../../../shared/components/ui/Button';
import { Card } from '../../../shared/components/ui/Card';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import { Select } from '../../../shared/components/ui/Select';
import { ItemStockBehaviorBadge, ItemTypeBadge } from '../components/ItemBadges';
import { itemApi } from '../services/itemApi';
import type { Item, ItemType } from '../types/item.types';

export function ItemListPage() {
    const [items, setItems] = useState<Item[]>([]);
    const [error, setError] = useState('');
    const [isLoading, setIsLoading] = useState(true);
    const [query, setQuery] = useState('');
    const [type, setType] = useState('');
    const [stockBehavior, setStockBehavior] = useState('');
    const [status, setStatus] = useState('');

    useEffect(() => {
        let mounted = true;
        itemApi.listItems()
            .then((response) => { if (mounted) setItems(response.data); })
            .catch((caught: unknown) => { if (mounted) setError(caught instanceof Error ? caught.message : 'Unable to load items.'); })
            .finally(() => { if (mounted) setIsLoading(false); });
        return () => { mounted = false; };
    }, []);

    const visibleItems = useMemo(() => {
        const q = query.trim().toLowerCase();
        return items.filter((item) => {
            const matchesQuery = q ? [item.code, item.name, item.category, item.baseUom].some((value) => value.toLowerCase().includes(q)) : true;
            return matchesQuery && (!type || item.itemType === type) && (!stockBehavior || item.stockBehavior === stockBehavior) && (!status || item.status === status);
        });
    }, [items, query, status, stockBehavior, type]);

    return (
        <div className="space-y-6">
            <PageHeader actions={<Link to="/items/new"><Button>New Item</Button></Link>} eyebrow="Core Master Data" subtitle="Items support stock products, services, labour, combos, rental charges, and non-stock references." title="Items" />
            <div className="grid gap-4 md:grid-cols-3">
                {[
                    ['Items loaded', String(items.length), 'Mock or backend-normalized records'],
                    ['Stock tracked', String(items.filter((item) => item.stockBehavior === 'stock_tracked').length), 'Only some items affect inventory'],
                    ['Backend calculations', 'Readonly', 'No frontend stock, cost, tax, or pricing logic'],
                ].map(([label, value, helper]) => (
                    <Card className="p-5" key={label}>
                        <p className="text-sm text-slate-500">{label}</p>
                        <p className="mt-2 text-2xl font-bold text-slate-950">{value}</p>
                        <p className="mt-1 text-xs text-slate-400">{helper}</p>
                    </Card>
                ))}
            </div>
            <SearchFilterBar onSearch={setQuery} placeholder="Search SKU, item name, category, UOM, barcode..." />
            <div className="grid gap-3 rounded-lg border border-slate-200 bg-white p-4 md:grid-cols-4">
                <Select onChange={(event) => setType(event.target.value)} options={[
                    { label: 'Inventory Product', value: 'inventory_product' },
                    { label: 'Service', value: 'service' },
                    { label: 'Labour', value: 'labour' },
                    { label: 'Combo / Bundle', value: 'combo' },
                    { label: 'Non-Inventory', value: 'non_inventory' },
                    { label: 'Rental Charge', value: 'rental_charge' },
                ]} placeholder="All item types" value={type} />
                <Select onChange={(event) => setStockBehavior(event.target.value)} options={[
                    { label: 'Stock tracked', value: 'stock_tracked' },
                    { label: 'No stock impact', value: 'no_stock_impact' },
                    { label: 'Reference only', value: 'reference_only' },
                ]} placeholder="All stock behavior" value={stockBehavior} />
                <Select onChange={(event) => setStatus(event.target.value)} options={[
                    { label: 'Active', value: 'active' },
                    { label: 'Draft', value: 'draft' },
                    { label: 'Inactive', value: 'inactive' },
                ]} placeholder="All statuses" value={status} />
                <Select options={[{ label: 'OEM', value: 'OEM' }, { label: 'Workshop Internal', value: 'Workshop Internal' }]} placeholder="All brands" />
            </div>
            {isLoading ? <EmptyState description="Loading items from the Item service..." title="Loading items" /> : null}
            {error ? <EmptyState description={error} title="Item service unavailable" /> : null}
            {!isLoading && !error && !visibleItems.length ? <EmptyState description="Create an item and choose the correct type and stock behavior." title="No items found" /> : null}
            {!isLoading && !error && visibleItems.length ? (
                <DataTable columns={[
                    { header: 'SKU', key: 'code', render: (row) => <Link className="font-semibold text-slate-950" to={`/items/${row.id}`}>{row.code}</Link> },
                    { header: 'Item', key: 'name' },
                    { header: 'Type', key: 'itemType', render: (row) => <ItemTypeBadge type={row.itemType as ItemType} /> },
                    { header: 'Category', key: 'category' },
                    { header: 'Base UOM', key: 'baseUom' },
                    { header: 'Stock Behavior', key: 'stockBehavior', render: (row) => <ItemStockBehaviorBadge behavior={row.stockBehavior} /> },
                    { header: 'Status', key: 'status', render: (row) => <StatusBadge status={row.status} /> },
                    { header: 'Updated', key: 'updatedAt' },
                    { header: 'Actions', key: 'actions', render: (row) => <div className="flex gap-2"><Link className="font-semibold text-slate-950" to={`/items/${row.id}`}>View</Link><Link className="font-semibold text-slate-500" to={`/items/${row.id}/edit`}>Edit</Link></div> },
                ]} getRowKey={(row) => row.id} rows={visibleItems} />
            ) : null}
        </div>
    );
}
