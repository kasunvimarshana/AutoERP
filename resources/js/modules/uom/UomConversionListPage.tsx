import { useState } from 'react';
import { Link } from 'react-router-dom';
import { Button } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Pagination } from '@/shared/components/Pagination';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { useApi } from '@/shared/hooks/useApi';
import { useDebounce } from '@/shared/hooks/useDebounce';
import { listUomConversions } from './uomApi';
import type { UomConversion } from './uomTypes';

export default function UomConversionListPage() {
    const [search, setSearch] = useState('');
    const [page, setPage] = useState(1);
    const debouncedSearch = useDebounce(search);
    const result = useApi((signal) => listUomConversions({ search: debouncedSearch || undefined, page, per_page: 25 }, signal), [debouncedSearch, page]);

    const columns: DataColumn<UomConversion>[] = [
        { key: 'from', header: 'From', render: (row) => formatUom(row.from_uom) },
        { key: 'to', header: 'To', render: (row) => formatUom(row.to_uom) },
        { key: 'factor', header: 'Factor', render: (row) => row.conversion_factor },
        { key: 'status', header: 'Status', render: (row) => <StatusBadge status={row.is_active ? 'active' : 'inactive'} /> },
        { key: 'actions', header: '', className: 'text-right', render: (row) => <Link className="text-sm font-semibold text-slate-600 hover:text-sky-700" to={`/uom-conversions/${row.id}/edit`}>Edit</Link> },
    ];

    return (
        <>
            <ContentHeader title="UOM Conversions" description="Generic conversion factors between units." actions={<Link to="/uom-conversions/create"><Button>New conversion</Button></Link>} />
            <div className="mb-4 max-w-md"><Input type="search" placeholder="Search UOM code, name, or symbol" value={search} onChange={(event) => { setSearch(event.target.value); setPage(1); }} /></div>
            <ErrorAlert error={result.error} />
            {result.loading ? <LoadingState /> : <DataTable rows={result.data?.data ?? []} columns={columns} rowKey={(row) => row.id} />}
            <Pagination meta={result.data?.meta} onPageChange={setPage} />
        </>
    );
}

function formatUom(uom: UomConversion['from_uom']) {
    return uom ? <span>{uom.code}<span className="block text-xs text-slate-500">{uom.name}</span></span> : '-';
}
