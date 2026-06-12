import { useState } from 'react';
import { toApiError, type ApiError, fieldError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { DecimalInput } from '@/shared/components/DecimalInput';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { MoneyDisplay } from '@/shared/components/MoneyDisplay';
import { Panel } from '@/shared/components/Panel';
import { Select } from '@/shared/components/Select';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { useApi } from '@/shared/hooks/useApi';
import { createBudget, getBudgetActuals, getFinanceLookups, listBudgets, type Budget, type BudgetPayload } from '../financeApi';

const currentYear = new Date().getFullYear();
const initial: BudgetPayload = {
    name: '',
    budget_year: currentYear,
    status: 'draft',
    description: null,
    lines: [{ account_id: null, budget_month: 1, amount: '0.000000' }],
};

export default function BudgetPage() {
    const [form, setForm] = useState(initial);
    const [actuals, setActuals] = useState<Record<string, unknown> | null>(null);
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    const lookups = useApi((signal) => getFinanceLookups(signal), []);
    const budgets = useApi((signal) => listBudgets({ per_page: 25 }, signal), []);
    const accounts = (lookups.data?.accounts ?? []).filter((account) => account.is_active && account.is_posting_account);
    const columns: DataColumn<Budget>[] = [
        { key: 'name', header: 'Budget', render: (row) => <button type="button" className="font-semibold text-sky-700 hover:underline" onClick={() => void loadActuals(row)}>{row.name}</button> },
        { key: 'year', header: 'Year', render: (row) => row.budget_year },
        { key: 'status', header: 'Status', render: (row) => <StatusBadge status={row.status} /> },
        { key: 'lines', header: 'Lines', render: (row) => String(row.lines_count ?? row.lines?.length ?? 0) },
    ];

    async function save() {
        setSaving(true);
        setError(null);
        try {
            await createBudget({ ...form, lines: form.lines.filter((line) => line.account_id) });
            setForm(initial);
            budgets.reload();
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setSaving(false);
        }
    }

    async function loadActuals(budget: Budget) {
        setError(null);
        try {
            setActuals(await getBudgetActuals(budget.id));
        } catch (requestError) {
            setError(toApiError(requestError));
        }
    }

    return <>
        <ContentHeader title="Budgets" description="Annual and monthly budget amounts compared with ledger actuals." />
        <div className="grid gap-5 xl:grid-cols-[minmax(0,1fr)_minmax(28rem,34rem)]">
            <div className="space-y-5">
                <Panel title="Budgets">
                    <ErrorAlert error={budgets.error} />
                    {budgets.loading ? <LoadingState /> : <DataTable rows={budgets.data?.data ?? []} rowKey={(row) => row.id} columns={columns} />}
                </Panel>
                {actuals && <Panel title="Actual vs budget">
                    <div className="mb-4 flex flex-wrap gap-4 text-sm font-semibold text-slate-700">
                        <span>Budget <MoneyDisplay value={String(actuals.total_budget ?? '0.000000')} /></span>
                        <span>Actual <MoneyDisplay value={String(actuals.total_actual ?? '0.000000')} /></span>
                        <span>Variance <MoneyDisplay value={String(actuals.variance ?? '0.000000')} /></span>
                    </div>
                    <DataTable rows={Array.isArray(actuals.rows) ? actuals.rows as Array<Record<string, unknown>> : []} rowKey={(row) => `${row.account_code}-${row.budget_month ?? row.fiscal_period_id ?? 'annual'}`} columns={[
                        { key: 'account', header: 'Account', render: (row) => `${row.account_code} - ${row.account_name}` },
                        { key: 'period', header: 'Period', render: (row) => row.budget_month ? `Month ${row.budget_month}` : row.fiscal_period_id ? 'Fiscal period' : 'Annual' },
                        { key: 'budget', header: 'Budget', render: (row) => <MoneyDisplay value={String(row.budget_amount ?? '0.000000')} /> },
                        { key: 'actual', header: 'Actual', render: (row) => <MoneyDisplay value={String(row.actual_amount ?? '0.000000')} /> },
                        { key: 'variance', header: 'Variance', render: (row) => <MoneyDisplay value={String(row.variance ?? '0.000000')} /> },
                    ]} />
                </Panel>}
            </div>
            <Panel title="New budget">
                <ErrorAlert error={error} />
                <form className="space-y-4" onSubmit={(event) => { event.preventDefault(); void save(); }}>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <Input label="Name" value={form.name} onChange={(event) => setForm({ ...form, name: event.target.value })} error={fieldError(error, 'name')} required />
                        <Input label="Year" type="number" value={form.budget_year} onChange={(event) => setForm({ ...form, budget_year: Number(event.target.value) })} error={fieldError(error, 'budget_year')} required />
                    </div>
                    <div className="space-y-3">
                        {form.lines.map((line, index) => <div key={index} className="grid gap-3 sm:grid-cols-[1.4fr_.7fr_1fr_auto]">
                            <Select label="Account" value={line.account_id ?? ''} onChange={(event) => updateLine(index, { account_id: event.target.value ? Number(event.target.value) : null })} options={accounts.map((account) => ({ value: String(account.id), label: `${account.code} - ${account.name}` }))} error={fieldError(error, `lines.${index}.account_id`)} />
                            <Select label="Month" value={line.budget_month ?? ''} onChange={(event) => updateLine(index, { budget_month: event.target.value ? Number(event.target.value) : null })} options={Array.from({ length: 12 }, (_, month) => ({ value: String(month + 1), label: String(month + 1) }))} />
                            <DecimalInput label="Amount" value={line.amount} onChange={(event) => updateLine(index, { amount: event.target.value })} error={fieldError(error, `lines.${index}.amount`)} />
                            <div className="flex items-end"><Button type="button" variant="danger" disabled={form.lines.length <= 1} onClick={() => removeLine(index)}>Remove</Button></div>
                        </div>)}
                    </div>
                    <div className="flex flex-wrap justify-between gap-3">
                        <Button type="button" variant="secondary" onClick={() => setForm({ ...form, lines: [...form.lines, { account_id: null, budget_month: 1, amount: '0.000000' }] })}>Add line</Button>
                        <Button type="submit" loading={saving}>Create budget</Button>
                    </div>
                </form>
            </Panel>
        </div>
    </>;

    function updateLine(index: number, patch: Partial<BudgetPayload['lines'][number]>) {
        setForm({ ...form, lines: form.lines.map((line, lineIndex) => lineIndex === index ? { ...line, ...patch } : line) });
    }

    function removeLine(index: number) {
        setForm({ ...form, lines: form.lines.filter((_, lineIndex) => lineIndex !== index) });
    }
}
