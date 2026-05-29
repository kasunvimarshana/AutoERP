import { useEffect, useState } from 'react';
import type { ReactNode } from 'react';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { SearchFilterBar } from '../../../shared/components/data/SearchFilterBar';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import {
    ApTransactionTable,
    ArTransactionTable,
    BankAccountForm,
    BankAccountTable,
    BankReconciliationPanel,
    BankTransactionTable,
    BudgetForm,
    BudgetLineTable,
    BudgetTable,
    CostCenterForm,
    CostCenterTable,
    FiscalPeriodForm,
    FiscalPeriodTable,
    FiscalYearForm,
    FiscalYearTable,
    PaymentTermForm,
    PaymentTermTable,
    TaxGroupForm,
    TaxGroupTable,
    TaxPreviewPanel,
    TaxRateForm,
    TaxRateTable,
    TaxRuleForm,
    TaxRuleTable,
} from '../components/FinanceComponents';
import { budgets, taxPreview } from '../mock/financeMock';
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
    const [rows, setRows] = useState<FiscalYear[]>([]);
    useEffect(() => { financeApi.listFiscalYears().then((response) => setRows(response.data)); }, []);
    return <ListPage subtitle="Fiscal year opening and closing is backend-controlled." title="Fiscal Years"><FiscalYearForm /><FiscalYearTable rows={rows} /></ListPage>;
}

export function FiscalPeriodListPage() {
    const [rows, setRows] = useState<FiscalPeriod[]>([]);
    useEffect(() => { financeApi.listFiscalPeriods().then((response) => setRows(response.data)); }, []);
    return <ListPage subtitle="Period lock validation happens in backend posting engines." title="Fiscal Periods"><FiscalPeriodForm /><FiscalPeriodTable rows={rows} /></ListPage>;
}

export function ApTransactionListPage() {
    const [rows, setRows] = useState<ApTransaction[]>([]);
    useEffect(() => { financeApi.listApTransactions().then((response) => setRows(response.data)); }, []);
    return <ListPage subtitle="Readonly payable balances and aging from backend/mock." title="AP Transactions"><ApTransactionTable rows={rows} /></ListPage>;
}

export function ArTransactionListPage() {
    const [rows, setRows] = useState<ArTransaction[]>([]);
    useEffect(() => { financeApi.listArTransactions().then((response) => setRows(response.data)); }, []);
    return <ListPage subtitle="Readonly receivable balances and aging from backend/mock." title="AR Transactions"><ArTransactionTable rows={rows} /></ListPage>;
}

export function TaxDashboardPage() {
    return (
        <ListPage subtitle="Tax configuration and previews. Backend owns tax rules, priority, and tax amount calculation." title="Tax">
            <div className="grid gap-4 md:grid-cols-3">
                <TaxGroupForm />
                <TaxRateForm />
                <TaxRuleForm />
            </div>
            <TaxPreviewPanel preview={taxPreview} />
        </ListPage>
    );
}

export function TaxGroupListPage() {
    const [rows, setRows] = useState<TaxGroup[]>([]);
    useEffect(() => { financeApi.listTaxGroups().then((response) => setRows(response.data)); }, []);
    return <ListPage subtitle="Tax groups for reusable finance tax setup." title="Tax Groups"><TaxGroupForm /><TaxGroupTable rows={rows} /></ListPage>;
}

export function TaxRateListPage() {
    const [rows, setRows] = useState<TaxRate[]>([]);
    useEffect(() => { financeApi.listTaxRates().then((response) => setRows(response.data)); }, []);
    return <ListPage subtitle="Tax rates are configured here; calculated tax stays backend-owned." title="Tax Rates"><TaxRateForm /><TaxRateTable rows={rows} /></ListPage>;
}

export function TaxRuleListPage() {
    const [rows, setRows] = useState<TaxRule[]>([]);
    useEffect(() => { financeApi.listTaxRules().then((response) => setRows(response.data)); }, []);
    return <ListPage subtitle="Tax rules are generic and source-reference friendly." title="Tax Rules"><TaxRuleForm /><TaxRuleTable rows={rows} /></ListPage>;
}

export function PaymentTermListPage() {
    const [rows, setRows] = useState<PaymentTerm[]>([]);
    useEffect(() => { financeApi.listPaymentTerms().then((response) => setRows(response.data)); }, []);
    return <ListPage subtitle="Payment terms used by customers, suppliers, and documents. Due calculations stay backend-owned." title="Payment Terms"><PaymentTermForm /><PaymentTermTable rows={rows} /></ListPage>;
}

export function CostCenterListPage() {
    const [rows, setRows] = useState<CostCenter[]>([]);
    useEffect(() => { financeApi.listCostCenters().then((response) => setRows(response.data)); }, []);
    return <ListPage subtitle="Generic cost center setup for journal lines and module mappings." title="Cost Centers"><CostCenterForm /><CostCenterTable rows={rows} /></ListPage>;
}

export function BankAccountListPage() {
    const [rows, setRows] = useState<BankAccount[]>([]);
    useEffect(() => { financeApi.listBankAccounts().then((response) => setRows(response.data)); }, []);
    return <ListPage subtitle="Bank account setup. Reconciliation status and balances stay backend-owned." title="Bank Accounts"><BankAccountForm /><BankAccountTable rows={rows} /></ListPage>;
}

export function BankTransactionListPage() {
    const [rows, setRows] = useState<BankTransaction[]>([]);
    useEffect(() => { financeApi.listBankTransactions().then((response) => setRows(response.data)); }, []);
    return <ListPage subtitle="Bank transactions with backend-owned reconciliation status." title="Bank Transactions"><BankTransactionTable rows={rows} /></ListPage>;
}

export function BankReconciliationListPage() {
    const [rows, setRows] = useState<BankReconciliation[]>([]);
    useEffect(() => { financeApi.listReconciliations().then((response) => setRows(response.data)); }, []);
    return <ListPage subtitle="Reconciliation variance and matching decisions are backend-owned." title="Bank Reconciliations"><BankReconciliationPanel rows={rows} /></ListPage>;
}

export function BudgetListPage() {
    const [rows, setRows] = useState<Budget[]>([]);
    useEffect(() => { financeApi.listBudgets().then((response) => setRows(response.data)); }, []);
    return (
        <ListPage subtitle="Budget usage and variance are readonly backend/mock values." title="Budgets">
            <BudgetForm />
            <BudgetTable rows={rows} />
            <div className="space-y-3">
                <h2 className="text-sm font-bold uppercase tracking-wide text-slate-500">Budget Lines</h2>
                <BudgetLineTable rows={budgets[0]?.lines ?? []} />
            </div>
        </ListPage>
    );
}

function ListPage({ children, subtitle, title }: { children: ReactNode; subtitle: string; title: string }) {
    return (
        <div className="space-y-6">
            <PageHeader eyebrow="Finance" subtitle={subtitle} title={title} />
            <SearchFilterBar placeholder={`Search ${title.toLowerCase()}...`} />
            <div className="space-y-5">
                {children ?? <EmptyState description="No records returned yet." title="No records" />}
            </div>
        </div>
    );
}
