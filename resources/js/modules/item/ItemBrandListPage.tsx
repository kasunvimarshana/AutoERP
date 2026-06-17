import { useState } from 'react';
import { Link } from 'react-router-dom';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button, LinkButton } from '@/shared/components/Button';
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
import { useAuth } from '@/modules/auth/AuthProvider';
import { deleteItemBrand, listItemBrands, updateItemBrand } from './itemApi';
import type { ItemBrand } from './itemTypes';
import { hasItemPermission, itemPermissions } from './itemPermissions';

export default function ItemBrandListPage() {
    const auth = useAuth();
    const canManage = hasItemPermission(auth.permissions, itemPermissions.manageBrands);
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
        {result.loading ? <LoadingState /> : <DataTable rows={result.data?.data ?? []} columns={columns} rowKey={(row) => row.id} emptyMessage="No item brands found." />}
        <Pagination meta={result.data?.meta} onPageChange={setPage} />
    </>;

    async function toggle(row: ItemBrand) {
        setActionError(null);
        try {
            await updateItemBrand(Number(row.id), {
                code: row.code,
                name: row.name,
                description: row.description ?? null,
                is_active: !row.is_active,
            });
            result.reload();
        } catch (error) {
            setActionError(toApiError(error));
        }
    }

    async function remove(row: ItemBrand) {
        if (!window.confirm(`Delete brand ${row.code}?`)) return;
        setActionError(null);
        try {
            await deleteItemBrand(Number(row.id));
            result.reload();
        } catch (error) {
            setActionError(toApiError(error));
        }
    }
}
