import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { LinkButton } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Pagination } from '@/shared/components/Pagination';
import { Select } from '@/shared/components/Select';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { useApi } from '@/shared/hooks/useApi';
import { useDebounce } from '@/shared/hooks/useDebounce';
import type { NamedResource } from '@/shared/types/common';
import { readableRelation } from '@/shared/utils/object';
import { useAuth } from '@/modules/auth/AuthProvider';
import { listItems, setItemActive } from './itemApi';
import { hasItemPermission, itemPermissions } from './itemPermissions';
import { itemTypes, type ItemSummary } from './itemTypes';
import { ItemBrandSelect } from './components/ItemBrandSelect';
import { ItemCategorySelect } from './components/ItemCategorySelect';
import { notifySuccess } from '@/shared/notifications/appToast';

export default function ItemListPage() {
    const auth = useAuth();
    const canCreate = hasItemPermission(auth, itemPermissions.create);
    const canUpdate = hasItemPermission(auth, itemPermissions.update);
    const canActivate = hasItemPermission(auth, itemPermissions.activate);
    const canDeactivate = hasItemPermission(auth, itemPermissions.deactivate);
    const [search, setSearch] = useState('');
    const [category, setCategory] = useState<NamedResource | null>(null);
    const [brand, setBrand] = useState<NamedResource | null>(null);
    const [itemType, setItemType] = useState('');
    const [active, setActive] = useState('');
    const [page, setPage] = useState(1);
    const [actionError, setActionError] = useState<ApiError | null>(null);
    const debounced = useDebounce(search);
    const result = useApi((signal) => listItems({
        search: debounced || undefined,
        category_id: category?.id,
        brand_id: brand?.id,
        item_type: itemType || undefined,
        is_active: active === '' ? undefined : active === 'true',
        page,
        per_page: 25,
    }, signal), [debounced, category?.id, brand?.id, itemType, active, page]);
    const [collection, setCollection] = useState(result.data);

    useEffect(() => {
        if (result.data) {
            setCollection(result.data);
        }
    }, [result.data]);

    const columns: DataColumn<ItemSummary>[] = [
        { key: 'item', header: 'Item', render: (row) => <Link className="font-semibold text-sky-700 hover:underline" to={`/items/${row.id}`}>{row.name}<span className="block text-xs font-normal text-slate-500">{row.code}</span></Link> },
        { key: 'type', header: 'Type', render: (row) => row.item_type },
        { key: 'category', header: 'Category', render: (row) => readableRelation(row.category) },
        { key: 'brand', header: 'Brand', render: (row) => readableRelation(row.brand) },
        { key: 'uom', header: 'Base UOM', render: (row) => readableRelation(row.base_uom) },
        { key: 'status', header: 'Status', render: (row) => <StatusBadge status={row.is_active ? 'active' : 'inactive'} /> },
        { key: 'actions', header: '', className: 'text-right', render: (row) => (
            <div className="flex justify-end gap-3">
                {canUpdate && <Link className="font-semibold text-slate-600 hover:text-sky-700" to={`/items/${row.id}/edit`}>Edit</Link>}
                {row.is_active && canDeactivate && <button type="button" className="font-semibold text-amber-700" onClick={() => void toggle(row)}>Deactivate</button>}
                {!row.is_active && canActivate && <button type="button" className="font-semibold text-emerald-700" onClick={() => void toggle(row)}>Activate</button>}
            </div>
        ) },
    ];

    async function toggle(item: ItemSummary) {
        setActionError(null);
        try {
            const updated = await setItemActive(Number(item.id), !item.is_active);
            setCollection((current) => updateItemCollection(current, updated, {
                categoryId: category?.id ?? null,
                brandId: brand?.id ?? null,
                itemType,
                active,
            }));
            notifySuccess(updated.is_active ? 'Item activated successfully.' : 'Item deactivated successfully.');
        } catch (error) {
            setActionError(toApiError(error));
        }
    }

    return <>
        <ContentHeader title="Items" description="Service-first item master data with readable resources." actions={canCreate ? <LinkButton to="/items/create">Create Item</LinkButton> : undefined} />
        <div className="mb-5 grid gap-4 md:grid-cols-2 xl:grid-cols-5">
            <Input type="search" label="Search" placeholder="Code, name, SKU, barcode" value={search} onChange={(event) => { setSearch(event.target.value); setPage(1); }} />
            <ItemCategorySelect value={category} onChange={(value) => { setCategory(value); setPage(1); }} />
            <ItemBrandSelect value={brand} onChange={(value) => { setBrand(value); setPage(1); }} />
            <Select label="Item type" value={itemType} onChange={(event) => { setItemType(event.target.value); setPage(1); }} options={itemTypes.map((value) => ({ value, label: value.replaceAll('_', ' ') }))} />
            <Select label="Status" value={active} onChange={(event) => { setActive(event.target.value); setPage(1); }} options={[{ value: 'true', label: 'Active' }, { value: 'false', label: 'Inactive' }]} />
        </div>
        <ErrorAlert error={actionError ?? result.error} />
        {result.loading && !collection ? <LoadingState /> : <DataTable rows={collection?.data ?? []} columns={columns} rowKey={(row) => row.id} />}
        <Pagination meta={collection?.meta} onPageChange={setPage} />
    </>;
}

function updateItemCollection(
    collection: Awaited<ReturnType<typeof listItems>> | null,
    updated: ItemSummary,
    filters: {
        categoryId: number | null;
        brandId: number | null;
        itemType: string;
        active: string;
    },
) {
    if (collection === null) return collection;

    const rows = collection.data ?? [];
    const currentIndex = rows.findIndex((row) => row.id === updated.id);
    const matches = matchesItemFilters(updated, filters);

    if (!matches) {
        if (currentIndex === -1) return collection;

        const nextRows = rows.filter((row) => row.id !== updated.id);
        return {
            ...collection,
            data: nextRows,
            meta: collection.meta ? {
                ...collection.meta,
                total: Math.max(0, collection.meta.total - 1),
                to: nextRows.length === 0 ? null : nextRows.length,
            } : collection.meta,
        };
    }

    if (currentIndex === -1) return collection;

    return {
        ...collection,
        data: rows.map((row) => row.id === updated.id ? updated : row),
    };
}

function matchesItemFilters(
    item: ItemSummary,
    filters: {
        categoryId: number | null;
        brandId: number | null;
        itemType: string;
        active: string;
    },
) {
    if (filters.categoryId !== null && item.category?.id !== filters.categoryId) return false;
    if (filters.brandId !== null && item.brand?.id !== filters.brandId) return false;
    if (filters.itemType && item.item_type !== filters.itemType) return false;
    if (filters.active === 'true' && !item.is_active) return false;
    if (filters.active === 'false' && item.is_active) return false;
    return true;
}
