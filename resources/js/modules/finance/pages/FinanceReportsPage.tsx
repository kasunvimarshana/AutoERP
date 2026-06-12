import { Link } from 'react-router-dom';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { DataTable } from '@/shared/components/DataTable';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { MoneyDisplay } from '@/shared/components/MoneyDisplay';
import { Panel } from '@/shared/components/Panel';
import { useApi } from '@/shared/hooks/useApi';
import { getApAging, getArAging, getBalanceSheet, getCashFlow, getProfitAndLoss, getTaxLiability } from '../financeApi';

const reportLinks = [
    ['Chart of Accounts', 'finance.chart-of-accounts'],
    ['Journal Report', 'finance.journals'],
    ['General Ledger', 'finance.ledger'],
    ['Trial Balance', 'finance.trial-balance'],
    ['Balance Sheet', 'finance.account-balances'],
    ['Cash Flow', 'finance.cash-flow'],
    ['AR Aging', 'finance.ar-aging'],
    ['AP Aging', 'finance.ap-aging'],
    ['Tax Liability', 'finance.tax-liability'],
    ['Tax Reconciliation', 'finance.tax-reconciliation'],
    ['Bank Reconciliation', 'finance.bank-reconciliation'],
    ['Actual vs Budget', 'finance.budget-vs-actual'],
];

export default function FinanceReportsPage() {
    const cashFlow = useApi((signal) => getCashFlow({}, signal), []);
    const profitLoss = useApi((signal) => getProfitAndLoss({}, signal), []);
    const balanceSheet = useApi((signal) => getBalanceSheet({}, signal), []);
    const ar = useApi((signal) => getArAging({}, signal), []);
    const ap = useApi((signal) => getApAging({}, signal), []);
    const tax = useApi((signal) => getTaxLiability({}, signal), []);

    return <>
        <ContentHeader title="Finance reports" description="Ledger, statement, aging, tax, reconciliation, and budget reports." />
        <div className="grid gap-5 xl:grid-cols-[minmax(0,1fr)_22rem]">
            <div className="space-y-5">
                <Panel title="Statement totals">
                    <ErrorAlert error={cashFlow.error ?? profitLoss.error ?? balanceSheet.error ?? tax.error} />
                    {cashFlow.loading || profitLoss.loading || balanceSheet.loading ? <LoadingState /> : <DataTable rows={[
                        { label: 'Net cash flow', value: cashFlow.data?.net_cash_flow },
                        { label: 'Net profit', value: profitLoss.data?.net_profit },
                        { label: 'Balance sheet difference', value: balanceSheet.data?.difference },
                        { label: 'Tax liability', value: tax.data?.net_tax_amount },
                    ]} rowKey={(row) => row.label} columns={[
                        { key: 'label', header: 'Metric', render: (row) => row.label },
                        { key: 'value', header: 'Amount', render: (row) => <MoneyDisplay value={String(row.value ?? '0.000000')} /> },
                    ]} />}
                </Panel>
                <Panel title="Aging totals">
                    <ErrorAlert error={ar.error ?? ap.error} />
                    {ar.loading || ap.loading ? <LoadingState /> : <DataTable rows={[
                        { label: 'Accounts receivable', current: ar.data?.buckets.current, d30: ar.data?.buckets['1_30'], d60: ar.data?.buckets['31_60'], d90: ar.data?.buckets['61_90'], over: ar.data?.buckets['90_plus'], total: ar.data?.total },
                        { label: 'Accounts payable', current: ap.data?.buckets.current, d30: ap.data?.buckets['1_30'], d60: ap.data?.buckets['31_60'], d90: ap.data?.buckets['61_90'], over: ap.data?.buckets['90_plus'], total: ap.data?.total },
                    ]} rowKey={(row) => row.label} columns={[
                        { key: 'label', header: 'Ledger', render: (row) => row.label },
                        { key: 'current', header: 'Current', render: (row) => <MoneyDisplay value={String(row.current ?? '0.000000')} /> },
                        { key: 'd30', header: '1-30', render: (row) => <MoneyDisplay value={String(row.d30 ?? '0.000000')} /> },
                        { key: 'd60', header: '31-60', render: (row) => <MoneyDisplay value={String(row.d60 ?? '0.000000')} /> },
                        { key: 'd90', header: '61-90', render: (row) => <MoneyDisplay value={String(row.d90 ?? '0.000000')} /> },
                        { key: 'over', header: '90+', render: (row) => <MoneyDisplay value={String(row.over ?? '0.000000')} /> },
                        { key: 'total', header: 'Total', render: (row) => <MoneyDisplay value={String(row.total ?? '0.000000')} /> },
                    ]} />}
                </Panel>
            </div>
            <Panel title="Exports">
                <div className="space-y-2">
                    {reportLinks.map(([label, key]) => <Link key={key} className="block rounded-md border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700 hover:border-sky-300 hover:text-sky-700" to={`/reports/${key}`}>{label}</Link>)}
                </div>
            </Panel>
        </div>
    </>;
}
