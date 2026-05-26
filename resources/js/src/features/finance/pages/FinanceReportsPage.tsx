import type { ReactNode } from 'react';
import { PageHeader } from '../../../components/layout/PageHeader';
import { EmptyState } from '../../../components/feedback/EmptyState';
import { ErrorState } from '../../../components/feedback/ErrorState';
import { LoadingState } from '../../../components/feedback/LoadingState';
import { Input } from '../../../components/forms/Input';
import { DataTable, SearchFilterToolbar, TableToolbar, type DataTableColumn } from '../../../components/tables';
import { ContentCard } from '../../../components/ui/ContentCard';
import { useSearchParams } from 'react-router-dom';
import { useTenant } from '../../auth/context/TenantContext';
import { useBalanceSheetReport, useGeneralLedgerReport, useProfitLossReport, useTrialBalanceReport } from '../hooks';
import { ProtectedErrorState, isForbiddenError } from '../../shared/ProtectedErrorState';
import { formatCurrency, formatDate, parsePositiveInteger } from '../../shared/utils';
import type { BalanceSheetRow, GeneralLedgerLine, TrialBalanceRow } from '../types';

type MetricCardProps = {
    label: string;
    value: string;
    hint?: string;
};

function MetricCard({ hint, label, value }: MetricCardProps) {
    return (
        <div className="rounded-2xl border border-stone-200 bg-stone-50/70 p-5">
            <p className="text-xs uppercase tracking-[0.16em] text-stone-500">{label}</p>
            <p className="mt-3 text-2xl font-semibold text-stone-950">{value}</p>
            {hint ? <p className="mt-2 text-sm text-stone-600">{hint}</p> : null}
        </div>
    );
}

