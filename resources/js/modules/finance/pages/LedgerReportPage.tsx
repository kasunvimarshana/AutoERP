import { useState } from 'react';
import { Link } from 'react-router-dom';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { DataTable } from '@/shared/components/DataTable';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { MoneyDisplay } from '@/shared/components/MoneyDisplay';
import { Pagination } from '@/shared/components/Pagination';
import { Select } from '@/shared/components/Select';
import { useApi } from '@/shared/hooks/useApi';
import { formatDate } from '@/shared/utils/formatDate';
import { getFinanceLookups, listLedgerEntries } from '../financeApi';

export default function LedgerReportPage() {
    const [accountId, setAccountId] = useState('');
    const [dateFrom, setDateFrom] = useState('');
    const [dateTo, setDateTo] = useState('');
    const [page, setPage] = useState(1);
    const lookups = useApi(getFinanceLookups, []);
    const result = useApi((signal) => listLedgerEntries({
        account_id: accountId || undefined,
        date_from: dateFrom || undefined,
        date_to: dateTo || undefined,
        page,
        per_page: 50,
    }, signal), [accountId, dateFrom, dateTo, page]);

    return <>
        <ContentHeader title="General ledger" description="Posted ledger entries with account, journal, and source traceability." actions={<Link className="text-sm font-semibold text-sky-700 hover:underline" to="/reports/finance.ledger">Export report</Link>} />
        <div className="mb-4 grid gap-3 md:grid-cols-3">
            <Select
                value={accountId}
                onChange={(event) => { setAccountId(event.target.value); setPage(1); }}
                options={(lookups.data?.accounts ?? []).map((account) => ({ value: String(account.id), label: `${account.code} - ${account.name}` }))}
                placeholder="All accounts"
            />
            <Input type="date" value={dateFrom} onChange={(event) => { setDateFrom(event.target.value); setPage(1); }} />
            <Input type="date" value={dateTo} onChange={(event) => { setDateTo(event.target.value); setPage(1); }} />
        </div>
        <ErrorAlert error={lookups.error ?? result.error} />
        {result.loading ? <LoadingState /> : <DataTable rows={result.data?.data ?? []} rowKey={(row) => row.id} columns={[
            { key: 'date', header: 'Date', render: (row) => formatDate(row.entry_date) },
            { key: 'account', header: 'Account', render: (row) => row.account ? `${row.account.code} - ${row.account.name}` : '-' },
            { key: 'journal', header: 'Journal', render: (row) => row.journal_entry ? <Link className="text-sky-700 hover:underline" to={`/finance/journals/${row.journal_entry.id}`}>{row.journal_entry.journal_number}</Link> : '-' },
            { key: 'source', header: 'Source', render: (row) => row.source_number ?? row.source_type ?? '-' },
            { key: 'debit', header: 'Debit', render: (row) => <MoneyDisplay value={row.debit} /> },
            { key: 'credit', header: 'Credit', render: (row) => <MoneyDisplay value={row.credit} /> },
            { key: 'balance', header: 'Balance after', render: (row) => <MoneyDisplay value={row.balance_after} /> },
        ]} />}
        <Pagination meta={result.data?.meta} onPageChange={setPage} />
    </>;
}
