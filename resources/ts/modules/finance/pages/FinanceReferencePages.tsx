import { useEffect, useState, type ReactNode } from 'react';
import { useParams } from 'react-router-dom';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { DataToolbar, type DataToolbarFilterValue } from '../../../shared/components/data/DataToolbar';
import { Tabs } from '../../../shared/components/ui/Tabs';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import {
    ApTransactionTable,
    ApiErrorBanner,
    ArTransactionTable,
    BankAccountTable,
    BankReconciliationPanel,
    BankTransactionTable,
    BudgetLineTable,
    BudgetTable,
    BudgetUsagePanel,
    CostCenterTable,
    FiscalPeriodTable,
    FiscalYearTable,
    PaymentTermTable,
    TaxGroupTable,
    TaxPreviewPanel,
    TaxRateTable,
    TaxRuleTable,
} from '../components/FinanceComponents';
import { financeApi } from '../services/financeApi';
import type {
    ApTransaction,
    ArTransaction,
    BankAccount,
    BankReconciliation,
    BankTransaction,
    Budget,
    CostCenter,
    FiscalPeriod,
    FiscalYear,
    PaymentTerm,
    TaxGroup,
    TaxRate,
    TaxRule,
} from '../types/finance.types';

export function FiscalYearListPage() {
    return <RemoteList loader={financeApi.listFiscalYears} render={(rows: FiscalYear[]) => <FiscalYearTable rows={rows} />} subtitle="Fiscal year opening and closing is backend-controlled." title="Fiscal Years" />;
}

export function FiscalPeriodListPage() {
    return <RemoteList loader={financeApi.listFiscalPeriods} render={(rows: FiscalPeriod[]) => <FiscalPeriodTable rows={rows} />} subtitle="Period lock validation happens in backend posting engines." title="Fiscal Periods" />;
}

export function ApTransactionListPage() {
    return <RemoteList loader={financeApi.listApTransactions} render={(rows: ApTransaction[]) => <ApTransactionTable rows={rows} />} subtitle="Readonly payable balances and aging from backend." title="AP Transactions" />;
}

export function ArTransactionListPage() {
    return <RemoteList loader={financeApi.listArTransactions} render={(rows: ArTransaction[]) => <ArTransactionTable rows={rows} />} subtitle="Readonly receivable balances and aging from backend." title="AR Transactions" />;
}

export function TaxDashboardPage() {
    return (
        <div className="space-y-6">
            <PageHeader eyebrow="Finance" subtitle="Tax configuration and previews. Backend owns tax rules, priority, and tax amount calculation." title="Tax" />
            <div className="grid gap-4 md:grid-cols-3">
                <TaxGroupListPage />
                <TaxRateListPage />
                <TaxRuleListPage />
            </div>
            <TaxPreviewPanel />
        </div>
    );
}

export function TaxGroupListPage() {
    return <RemoteList loader={financeApi.listTaxGroups} render={(rows: TaxGroup[]) => <TaxGroupTable rows={rows} />} subtitle="Tax groups for reusable finance tax setup." title="Tax Groups" />;
}

export function TaxRateListPage() {
    return <RemoteList loader={financeApi.listTaxRates} render={(rows: TaxRate[]) => <TaxRateTable rows={rows} />} subtitle="Tax rates are configured here; calculated tax stays backend-owned." title="Tax Rates" />;
}

export function TaxRuleListPage() {
    return <RemoteList loader={financeApi.listTaxRules} render={(rows: TaxRule[]) => <TaxRuleTable rows={rows} />} subtitle="Tax rules are generic and source-reference friendly." title="Tax Rules" />;
}

export function PaymentTermListPage() {
    return <RemoteList loader={financeApi.listPaymentTerms} render={(rows: PaymentTerm[]) => <PaymentTermTable rows={rows} />} subtitle="Payment terms used by customers, suppliers, and documents. Due calculations stay backend-owned." title="Payment Terms" />;
}

export function CostCenterListPage() {
    return <RemoteList loader={financeApi.listCostCenters} render={(rows: CostCenter[]) => <CostCenterTable rows={rows} />} subtitle="Generic cost center setup for journal lines and module mappings." title="Cost Centers" />;
}

export function BankAccountListPage() {
    return <RemoteList loader={financeApi.listBankAccounts} render={(rows: BankAccount[]) => <BankAccountTable rows={rows} />} subtitle="Bank account setup. Reconciliation status and balances stay backend-owned." title="Bank Accounts" />;
}

