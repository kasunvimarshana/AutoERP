import { useState } from 'react';
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
import { deactivateUom, listUoms } from './uomApi';
import type { UnitOfMeasure } from './uomTypes';
import { uomCategories, uomTypes } from './uomTypes';

export default function UomListPage() {
    const [search, setSearch] = useState('');
    const [type, setType] = useState('');
    const [category, setCategory] = useState('');
    const [active, setActive] = useState('');
    const [page, setPage] = useState(1);
    const [refreshKey, setRefreshKey] = useState(0);
    const [actionError, setActionError] = useState<ApiError | null>(null);
    const debouncedSearch = useDebounce(search);
    const result = useApi((signal) => listUoms({
        search: debouncedSearch || undefined,
        type: type || undefined,
        category: category || undefined,
        is_active: active === '' ? undefined : active === 'true',
        page,
        per_page: 25,
    }, signal), [active, category, debouncedSearch, page, refreshKey, type]);

    const columns: DataColumn<UnitOfMeasure>[] = [
        { key: 'uom', header: 'UOM', render: (row) => <Link className="font-semibold text-sky-700 hover:underline" to={`/uoms/${row.id}`}>{row.code}<span className="block text-xs font-normal text-slate-500">{row.name} ({row.symbol})</span></Link> },
        { key: 'type', header: 'Type', render: (row) => row.type },
        { key: 'category', header: 'Category', render: (row) => row.category },
        { key: 'precision', header: 'Precision', render: (row) => row.decimal_precision },
        { key: 'base', header: 'Base', render: (row) => row.is_base ? 'Yes' : 'No' },
        { key: 'status', header: 'Status', render: (row) => <StatusBadge status={row.is_active ? 'active' : 'inactive'} /> },
        {
            key: 'actions',
            header: '',
            className: 'text-right',
            render: (row) => (
                <div className="flex justify-end gap-3">
                    <Link className="text-sm font-semibold text-slate-600 hover:text-sky-700" to={`/uoms/${row.id}/edit`}>Edit</Link>
                    {row.is_active && (
                        <button type="button" className="text-sm font-semibold text-rose-600 hover:text-rose-700" onClick={async () => {
                            setActionError(null);
                            try {
                                await deactivateUom(row.id);
                                setRefreshKey((value) => value + 1);
                            } catch (requestError) {
                                setActionError(toApiError(requestError));
                            }
                        }}>
                            Deactivate
                        </button>
                    )}
                </div>
            ),
        },
    ];

    return (
        <>
            <ContentHeader title="Units of Measure" description="Generic unit definitions and categories." actions={<LinkButton to="/uoms/create">New UOM</LinkButton>} />
            <div className="mb-4 grid gap-3 lg:grid-cols-4">
                <Input type="search" placeholder="Search code, name, or symbol" value={search} onChange={(event) => { setSearch(event.target.value); setPage(1); }} />
                <Select value={type} onChange={(event) => { setType(event.target.value); setPage(1); }} options={uomTypes.map((value) => ({ value, label: value }))} placeholder="All types" />
                <Select value={category} onChange={(event) => { setCategory(event.target.value); setPage(1); }} options={uomCategories.map((value) => ({ value, label: value }))} placeholder="All categories" />
                <Select value={active} onChange={(event) => { setActive(event.target.value); setPage(1); }} options={[{ value: 'true', label: 'Active' }, { value: 'false', label: 'Inactive' }]} placeholder="Any status" />
            </div>
            <ErrorAlert error={actionError ?? result.error} />
            {result.loading ? <LoadingState /> : <DataTable rows={result.data?.data ?? []} columns={columns} rowKey={(row) => row.id} />}
            <Pagination meta={result.data?.meta} onPageChange={setPage} />
        </>
    );
}
