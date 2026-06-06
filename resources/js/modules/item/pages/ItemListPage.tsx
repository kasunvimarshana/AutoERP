import { useState } from 'react';
import { Link } from 'react-router-dom';
import { listItems } from '../itemApi';
import type { Item } from '../types';
import { useApi } from '@/shared/hooks/useApi';
import { useDebounce } from '@/shared/hooks/useDebounce';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { Button } from '@/shared/components/Button';
import { Input } from '@/shared/components/Input';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { Pagination } from '@/shared/components/Pagination';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { readableRelation } from '@/shared/utils/object';

export default function ItemListPage() {
    const [search, setSearch] = useState('');
    const [page, setPage] = useState(1);
    const debouncedSearch = useDebounce(search);
    const result = useApi((signal) => listItems({ search: debouncedSearch || undefined, page, per_page: 25 }, signal), [debouncedSearch, page]);
    const columns: DataColumn<Item>[] = [
        { key: 'item', header: 'Item', render: (row) => <Link className="font-semibold text-sky-700 hover:underline" to={`/items/${row.id}`}>{row.name}<span className="block text-xs font-normal text-slate-500">{row.code}</span></Link> },
        { key: 'type', header: 'Type', render: (row) => row.item_type },
        { key: 'category', header: 'Category', render: (row) => readableRelation(row.category) },
        { key: 'brand', header: 'Brand', render: (row) => readableRelation(row.brand) },
        { key: 'uom', header: 'Base UOM', render: (row) => readableRelation(row.base_uom) },
        { key: 'status', header: 'Status', render: (row) => <StatusBadge status={row.is_active ? 'active' : 'inactive'} /> },
        { key: 'actions', header: '', className: 'text-right', render: (row) => <Link className="font-semibold text-slate-600 hover:text-sky-700" to={`/items/${row.id}/edit`}>Edit</Link> },
    ];
    return (
        <>
            <ContentHeader title="Items" description="Readable catalog resources backed by the Item module." actions={<Link to="/items/create"><Button>New item</Button></Link>} />
            <div className="mb-4 max-w-md"><Input type="search" placeholder="Search code, name, SKU, or barcode" value={search} onChange={(event) => { setSearch(event.target.value); setPage(1); }} /></div>
            <ErrorAlert error={result.error} />
            {result.loading ? <LoadingState /> : <DataTable rows={result.data?.data ?? []} columns={columns} rowKey={(row) => row.id} />}
            <Pagination meta={result.data?.meta} onPageChange={setPage} />
        </>
    );
}
