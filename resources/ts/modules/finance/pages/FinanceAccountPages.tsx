import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { SearchFilterBar } from '../../../shared/components/data/SearchFilterBar';
import { Tabs } from '../../../shared/components/ui/Tabs';
import { Button } from '../../../shared/components/ui/Button';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import {
    AccountForm,
    AccountLedgerTable,
    AccountSummaryCard,
    AccountTreeView,
    FinanceActivityTimeline,
} from '../components/FinanceComponents';
import { financeActivity, journalEntries } from '../mock/financeMock';
import { financeApi } from '../services/financeApi';
import type { Account } from '../types/finance.types';

export function AccountListPage() {
    const [rows, setRows] = useState<Account[]>([]);

    useEffect(() => {
        financeApi.listAccounts().then((response) => setRows(response.data));
    }, []);

    return (
        <div className="space-y-6">
            <PageHeader
                actions={<Link to="/finance/accounts/new"><Button>Create Account</Button></Link>}
                eyebrow="Finance"
                subtitle="Chart of accounts for generic accounting mappings. Account balances are displayed only when returned by backend."
                title="Chart of Accounts"
            />
            <SearchFilterBar placeholder="Search account code, name, type, parent..." />
            {rows.length ? <AccountTreeView rows={rows} /> : <EmptyState description="No accounts returned yet." title="No accounts" />}
        </div>
    );
}

export function AccountCreatePage() {
    return (
        <div className="space-y-6">
            <PageHeader eyebrow="Finance" subtitle="Create an account setup record. Backend validates hierarchy and accounting rules." title="Create Account" />
            <AccountForm />
        </div>
    );
}

export function AccountEditPage() {
    return (
        <div className="space-y-6">
            <PageHeader eyebrow="Finance" subtitle="Edit account setup. Backend remains authoritative for balance and posting effects." title="Edit Account" />
            <AccountForm mode="edit" />
        </div>
    );
}

export function AccountDetailPage() {
    const { id = 'acc-001' } = useParams();
    const [account, setAccount] = useState<Account>();
    const [activeTab, setActiveTab] = useState('overview');

    useEffect(() => {
        financeApi.getAccount(id).then((response) => setAccount(response.data));
    }, [id]);

    if (!account) {
        return <EmptyState description="Loading account details..." title="Loading" />;
    }

    return (
        <div className="space-y-6">
            <PageHeader
                actions={<Link to={`/finance/accounts/${account.id}/edit`}><Button>Edit Account</Button></Link>}
                eyebrow="Finance"
                subtitle="Account detail with backend-owned ledger, balance, and usage information."
                title={account.accountName}
            />
            <AccountSummaryCard account={account} />
            <Tabs
                active={activeTab}
                items={[
                    { label: 'Overview', value: 'overview' },
                    { label: 'Ledger Preview / Transactions', value: 'ledger' },
                    { label: 'Opening Balance', value: 'opening' },
                    { label: 'Usage / Mappings', value: 'usage' },
                    { label: 'Audit / History', value: 'audit' },
                ]}
                onChange={setActiveTab}
            />
            {activeTab === 'overview' ? <AccountSummaryCard account={account} /> : null}
            {activeTab === 'ledger' ? <AccountLedgerTable rows={journalEntries} /> : null}
            {activeTab === 'opening' ? <EmptyState description="Opening balance postings are backend-owned and will be returned by Finance API." title="Opening Balance" /> : null}
            {activeTab === 'usage' ? <EmptyState description="Mappings to AP, AR, inventory, bank, tax, and module defaults will render here." title="Usage / Mappings" /> : null}
            {activeTab === 'audit' ? <FinanceActivityTimeline rows={financeActivity} /> : null}
        </div>
    );
}
