import { useParams } from 'react-router-dom';
import { getAccount, getAccountBalance, listLedgerEntries } from '../financeApi';
import { useApi } from '@/shared/hooks/useApi';
import { useOnDemandTab } from '@/shared/hooks/useOnDemandTab';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { Tabs } from '@/shared/components/Tabs';
import { Panel } from '@/shared/components/Panel';
import { DetailGrid } from '@/shared/components/DetailGrid';
import { RecordTable } from '@/shared/components/RecordTable';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { readableRelation } from '@/shared/utils/object';
import { LinkButton } from '@/shared/components/Button';
import { MoneyDisplay } from '@/shared/components/MoneyDisplay';

const UNAVAILABLE_BALANCE = 'Unavailable';

type Tab = 'summary' | 'balance' | 'ledger' | 'children';
const tabs = [{ id: 'summary' as Tab, label: 'Summary' }, { id: 'balance' as Tab, label: 'Balance' }, { id: 'ledger' as Tab, label: 'Ledger' }, { id: 'children' as Tab, label: 'Child Accounts' }];

export default function FinanceAccountDetailPage() {
    const id = Number(useParams().id);
    const tabState = useOnDemandTab<Tab>('summary');
    const account = useApi((signal) => getAccount(id, signal), [id]);
    const balance = useApi((signal) => getAccountBalance(id, {}, signal), [id]);
    const ledger = useApi((signal) => listLedgerEntries({ account_id: id, per_page: 25 }, signal), [id], tabState.openedTabs.has('ledger'));
    if (account.loading) return <LoadingState />;
    if (!account.data) return <ErrorAlert error={account.error} />;
    const value = account.data;
    const currentBalance = balance.loading
        ? 'Loading…'
        : balance.error
            ? UNAVAILABLE_BALANCE
            : <MoneyDisplay value={balance.data?.balance ?? '0.000000'} />;

    return (
        <>
            <ContentHeader title={`${value.code ?? ''} ${value.name ?? ''}`.trim()} description="Finance account detail" actions={<LinkButton to={`/finance/accounts/${id}/edit`} variant="secondary">Edit account</LinkButton>} />
            <Panel className="p-0">
                <Tabs tabs={tabs} active={tabState.activeTab} onChange={tabState.openTab} />
                <div className="p-5">
                    {tabState.activeTab === 'summary' && <DetailGrid items={[
                        { label: 'Status', value: <StatusBadge status={value.is_active ? 'active' : 'inactive'} /> },
                        { label: 'Type', value: readableRelation(value.account_type) },
                        { label: 'Category', value: readableRelation(value.account_category) },
                        { label: 'Parent', value: readableRelation(value.parent) },
                        { label: 'Normal balance', value: value.normal_balance },
                        { label: 'Postable', value: value.is_posting_account ? 'Yes' : 'No' },
                        { label: 'Current balance', value: currentBalance },
                    ]} />}
                    {tabState.activeTab === 'children' && <RecordTable rows={value.children ?? []} fields={['code', 'name', 'normal_balance', 'is_active']} rowKey={(row, index) => String(row.id ?? row.code ?? `child-account-${index}`)} />}
                    {tabState.activeTab === 'balance' && (balance.loading ? <LoadingState /> : balance.error ? <ErrorAlert error={balance.error} /> : <RecordTable rows={balance.data ? [balance.data] : []} fields={['opening_debit', 'opening_credit', 'period_debit', 'period_credit', 'closing_debit', 'closing_credit', 'balance']} rowKey={() => `account-${id}-balance`} />)}
                    {tabState.activeTab === 'ledger' && (ledger.loading ? <LoadingState /> : ledger.error ? <ErrorAlert error={ledger.error} /> : <RecordTable rows={ledger.data?.data ?? []} fields={['entry_date', 'journal_entry', 'debit', 'credit', 'source_number']} rowKey={(row, index) => String(row.id ?? `${String(row.journal_entry ?? row.source_number ?? 'ledger')}-${String(row.entry_date ?? index)}`)} />)}
                </div>
            </Panel>
        </>
    );
}
