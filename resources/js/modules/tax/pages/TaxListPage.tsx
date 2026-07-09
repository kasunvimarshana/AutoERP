import { useState } from 'react';
import { Link } from 'react-router-dom';
import { hasPermission } from '@/modules/auth/accessControl';
import { useAuth } from '@/modules/auth/AuthProvider';
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
import { listTaxes } from '../taxApi';
import { taxPermissions } from '../taxPermissions';
import type { Tax } from '../taxTypes';

export default function TaxListPage() {
    const auth = useAuth();
    const canManage = hasPermission(auth, taxPermissions.taxesManage);
    const [search, setSearch] = useState('');
    const [active, setActive] = useState('');
    const [page, setPage] = useState(1);
    const debounced = useDebounce(search);
    const result = useApi((signal) => listTaxes({
        search: debounced || undefined,
        active: active === '' ? undefined : active === 'true',
        page,
        per_page: 25,
    }, signal), [active, debounced, page]);

    const columns: DataColumn<Tax>[] = [
        {
            key: 'tax',
            header: 'Tax',
            render: (row) => canManage
                ? <Link className="font-semibold text-sky-700 hover:underline" to={`/tax/taxes/${row.id}/edit`}>{row.code}<span className="block text-xs font-normal text-slate-500">{row.name}</span></Link>
                : <span className="font-semibold text-slate-700">{row.code}<span className="block text-xs font-normal text-slate-500">{row.name}</span></span>,
        },
        { key: 'type', header: 'Type', render: (row) => row.tax_type },
        { key: 'method', header: 'Method', render: (row) => row.calculation_method },
        { key: 'flags', header: 'Flags', render: (row) => [row.recoverable && 'recoverable', row.payable && 'payable', row.receivable && 'receivable', row.is_withholding && 'withholding'].filter(Boolean).join(', ') || '-' },
        { key: 'status', header: 'Status', render: (row) => <StatusBadge status={row.active ? 'active' : 'inactive'} /> },
    ];

    return (
        <>
            <ContentHeader
                title="Tax Engine"
                description="Configurable tax master data, rates, and calculation methods."
                actions={(
                    <div className="flex gap-3 text-sm font-semibold">
                        <Link className="text-sky-700 hover:underline" to="/tax/groups">Tax groups</Link>
                        {canManage && (
                            <Link className="rounded-lg bg-sky-600 px-4 py-2 text-white hover:bg-sky-700" to="/tax/taxes/create">
                                New tax
                            </Link>
                        )}
                    </div>
                )}
            />
            <div className="mb-4 grid gap-3 md:grid-cols-2">
                <Input type="search" placeholder="Search code, name, or type" value={search} onChange={(event) => { setSearch(event.target.value); setPage(1); }} />
                <Select value={active} onChange={(event) => { setActive(event.target.value); setPage(1); }} options={[{ value: 'true', label: 'Active' }, { value: 'false', label: 'Inactive' }]} placeholder="Any status" />
            </div>
            <ErrorAlert error={result.error} />
            {result.loading ? <LoadingState /> : <DataTable rows={result.data?.data ?? []} columns={columns} rowKey={(row) => row.id} />}
            <Pagination meta={result.data?.meta} onPageChange={setPage} />
        </>
    );
}
