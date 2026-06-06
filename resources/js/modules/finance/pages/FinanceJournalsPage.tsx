import { useState } from 'react';
import { listLedgerEntries } from '../financeApi';
import { useApi } from '@/shared/hooks/useApi';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { CapabilityNotice } from '@/shared/components/CapabilityNotice';
import { DataTable } from '@/shared/components/DataTable';
import { Pagination } from '@/shared/components/Pagination';
import { MoneyDisplay } from '@/shared/components/MoneyDisplay';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { formatDate } from '@/shared/utils/formatDate';
import { readableRelation } from '@/shared/utils/object';

export default function FinanceJournalsPage() {
    const [page, setPage] = useState(1);
    const result = useApi((signal) => listLedgerEntries({ page, per_page: 25 }, signal), [page]);
    return (
        <>
            <ContentHeader title="Journal activity" description="Ledger-backed finance activity." />
            <div className="mb-4"><CapabilityNotice>The Finance API can create, post, and reverse journals, but does not expose journal list/detail GET endpoints. This page uses the available ledger feed and does not fabricate journal records.</CapabilityNotice></div>
            <ErrorAlert error={result.error} />
            {result.loading ? <LoadingState /> : <DataTable rows={result.data?.data ?? []} rowKey={(row) => row.id} columns={[
                { key: 'date', header: 'Date', render: (row) => formatDate(row.entry_date) },
                { key: 'account', header: 'Account', render: (row) => readableRelation(row.account) },
                { key: 'journal', header: 'Journal', render: (row) => readableRelation(row.journal_entry) },
                { key: 'debit', header: 'Debit', render: (row) => <MoneyDisplay value={row.debit} /> },
                { key: 'credit', header: 'Credit', render: (row) => <MoneyDisplay value={row.credit} /> },
            ]} />}
            <Pagination meta={result.data?.meta} onPageChange={setPage} />
        </>
    );
}
