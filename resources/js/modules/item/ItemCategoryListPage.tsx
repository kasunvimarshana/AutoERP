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
import { deleteItemCategory, listItemCategories, updateItemCategory } from './itemApi';
import type { ItemCategory } from './itemTypes';
import { hasItemPermission, itemPermissions } from './itemPermissions';

export default function ItemCategoryListPage() {
    const auth = useAuth();
    const canManage = hasItemPermission(auth, itemPermissions.manageCategories);
    const [search, setSearch] = useState('');
    const [active, setActive] = useState('');
    const [page, setPage] = useState(1);
    const [actionError, setActionError] = useState<ApiError | null>(null);
    const debounced = useDebounce(search);
    const result = useApi((signal) => listItemCategories({
        search: debounced || undefined,
        is_active: active === '' ? undefined : active === 'true',
        page,
        per_page: 25,
    }, signal), [debounced, active, page]);

    const columns: DataColumn<ItemCategory>[] = [
        { key: 'category', header: 'Category', render: (row) => <Link className="font-semibold text-sky-700 hover:underline" to={`/item-categories/${row.id}`}>{row.name}<span className="block text-xs font-normal text-slate-500">{row.code}</span></Link> },
        { key: 'parent', header: 'Parent Category', render: (row) => row.parent?.name ?? '-' },
        { key: 'status', header: 'Status', render: (row) => <StatusBadge status={row.is_active ? 'active' : 'inactive'} /> },
    ];
    if (canManage) {
        columns.push({ key: 'actions', header: '', className: 'text-right', render: (row) => <div className="flex justify-end gap-2"><LinkButton to={`/item-categories/${row.id}/edit`} variant="secondary">Edit</LinkButton><Button variant="ghost" onClick={() => void toggle(row)}>{row.is_active ? 'Deactivate' : 'Activate'}</Button><Button variant="danger" onClick={() => void remove(row)}>Delete</Button></div> });
    }

    return <>
        <ContentHeader title="Categories" description="Item category hierarchy and status." actions={canManage ? <LinkButton to="/item-categories/create">Create Category</LinkButton> : undefined} />
        <div className="mb-5 grid gap-4 md:grid-cols-2">
            <Input type="search" label="Search" placeholder="Code or name" value={search} onChange={(event) => { setSearch(event.target.value); setPage(1); }} />
            <Select label="Status" value={active} onChange={(event) => { setActive(event.target.value); setPage(1); }} placeholder="Any status" options={[{ value: 'true', label: 'Active' }, { value: 'false', label: 'Inactive' }]} />
        </div>
        <ErrorAlert error={actionError ?? result.error} />
        {result.loading ? <LoadingState /> : <DataTable rows={result.data?.data ?? []} columns={columns} rowKey={(row) => row.id} emptyMessage="No item categories found." />}
        <Pagination meta={result.data?.meta} onPageChange={setPage} />
    </>;

    async function toggle(row: ItemCategory) {
        setActionError(null);
        try {
            await updateItemCategory(Number(row.id), {
                code: row.code,
                name: row.name,
                parent_id: row.parent ? Number(row.parent.id) : null,
                description: row.description ?? null,
                sort_order: row.sort_order,
                is_active: !row.is_active,
            });
            result.reload();
        } catch (error) {
            setActionError(toApiError(error));
        }
    }

    async function remove(row: ItemCategory) {
        if (!window.confirm(`Delete category ${row.code}?`)) return;
        setActionError(null);
        try {
            await deleteItemCategory(Number(row.id));
            result.reload();
        } catch (error) {
            setActionError(toApiError(error));
        }
    }
}
