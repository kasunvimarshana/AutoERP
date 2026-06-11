import { useState } from 'react';
import { Link } from 'react-router-dom';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { DataTable } from '@/shared/components/DataTable';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { MoneyDisplay } from '@/shared/components/MoneyDisplay';
import { Pagination } from '@/shared/components/Pagination';
import { useApi } from '@/shared/hooks/useApi';
import { useDebounce } from '@/shared/hooks/useDebounce';
import { listAccountBalances } from '../financeApi';

export default function AccountBalanceReportPage() {
    const [search, setSearch] = useState('');
    const [dateFrom, setDateFrom] = useState('');
    const [dateTo, setDateTo] = useState('');
    const [page, setPage] = useState(1);
    const debounced = useDebounce(search);
    const result = useApi((signal) => listAccountBalances({
        search: debounced || undefined,
        date_from: dateFrom || undefined,
        date_to: dateTo || undefined,
        page,
        per_page: 50,
    }, signal), [debounced, dateFrom, dateTo, page]);

    return <>
        <ContentHeader title="Account balances" description="Opening, period movement, and closing balances from posted ledger entries." actions={<Link className="text-sm font-semibold text-sky-700 hover:underline" to="/reports/finance.account-balances">Export report</Link>} />
        <div className="mb-4 grid gap-3 md:grid-cols-3">
            <Input type="search" placeholder="Search account code or name" value={search} onChange={(event) => { setSearch(event.target.value); setPage(1); }} />
            <Input type="date" value={dateFrom} onChange={(event) => { setDateFrom(event.target.value); setPage(1); }} />
            <Input type="date" value={dateTo} onChange={(event) => { setDateTo(event.target.value); setPage(1); }} />
        </div>
        <ErrorAlert error={result.error} />
        {result.loading ? <LoadingState /> : <DataTable rows={result.data?.data ?? []} rowKey={(row) => row.account_id} columns={[
            { key: 'account', header: 'Account', render: (row) => <Link className="font-semibold text-sky-700 hover:underline" to={`/finance/accounts/${row.account_id}`}>{row.account_code} - {row.account_name}</Link> },
            { key: 'openingDr', header: 'Opening Dr', render: (row) => <MoneyDisplay value={row.opening_debit} /> },
            { key: 'openingCr', header: 'Opening Cr', render: (row) => <MoneyDisplay value={row.opening_credit} /> },
            { key: 'periodDr', header: 'Period Dr', render: (row) => <MoneyDisplay value={row.period_debit} /> },
            { key: 'periodCr', header: 'Period Cr', render: (row) => <MoneyDisplay value={row.period_credit} /> },
            { key: 'closingDr', header: 'Closing Dr', render: (row) => <MoneyDisplay value={row.closing_debit} /> },
            { key: 'closingCr', header: 'Closing Cr', render: (row) => <MoneyDisplay value={row.closing_credit} /> },
        ]} />}
        <Pagination meta={result.data?.meta} onPageChange={setPage} />
    </>;
}