export function FinanceReportsPage() {
    const { tenantId } = useTenant();
    const [searchParams, setSearchParams] = useSearchParams();

    const page = parsePositiveInteger(searchParams.get('page'), 1);
    const dateFrom = searchParams.get('date_from') ?? '';
    const dateTo = searchParams.get('date_to') ?? '';

    const generalLedgerQuery = useGeneralLedgerReport({
        tenant_id: tenantId,
        page,
        per_page: 10,
        date_from: dateFrom || undefined,
        date_to: dateTo || undefined,
    });
    const trialBalanceQuery = useTrialBalanceReport({
        tenant_id: tenantId,
        date_from: dateFrom || undefined,
        date_to: dateTo || undefined,
    });
    const balanceSheetQuery = useBalanceSheetReport({
        tenant_id: tenantId,
        as_of_date: dateTo || undefined,
    });
    const profitLossQuery = useProfitLossReport({
        tenant_id: tenantId,
        date_from: dateFrom || undefined,
        date_to: dateTo || undefined,
    });

    function updateParams(updates: Record<string, string | number | undefined>) {
        setSearchParams((current) => {
            const next = new URLSearchParams(current);

            for (const [key, value] of Object.entries(updates)) {
                if (value === undefined || value === '') {
                    next.delete(key);
                } else {
                    next.set(key, String(value));
                }
            }

            if ('date_from' in updates || 'date_to' in updates) {
                next.set('page', '1');
            }

            return next;
        });
    }

    const generalLedgerColumns: DataTableColumn<GeneralLedgerLine>[] = [
        {
            key: 'entry_number',
            header: 'Entry',
            render: (line) => (
                <div>
                    <p className="font-medium text-stone-950">{line.entry_number}</p>
                    <p className="mt-1 text-xs text-stone-500">{line.account_code} | {line.account_name}</p>
                </div>
            ),
        },
        { key: 'posting_date', header: 'Posting Date', render: (line) => <span className="text-sm text-stone-700">{formatDate(line.posting_date)}</span> },
        { key: 'line_description', header: 'Description', render: (line) => <span className="text-sm text-stone-700">{line.line_description || line.entry_description || '-'}</span> },
        { key: 'debit', header: 'Debit', render: (line) => <span className="text-sm text-stone-700">{formatCurrency(line.base_debit_amount)}</span> },
        { key: 'credit', header: 'Credit', render: (line) => <span className="text-sm text-stone-700">{formatCurrency(line.base_credit_amount)}</span> },
    ];

    const trialBalanceColumns: DataTableColumn<TrialBalanceRow>[] = [
        { key: 'account_code', header: 'Account', render: (row) => <span className="text-sm text-stone-700">{`${row.account_code} | ${row.account_name}`}</span> },
        { key: 'total_debit', header: 'Debit', render: (row) => <span className="text-sm text-stone-700">{formatCurrency(row.total_debit)}</span> },
        { key: 'total_credit', header: 'Credit', render: (row) => <span className="text-sm text-stone-700">{formatCurrency(row.total_credit)}</span> },
        { key: 'net_balance', header: 'Net', render: (row) => <span className="text-sm text-stone-700">{formatCurrency(row.net_balance)}</span> },
    ];

    const statementColumns: DataTableColumn<BalanceSheetRow>[] = [
        { key: 'account_code', header: 'Account', render: (row) => <span className="text-sm text-stone-700">{`${row.account_code} | ${row.account_name}`}</span> },
        { key: 'balance', header: 'Balance', render: (row) => <span className="text-sm text-stone-700">{formatCurrency(row.balance)}</span> },
    ];

    const hasReportError = [generalLedgerQuery, trialBalanceQuery, balanceSheetQuery, profitLossQuery].some((query) => query.isError);
    const firstError = generalLedgerQuery.isError
        ? generalLedgerQuery.error
        : trialBalanceQuery.isError
          ? trialBalanceQuery.error
          : balanceSheetQuery.isError
            ? balanceSheetQuery.error
            : profitLossQuery.isError
              ? profitLossQuery.error
              : null;

    let reportContent: ReactNode;

    if (generalLedgerQuery.isPending || trialBalanceQuery.isPending || balanceSheetQuery.isPending || profitLossQuery.isPending) {
        reportContent = (
            <ContentCard>
                <LoadingState lines={12} />
            </ContentCard>
        );
    } else if (hasReportError && firstError) {
        reportContent = (
            <ContentCard>
                {isForbiddenError(firstError) ? (
                    <ProtectedErrorState description={firstError.message} />
                ) : (
                    <ErrorState description={firstError.message} title="Unable to load finance reports" />
                )}
            </ContentCard>
        );
    } else if (!generalLedgerQuery.data || !trialBalanceQuery.data || !balanceSheetQuery.data || !profitLossQuery.data) {
        reportContent = (
            <ContentCard>
                <EmptyState description="Report data is not available yet for the selected filters." title="No report data" />
            </ContentCard>
        );
    } else {
        const generalLedger = generalLedgerQuery.data;
        const trialBalance = trialBalanceQuery.data;
        const balanceSheet = balanceSheetQuery.data;
        const profitLoss = profitLossQuery.data;

        reportContent = (
            <>
                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <MetricCard
                        hint={trialBalance.summary.is_balanced ? 'Debits and credits are balanced.' : 'Review imbalances before close.'}
                        label="Trial Balance"
                        value={formatCurrency(trialBalance.summary.total_debit)}
                    />
                    <MetricCard
                        hint={balanceSheet.summary.is_balanced ? 'Assets match liabilities plus equity.' : 'Statement is out of balance.'}
                        label="Total Assets"
                        value={formatCurrency(balanceSheet.summary.total_assets)}
                    />
                    <MetricCard hint="Revenue less expenses for the selected period." label="Net Income" value={formatCurrency(profitLoss.summary.net_income)} />
                    <MetricCard hint="Posted journal lines in the selected ledger window." label="General Ledger Rows" value={String(generalLedger.meta.total)} />
                </div>

                <ContentCard className="p-0">
                    <TableToolbar description="The general ledger grid uses the paginated reporting endpoint and respects the selected date filters." title="General ledger">
                        <div className="text-sm text-stone-500">{generalLedger.meta.total} lines</div>
                    </TableToolbar>
                    <DataTable
                        columns={generalLedgerColumns}
                        emptyState={<EmptyState className="m-6" description="No posted ledger rows were returned for the selected window." title="No ledger rows found" />}
                        footer={
                            generalLedger.meta.last_page > 1 ? (
                                <div className="flex items-center justify-between gap-4">
                                    <span className="text-sm text-stone-500">
                                        Page {generalLedger.meta.current_page} of {generalLedger.meta.last_page}
                                    </span>
                                    <div className="flex gap-2">
                                        <button
                                            className="inline-flex h-10 items-center justify-center rounded-xl border border-stone-200 px-4 text-sm font-medium text-stone-700 transition hover:bg-stone-50 disabled:cursor-not-allowed disabled:opacity-50"
                                            disabled={generalLedger.meta.current_page <= 1}
                                            onClick={() => updateParams({ page: generalLedger.meta.current_page - 1 })}
                                            type="button"
                                        >
                                            Previous
                                        </button>
                                        <button
                                            className="inline-flex h-10 items-center justify-center rounded-xl border border-stone-200 px-4 text-sm font-medium text-stone-700 transition hover:bg-stone-50 disabled:cursor-not-allowed disabled:opacity-50"
                                            disabled={generalLedger.meta.current_page >= generalLedger.meta.last_page}
                                            onClick={() => updateParams({ page: generalLedger.meta.current_page + 1 })}
                                            type="button"
                                        >
                                            Next
                                        </button>
                                    </div>
                                </div>
                            ) : null
                        }
                        getRowKey={(line) => line.id}
                        rows={generalLedger.data}
                    />
                </ContentCard>

                <div className="grid gap-6 xl:grid-cols-2">
                    <ContentCard className="p-0">
                        <TableToolbar description="Top accounts contributing to the current trial balance." title="Trial balance" />
                        <DataTable
                            columns={trialBalanceColumns}
                            emptyState={<EmptyState className="m-6" description="No trial-balance rows were returned for the selected window." title="No trial balance data" />}
                            getRowKey={(row) => row.account_id}
                            rows={trialBalance.data}
                        />
                    </ContentCard>

                    <ContentCard className="p-0">
                        <TableToolbar description="Profit and loss statement rows grouped by revenue and expense account balances." title="Profit and loss" />
                        <DataTable
                            columns={statementColumns}
                            emptyState={<EmptyState className="m-6" description="No profit-and-loss rows were returned for the selected window." title="No profit and loss data" />}
                            getRowKey={(row) => `${row.account_type}-${row.account_id}`}
                            rows={[...profitLoss.revenues, ...profitLoss.expenses]}
                        />
                    </ContentCard>
                </div>

                <ContentCard className="p-0">
                    <TableToolbar description="Balance sheet rows across assets, liabilities, and equity as of the selected end date." title="Balance sheet" />
                    <DataTable
                        columns={statementColumns}
                        emptyState={<EmptyState className="m-6" description="No balance-sheet rows were returned for the selected as-of date." title="No balance sheet data" />}
                        getRowKey={(row) => `${row.account_type}-${row.account_id}`}
                        rows={[...balanceSheet.assets, ...balanceSheet.liabilities, ...balanceSheet.equity]}
                    />
                </ContentCard>
            </>
        );
    }

    return (
        <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
            <PageHeader
                breadcrumbs={[{ label: 'Finance' }, { label: 'Reports' }]}
                description="Finance reporting is now backed by the existing report endpoints, including general ledger, trial balance, balance sheet, and profit-loss summaries."
                title="Reports"
            />

            <ContentCard className="p-0">
                <TableToolbar description="Apply an optional reporting window. The balance sheet uses the end date as its as-of date when one is provided." title="Report filters">
                    <SearchFilterToolbar
                        filters={<Input className="w-full md:max-w-[14rem]" label={undefined} onChange={(event) => updateParams({ date_to: event.target.value || undefined })} type="date" value={dateTo} />}
                        search={<Input className="w-full md:max-w-[14rem]" label={undefined} onChange={(event) => updateParams({ date_from: event.target.value || undefined })} type="date" value={dateFrom} />}
                        trailing={<div className="text-sm text-stone-500">Tenant {tenantId}</div>}
                    />
                </TableToolbar>
            </ContentCard>

            {reportContent}
        </div>
    );
}
