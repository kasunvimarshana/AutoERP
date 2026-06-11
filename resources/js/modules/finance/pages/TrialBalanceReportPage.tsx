import { useState } from 'react';
import { Link } from 'react-router-dom';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { DataTable } from '@/shared/components/DataTable';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { MoneyDisplay } from '@/shared/components/MoneyDisplay';
import { Panel } from '@/shared/components/Panel';
import { useApi } from '@/shared/hooks/useApi';
import { getTrialBalance } from '../financeApi';

export default function TrialBalanceReportPage() {
    const [dateFrom, setDateFrom] = useState('');
    const [dateTo, setDateTo] = useState('');
    const result = useApi((signal) => getTrialBalance({
        date_from: dateFrom || undefined,
        date_to: dateTo || undefined,
    }, signal), [dateFrom, dateTo]);

    return <>
        <ContentHeader title="Trial balance" description="Ledger-derived debit and credit balances." actions={<Link className="text-sm font-semibold text-sky-700 hover:underline" to="/reports/finance.trial-balance">Export report</Link>} />
        <div className="mb-4 grid max-w-2xl gap-3 md:grid-cols-2">
            <Input label="From" type="date" value={dateFrom} onChange={(event) => setDateFrom(event.target.value)} />
            <Input label="To" type="date" value={dateTo} onChange={(event) => setDateTo(event.target.value)} />
        </div>
        <ErrorAlert error={result.error} />
        {result.loading || !result.data ? <LoadingState /> : <>
            <Panel className={`mb-4 ${result.data.isBalanced ? 'border-emerald-200 bg-emerald-50' : 'border-rose-200 bg-rose-50'}`}>
                <div className="flex flex-wrap justify-between gap-4 text-sm font-semibold">
                    <span>Total debit: <MoneyDisplay value={result.data.totalDebit} /></span>
                    <span>Total credit: <MoneyDisplay value={result.data.totalCredit} /></span>
                    <span>{result.data.isBalanced ? 'Balanced' : 'Out of balance'}</span>
                </div>
            </Panel>
            <DataTable rows={result.data.accountBalances} rowKey={(row) => row.accountId} columns={[
                { key: 'account', header: 'Account', render: (row) => `${row.accountCode} - ${row.accountName}` },
                { key: 'debit', header: 'Debit', render: (row) => <MoneyDisplay value={row.closingDebit} /> },
                { key: 'credit', header: 'Credit', render: (row) => <MoneyDisplay value={row.closingCredit} /> },
            ]} />
        </>}
    </>;
}