export function BankTransactionListPage() {
    return <RemoteList loader={financeApi.listBankTransactions} render={(rows: BankTransaction[]) => <BankTransactionTable rows={rows} />} subtitle="Bank transactions with backend-owned reconciliation status." title="Bank Transactions" />;
}

export function BankReconciliationListPage() {
    return <RemoteList loader={financeApi.listReconciliations} render={(rows: BankReconciliation[]) => <BankReconciliationPanel rows={rows} />} subtitle="Reconciliation variance and matching decisions are backend-owned." title="Bank Reconciliations" />;
}

export function BudgetListPage() {
    return <RemoteList loader={financeApi.listBudgets} render={(rows: Budget[]) => <BudgetTable rows={rows} />} subtitle="Budget usage and variance are readonly backend values." title="Budgets" />;
}

export function BudgetDetailPage() {
    const { id = '' } = useParams();
    const [activeTab, setActiveTab] = useState('overview');
    const [budget, setBudget] = useState<Budget>();
    const [error, setError] = useState<Error | null>(null);

    useEffect(() => {
        financeApi.listBudgets({ search: id })
            .then((response) => setBudget(response.data.find((row) => row.id === id) ?? response.data[0]))
            .catch((caught: Error) => setError(caught));
    }, [id]);

    if (!budget) {
        return <EmptyState description="Loading budget detail from backend..." title="Loading" />;
    }

    return (
        <div className="space-y-6">
            <PageHeader
                eyebrow="Finance"
                subtitle="Budget detail. Usage and variance are backend read-model values, never calculated in the frontend."
                title={budget.name}
            />
            <ApiErrorBanner error={error} />
            <Tabs
                active={activeTab}
                items={[
                    { label: 'Overview', value: 'overview' },
                    { label: 'Budget Lines', value: 'lines' },
                    { label: 'Usage / Variance', value: 'usage' },
                    { label: 'Audit / History', value: 'audit' },
                ]}
                onChange={setActiveTab}
            />
            {activeTab === 'overview' ? <BudgetTable rows={[budget]} /> : null}
            {activeTab === 'lines' ? <BudgetLineTable rows={budget.lines} /> : null}
            {activeTab === 'usage' ? <BudgetUsagePanel rows={budget.lines.map((line) => ({ budgetAmount: line.budgetAmount, id: line.id, usedAmount: line.usage, varianceAmount: line.variance }))} /> : null}
            {activeTab === 'audit' ? <EmptyState description="No Finance audit/history endpoint is currently exposed for budgets." title="No audit read model" /> : null}
        </div>
    );
}

function RemoteList<T extends { id: string }>({
    loader,
    render,
    subtitle,
    title,
}: {
    loader: (query?: { search?: string; per_page?: number }) => Promise<{ data: T[] }>;
    render: (rows: T[]) => ReactNode;
    subtitle: string;
    title: string;
}) {
    const [rows, setRows] = useState<T[]>([]);
    const [search, setSearch] = useState('');
    const [filters, setFilters] = useState<Record<string, DataToolbarFilterValue>>({});
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<Error | null>(null);

    useEffect(() => {
        setLoading(true);
        loader({ search, status: String(filters.status ?? '') } as { search?: string; per_page?: number })
            .then((response) => {
                setRows(response.data);
                setError(null);
            })
            .catch((caught: Error) => setError(caught))
            .finally(() => setLoading(false));
    }, [filters.status, loader, search]);

    return (
        <div className="space-y-6">
            <PageHeader eyebrow="Finance" subtitle={subtitle} title={title} />
            <ApiErrorBanner error={error} />
            <DataToolbar
                disabled={loading}
                filterValues={filters}
                filters={[{ id: 'status', label: 'Status', type: 'status', options: [{ label: 'Active', value: 'active' }, { label: 'Inactive', value: 'inactive' }, { label: 'Open', value: 'open' }, { label: 'Closed', value: 'closed' }, { label: 'Draft', value: 'draft' }] }]}
                isLoading={loading}
                onFilterChange={(id, value) => setFilters((current) => ({ ...current, [id]: value }))}
                onResetFilters={() => setFilters({})}
                onSearchChange={setSearch}
                savedViewsDisabledReason="Saved views are not backed by a Finance preferences endpoint yet."
                searchPlaceholder={`Search ${title.toLowerCase()}...`}
                searchValue={search}
            />
            {rows.length ? render(rows) : <EmptyState description="No records returned by the backend for the current filters." title="No records" />}
        </div>
    );
}
