import { useEffect, useMemo, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { DataToolbar, type DataToolbarFilterValue } from '../../../shared/components/data/DataToolbar';
import { Tabs } from '../../../shared/components/ui/Tabs';
import { Button } from '../../../shared/components/ui/Button';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import {
    AccountForm,
    AccountLedgerTable,
    AccountSummaryCard,
    AccountTreeView,
    ApiErrorBanner,
    apiFieldErrors,
} from '../components/FinanceComponents';
import { financeApi } from '../services/financeApi';
import type { Account, AccountFormValues, JournalEntry } from '../types/finance.types';

export function AccountListPage() {
    const [rows, setRows] = useState<Account[]>([]);
    const [search, setSearch] = useState('');
    const [filters, setFilters] = useState<Record<string, DataToolbarFilterValue>>({});
    const [error, setError] = useState<Error | null>(null);
    const [loading, setLoading] = useState(false);

    useEffect(() => {
        setLoading(true);
        financeApi.listAccounts({
            account_group: String(filters.account_group ?? ''),
            is_active: filters.status === 'active' ? true : filters.status === 'inactive' ? false : undefined,
            normal_balance: String(filters.normal_balance ?? ''),
            search,
            type: String(filters.type ?? ''),
        })
            .then((response) => {
                setRows(response.data);
                setError(null);
            })
            .catch((caught: Error) => setError(caught))
            .finally(() => setLoading(false));
    }, [filters.account_group, filters.normal_balance, filters.status, filters.type, search]);

    return (
        <div className="space-y-6">
            <PageHeader
                actions={<Link to="/finance/accounts/new"><Button>Create Account</Button></Link>}
                eyebrow="Finance"
                subtitle="Chart of accounts for generic accounting mappings. Account balances are displayed only when returned by backend."
                title="Chart of Accounts"
            />
            <ApiErrorBanner error={error} />
            <DataToolbar
                disabled={loading}
                filterValues={filters}
                filters={[
                    { id: 'type', label: 'Type', type: 'select', options: ['asset', 'liability', 'equity', 'income', 'expense'].map((value) => ({ label: value, value })) },
                    { id: 'normal_balance', label: 'Normal balance', type: 'select', options: [{ label: 'Debit', value: 'DEBIT' }, { label: 'Credit', value: 'CREDIT' }] },
                    { id: 'account_group', label: 'Group', type: 'text', placeholder: 'cash_bank, tax, expense...' },
                    { id: 'status', label: 'Status', type: 'status', options: [{ label: 'Active', value: 'active' }, { label: 'Inactive', value: 'inactive' }] },
                ]}
                isLoading={loading}
                onFilterChange={(id, value) => setFilters((current) => ({ ...current, [id]: value }))}
                onResetFilters={() => setFilters({})}
                onSearchChange={setSearch}
                savedViewsDisabledReason="Saved views are not backed by a Finance preferences endpoint yet."
                searchPlaceholder="Search account code, name, type, parent..."
                searchValue={search}
            />
            {rows.length ? <AccountTreeView rows={rows} /> : <EmptyState description="No accounts returned for the current filters." title="No accounts" />}
        </div>
    );
}

export function AccountCreatePage() {
    const navigate = useNavigate();
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState<Error | null>(null);

    function submit(values: AccountFormValues): void {
        setSaving(true);
        financeApi.createAccount(values)
            .then(() => navigate('/finance/accounts'))
            .catch((caught: Error) => setError(caught))
            .finally(() => setSaving(false));
    }

    return (
        <div className="space-y-6">
            <PageHeader eyebrow="Finance" subtitle="Create an account setup record. Backend validates hierarchy and accounting rules." title="Create Account" />
            <ApiErrorBanner error={error} />
            <AccountForm errors={apiFieldErrors(error)} isSaving={saving} onSubmit={submit} />
        </div>
    );
}

export function AccountEditPage() {
    const { id = '' } = useParams();
    const navigate = useNavigate();
    const [account, setAccount] = useState<Account>();
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState<Error | null>(null);

    useEffect(() => {
        financeApi.getAccount(id).then((response) => setAccount(response.data)).catch((caught: Error) => setError(caught));
    }, [id]);

    function submit(values: AccountFormValues): void {
        setSaving(true);
        financeApi.updateAccount(id, values)
            .then(() => navigate(`/finance/accounts/${id}`))
            .catch((caught: Error) => setError(caught))
            .finally(() => setSaving(false));
    }

    const initialValues = useMemo(() => accountToForm(account), [account]);

    return (
        <div className="space-y-6">
            <PageHeader eyebrow="Finance" subtitle="Edit account setup. Backend remains authoritative for balances and posting effects." title="Edit Account" />
            <ApiErrorBanner error={error} />
            {account ? <AccountForm errors={apiFieldErrors(error)} initialValues={initialValues} isSaving={saving} mode="edit" onSubmit={submit} /> : <EmptyState description="Loading account from backend..." title="Loading" />}
        </div>
    );
}

export function AccountDetailPage() {
    const { id = '' } = useParams();
    const [account, setAccount] = useState<Account>();
    const [journals, setJournals] = useState<JournalEntry[]>([]);
    const [activeTab, setActiveTab] = useState('overview');
    const [error, setError] = useState<Error | null>(null);

    useEffect(() => {
        Promise.all([financeApi.getAccount(id), financeApi.listJournalEntries({ search: id })])
            .then(([accountResponse, journalResponse]) => {
                setAccount(accountResponse.data);
                setJournals(journalResponse.data);
                setError(null);
            })
            .catch((caught: Error) => setError(caught));
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
            <ApiErrorBanner error={error} />
            <AccountSummaryCard account={account} />
            <Tabs
                active={activeTab}
                items={[
                    { label: 'Overview', value: 'overview' },
                    { label: 'Ledger / Transactions', value: 'ledger' },
                    { label: 'Usage / Mappings', value: 'usage' },
                    { label: 'Audit / History', value: 'audit' },
                ]}
                onChange={setActiveTab}
            />
            {activeTab === 'overview' ? <AccountSummaryCard account={account} /> : null}
            {activeTab === 'ledger' ? <AccountLedgerTable rows={journals} /> : null}
            {activeTab === 'usage' ? <EmptyState description="No Finance account usage endpoint is currently exposed; mappings will appear here when backend returns them." title="No usage read model" /> : null}
            {activeTab === 'audit' ? <EmptyState description="No Finance audit endpoint is currently exposed for accounts." title="No audit read model" /> : null}
        </div>
    );
}

function accountToForm(account?: Account): Partial<AccountFormValues> {
    if (!account) {
        return {};
    }

    return {
        accountCode: account.accountCode,
        accountGroup: account.accountGroup ?? '',
        accountName: account.accountName,
        accountType: account.accountType,
        allowsManualPosting: account.allowsManualPosting,
        description: account.description ?? '',
        isBankAccount: account.isBankAccount,
        isCashAccount: account.isCashAccount,
        isControlAccount: account.isControlAccount,
        normalBalance: account.normalBalance,
        parentId: account.parentId ?? '',
        status: account.status,
    };
}
