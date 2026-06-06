import { useState } from 'react';
import { Link } from 'react-router-dom';
import { listSuppliers } from '../supplierApi';
import type { Supplier } from '../types';
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

export default function SupplierListPage() {
    const [search, setSearch] = useState('');
    const [page, setPage] = useState(1);
    const debouncedSearch = useDebounce(search);
    const result = useApi(
        (signal) => listSuppliers({ search: debouncedSearch || undefined, page, per_page: 25 }, signal),
        [debouncedSearch, page],
    );

    const columns: DataColumn<Supplier>[] = [
        { key: 'supplier', header: 'Supplier', render: (row) => <Link className="font-semibold text-sky-700 hover:underline" to={`/suppliers/${row.id}`}>{row.name}<span className="block text-xs font-normal text-slate-500">{row.code}</span></Link> },
        { key: 'type', header: 'Type', render: (row) => row.supplier_type },
        { key: 'contact', header: 'Contact', render: (row) => row.email ?? row.phone ?? '-' },
        { key: 'status', header: 'Status', render: (row) => <StatusBadge status={row.status} /> },
        { key: 'actions', header: '', className: 'text-right', render: (row) => <Link className="text-sm font-semibold text-slate-600 hover:text-sky-700" to={`/suppliers/${row.id}/edit`}>Edit</Link> },
    ];

    return (
        <>
            <ContentHeader title="Suppliers" description="Supplier master data and onboarding relationships." actions={<Link to="/suppliers/create"><Button>New supplier</Button></Link>} />
            <div className="mb-4 max-w-md"><Input type="search" placeholder="Search name, code, number, or email" value={search} onChange={(event) => { setSearch(event.target.value); setPage(1); }} /></div>
            <ErrorAlert error={result.error} />
            {result.loading ? <LoadingState /> : <DataTable rows={result.data?.data ?? []} columns={columns} rowKey={(row) => row.id} />}
            <Pagination meta={result.data?.meta} onPageChange={setPage} />
        </>
    );
}
