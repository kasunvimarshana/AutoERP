import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button, LinkButton } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { useConfirmDialog } from '@/shared/components/ConfirmDialog';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Pagination } from '@/shared/components/Pagination';
import { Select } from '@/shared/components/Select';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { useApi } from '@/shared/hooks/useApi';
import { useDebounce } from '@/shared/hooks/useDebounce';
import { useAuth } from '@/modules/auth/AuthProvider';
import { deleteItemBrand, listItemBrands, updateItemBrand } from './itemApi';
import type { ItemBrand } from './itemTypes';
import { hasItemPermission, itemPermissions } from './itemPermissions';
import { notifySuccess } from '@/shared/notifications/appToast';

export default function ItemBrandListPage() {
    const auth = useAuth();
    const { confirm, confirmDialog } = useConfirmDialog();
    const canManage = hasItemPermission(auth, itemPermissions.manageBrands);
    const [search, setSearch] = useState('');
    const [active, setActive] = useState('');
    const [page, setPage] = useState(1);
    const [actionError, setActionError] = useState<ApiError | null>(null);
    const debounced = useDebounce(search);
    const result = useApi((signal) => listItemBrands({
        search: debounced || undefined,
        is_active: active === '' ? undefined : active === 'true',
        page,
        per_page: 25,
    }, signal), [debounced, active, page]);
    const [collection, setCollection] = useState(result.data);

    useEffect(() => {
        if (result.data) {
            setCollection(result.data);
        }
    }, [result.data]);

    const columns: DataColumn<ItemBrand>[] = [
        { key: 'brand', header: 'Brand', render: (row) => <Link className="font-semibold text-sky-700 hover:underline" to={`/item-brands/${row.id}`}>{row.name}<span className="block text-xs font-normal text-slate-500">{row.code}</span></Link> },
        { key: 'description', header: 'Description', render: (row) => row.description ?? '-' },
        { key: 'status', header: 'Status', render: (row) => <StatusBadge status={row.is_active ? 'active' : 'inactive'} /> },
    ];
    if (canManage) {
        columns.push({ key: 'actions', header: '', className: 'text-right', render: (row) => <div className="flex justify-end gap-2"><LinkButton to={`/item-brands/${row.id}/edit`} variant="secondary">Edit</LinkButton><Button variant="ghost" onClick={() => void toggle(row)}>{row.is_active ? 'Deactivate' : 'Activate'}</Button><Button variant="danger" onClick={() => void remove(row)}>Delete</Button></div> });
    }

    return <>
        <ContentHeader title="Brands" description="Item brand names and status." actions={canManage ? <LinkButton to="/item-brands/create">Create Brand</LinkButton> : undefined} />
        <div className="mb-5 grid gap-4 md:grid-cols-2">
            <Input type="search" label="Search" placeholder="Code or name" value={search} onChange={(event) => { setSearch(event.target.value); setPage(1); }} />
            <Select label="Status" value={active} onChange={(event) => { setActive(event.target.value); setPage(1); }} placeholder="Any status" options={[{ value: 'true', label: 'Active' }, { value: 'false', label: 'Inactive' }]} />
        </div>
        <ErrorAlert error={actionError ?? result.error} />
        {result.loading && !collection ? <LoadingState /> : <DataTable rows={collection?.data ?? []} columns={columns} rowKey={(row) => row.id} emptyMessage="No item brands found." />}
        <Pagination meta={collection?.meta} onPageChange={setPage} />
        {confirmDialog}
    </>;

    async function toggle(row: ItemBrand) {
        setActionError(null);
        try {
            const updated = await updateItemBrand(Number(row.id), {
                code: row.code,
                name: row.name,
                description: row.description ?? null,
                is_active: !row.is_active,
            });
            setCollection((current) => updateBrandCollection(current, updated, active));
            notifySuccess(updated.is_active ? 'Brand activated successfully.' : 'Brand deactivated successfully.');
        } catch (error) {
            setActionError(toApiError(error));
        }
    }

    async function remove(row: ItemBrand) {
        if (!await confirm({ title: 'Delete brand', message: `Delete brand “${row.name}” (${row.code})? This cannot be undone.`, confirmLabel: 'Delete brand' })) return;
        setActionError(null);
        try {
            await deleteItemBrand(Number(row.id));
            setCollection((current) => removeBrandFromCollection(current, row.id));
            notifySuccess('Brand deleted successfully.');
        } catch (error) {
            setActionError(toApiError(error));
        }
    }
}

function updateBrandCollection(
    collection: Awaited<ReturnType<typeof listItemBrands>> | null,
    updated: ItemBrand,
    activeFilter: string,
) {
    if (collection === null) return collection;

    const rows = collection.data ?? [];
    const currentIndex = rows.findIndex((row) => row.id === updated.id);
    const matches = activeFilter === '' || (activeFilter === 'true' ? updated.is_active : !updated.is_active);

    if (!matches) {
        return removeBrandFromCollection(collection, updated.id);
    }

    if (currentIndex === -1) return collection;

    return {
        ...collection,
        data: rows.map((row) => row.id === updated.id ? updated : row),
    };
}

function removeBrandFromCollection(
    collection: Awaited<ReturnType<typeof listItemBrands>> | null,
    brandId: number,
) {
    if (collection === null) return collection;

    const rows = collection.data ?? [];
    const nextRows = rows.filter((row) => row.id !== brandId);
    if (nextRows.length === rows.length) return collection;

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
