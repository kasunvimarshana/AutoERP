import { useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { StatusBadge } from '../../../shared/components/business/StatusBadge';
import { DataTable } from '../../../shared/components/data/DataTable';
import { DataToolbar, type DataToolbarFilterValue } from '../../../shared/components/data/DataToolbar';
import { Button } from '../../../shared/components/ui/Button';
import { Card } from '../../../shared/components/ui/Card';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import { ItemStockBehaviorBadge, ItemTypeBadge } from '../components/ItemBadges';
import { itemApi } from '../services/itemApi';
import type { Item, ItemStatus, ItemType, StockBehavior } from '../types/item.types';

function stockFilterToBoolean(value: string) {
    if (value === 'stock_tracked') {
        return true;
    }

    if (value === 'no_stock_impact') {
        return false;
    }

    return undefined;
}

export function ItemListPage() {
    const [items, setItems] = useState<Item[]>([]);
    const [total, setTotal] = useState(0);
    const [error, setError] = useState('');
    const [isLoading, setIsLoading] = useState(true);
    const [query, setQuery] = useState('');
    const [type, setType] = useState('');
    const [stockBehavior, setStockBehavior] = useState('');
    const [status, setStatus] = useState('');

    useEffect(() => {
        let mounted = true;
        setIsLoading(true);
        setError('');

        itemApi.listItems({
            isStockable: stockFilterToBoolean(stockBehavior),
            search: query,
            status: status ? (status as ItemStatus) : undefined,
            type: type ? (type as ItemType) : undefined,
        })
            .then((response) => {
                if (mounted) {
                    setItems(response.data);
                    setTotal(Number(response.meta?.total ?? response.data.length));
                }
            })
            .catch((caught: unknown) => {
                if (mounted) {
                    setError(caught instanceof Error ? caught.message : 'Unable to load items.');
                }
            })
            .finally(() => {
                if (mounted) {
                    setIsLoading(false);
                }
            });

        return () => {
            mounted = false;
        };
    }, [query, status, stockBehavior, type]);

    const selectedStockBehavior = useMemo(() => (stockBehavior || 'all') as StockBehavior | 'all', [stockBehavior]);
    const filters = [
        { id: 'type', label: 'Item type', options: [
            { label: 'Inventory Product', value: 'inventory_product' },
            { label: 'Service', value: 'service' },
            { label: 'Labour', value: 'labour' },
            { label: 'Combo / Bundle', value: 'combo' },
            { label: 'Non-Inventory', value: 'non_inventory' },
            { label: 'Rental Charge', value: 'rental_charge' },
            { label: 'External Service', value: 'external_service' },
            { label: 'Customer-Supplied Reference', value: 'customer_supplied' },
        ], placeholder: 'All item types', type: 'select' as const },
        { id: 'stockBehavior', label: 'Stock behavior', options: [
            { label: 'Stock tracked', value: 'stock_tracked' },
            { label: 'No stock impact', value: 'no_stock_impact' },
        ], placeholder: 'All stock behavior', type: 'select' as const },
        { id: 'status', label: 'Status', options: [
            { label: 'Active', value: 'active' },
            { label: 'Draft', value: 'draft' },
            { label: 'Inactive', value: 'inactive' },
            { label: 'Discontinued', value: 'discontinued' },
        ], placeholder: 'All statuses', type: 'status' as const },
    ];

    function updateFilter(filterId: string, value: DataToolbarFilterValue): void {
        const next = typeof value === 'string' ? value : '';
        if (filterId === 'type') setType(next);
        if (filterId === 'stockBehavior') setStockBehavior(next);
        if (filterId === 'status') setStatus(next);
    }

    return (
        <div className="space-y-6">
            <PageHeader actions={<Link to="/items/new"><Button>New Item</Button></Link>} eyebrow="Core Master Data" subtitle="Reusable item, service, labour, bundle, rental charge, and non-stock master data." title="Items" />
            <div className="grid gap-4 md:grid-cols-3">
                <Card className="p-5">
                    <p className="text-sm text-slate-500">Items returned</p>
                    <p className="mt-2 text-2xl font-bold text-slate-950">{total}</p>
                    <p className="mt-1 text-xs text-slate-400">From backend pagination metadata</p>
                </Card>
                <Card className="p-5">
                    <p className="text-sm text-slate-500">Stock behavior</p>
                    <p className="mt-2 text-2xl font-bold text-slate-950">{selectedStockBehavior === 'all' ? 'Backend' : selectedStockBehavior === 'stock_tracked' ? 'Tracked' : 'No stock'}</p>
                    <p className="mt-1 text-xs text-slate-400">Filter sends setup flags to the Item API</p>
                </Card>
                <Card className="p-5">
                    <p className="text-sm text-slate-500">Business calculations</p>
                    <p className="mt-2 text-2xl font-bold text-slate-950">Readonly</p>
                    <p className="mt-1 text-xs text-slate-400">Stock, tax, and costing are loaded from API-backed module data</p>
                </Card>
            </div>
            <DataToolbar
                filterValues={{ status, stockBehavior, type }}
                filters={filters}
                isLoading={isLoading}
                onFilterChange={updateFilter}
                onRemoveFilter={(filterId) => updateFilter(filterId, undefined)}
                onResetFilters={() => { setType(''); setStockBehavior(''); setStatus(''); }}
                onSearchChange={setQuery}
                savedViewsDisabledReason="Saved views need a user-preferences backend before they can be enabled for item lists."
                searchPlaceholder="Search SKU, item name, barcode, category, or brand..."
                searchValue={query}
            />
            {isLoading ? <EmptyState description="Loading items from the Item API..." title="Loading items" /> : null}
            {error ? <EmptyState description={error} title="Item service unavailable" /> : null}
            {!isLoading && !error && !items.length ? <EmptyState description="Create an item and choose the correct type, UOM, and stock setup." title="No items found" /> : null}
            {!isLoading && !error && items.length ? (
                <DataTable columns={[
                    { header: 'SKU', key: 'code', render: (row) => <Link className="font-semibold text-slate-950" to={`/items/${row.id}`}>{row.code}</Link> },
                    { header: 'Item', key: 'name' },
                    { header: 'Type', key: 'itemType', render: (row) => <ItemTypeBadge type={row.itemType} /> },
                    { header: 'Category', key: 'category' },
                    { header: 'Brand', key: 'brand', render: (row) => row.brand || 'Not assigned' },
                    { header: 'Base UOM', key: 'baseUom' },
                    { header: 'Stock Behavior', key: 'stockBehavior', render: (row) => <ItemStockBehaviorBadge behavior={row.stockBehavior} /> },
                    { header: 'Status', key: 'status', render: (row) => <StatusBadge status={row.status} /> },
                    { header: 'Actions', key: 'actions', render: (row) => <div className="flex gap-2"><Link className="font-semibold text-slate-950" to={`/items/${row.id}`}>View</Link><Link className="font-semibold text-slate-500" to={`/items/${row.id}/edit`}>Edit</Link></div> },
                ]} getRowKey={(row) => row.id} rows={items} />
            ) : null}
        </div>
    );
}
