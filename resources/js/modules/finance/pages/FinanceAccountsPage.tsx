import { useState } from 'react';
import { Link } from 'react-router-dom';
import { listAccounts, type FinanceAccount } from '../financeApi';
import { useApi } from '@/shared/hooks/useApi';
import { useDebounce } from '@/shared/hooks/useDebounce';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { Input } from '@/shared/components/Input';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { Pagination } from '@/shared/components/Pagination';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { readableRelation } from '@/shared/utils/object';

export default function FinanceAccountsPage() {
    const [search, setSearch] = useState('');
    const [page, setPage] = useState(1);
    const debounced = useDebounce(search);
    const result = useApi((signal) => listAccounts({ search: debounced || undefined, page, per_page: 25 }, signal), [debounced, page]);
    const columns: DataColumn<FinanceAccount>[] = [
        { key: 'account', header: 'Account', render: (row) => <Link className="font-semibold text-sky-700 hover:underline" to={`/finance/accounts/${row.id}`}>{row.code} - {row.name}</Link> },
        { key: 'type', header: 'Type', render: (row) => readableRelation(row.account_type) },
        { key: 'category', header: 'Category', render: (row) => readableRelation(row.account_category) },
        { key: 'normal', header: 'Normal balance', render: (row) => row.normal_balance ?? '-' },
        { key: 'status', header: 'Status', render: (row) => <StatusBadge status={row.is_active ? 'active' : 'inactive'} /> },
    ];
    return (
        <>
            <ContentHeader title="Chart of accounts" description="Finance accounts with readable type and category resources." actions={<Link className="text-sm font-semibold text-sky-700 hover:underline" to="/finance/journals">Journal activity</Link>} />
            <div className="mb-4 max-w-md"><Input type="search" placeholder="Search account code or name" value={search} onChange={(event) => { setSearch(event.target.value); setPage(1); }} /></div>
            <ErrorAlert error={result.error} />
            {result.loading ? <LoadingState /> : <DataTable rows={result.data?.data ?? []} columns={columns} rowKey={(row) => row.id} />}
            <Pagination meta={result.data?.meta} onPageChange={setPage} />
        </>
    );
}
