import { useEffect, useState } from 'react';
import { useAuth } from '@/modules/auth/AuthProvider';
import { hasPermission } from '@/modules/auth/accessControl';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { Pagination } from '@/shared/components/Pagination';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { MoneyDisplay } from '@/shared/components/MoneyDisplay';
import { formatBusinessDate } from '@/shared/utils/businessDate';
import type { PaginationMeta } from '@/shared/types/pagination';
import {
    listRentalExpenses,
    transitionRentalExpense,
    ExpenseStatus,
    EXPENSE_PERMISSION,
    type RentalExpense,
} from './rentalExpenseApi';
import { RentalExpenseEditor } from './RentalExpenseEditor';

export default function RentalExpensesPage() {
    const auth = useAuth();
    const canManage = hasPermission(auth, EXPENSE_PERMISSION.manage);

    const [rows, setRows] = useState<RentalExpense[]>([]);
    const [meta, setMeta] = useState<PaginationMeta>();
    const [page, setPage] = useState(1);
    const [revision, setRevision] = useState(0);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<ApiError | null>(null);

    const [creating, setCreating] = useState(false);
    const [selected, setSelected] = useState<RentalExpense | null>(null);
    const [actionLoading, setActionLoading] = useState(false);

    useEffect(() => {
        const controller = new AbortController();
        listRentalExpenses(page, controller.signal)
            .then(result => {
                if (!controller.signal.aborted) {
                    setRows(result.data);
                    setMeta(result.meta);
                    setError(null);
                }
            })
            .catch(failure => {
                if (!controller.signal.aborted) {
                    setRows([]);
                    setMeta(undefined);
                    setError(toApiError(failure));
                }
            })
            .finally(() => {
                if (!controller.signal.aborted) setLoading(false);
            });
        return () => controller.abort();
    }, [page, revision]);

    function reload() {
        setCreating(false);
        setSelected(null);
        setLoading(true);
        setRevision(v => v + 1);
    }

    async function handleTransition(expense: RentalExpense, next: ExpenseStatus, allocations?: Array<{ allocation_type: string; net_amount: string }>) {
        setActionLoading(true);
        setError(null);
        try {
            await transitionRentalExpense(expense.id, expense.row_version, next, allocations);
            reload();
        } catch (failure) {
            setError(toApiError(failure));
        } finally {
            setActionLoading(false);
        }
    }

    return (
        <div>
            <ContentHeader
                title="Rental Fleet Expenses"
                description="Record operational fleet costs (fuel, highway tolls, traffic fines, maintenance) and allocate them to customers or owner payable deductions."
                actions={
                    <>
                        <Button variant="secondary" onClick={reload} disabled={loading || actionLoading || creating}>
                            Reload
                        </Button>
                        {canManage && (
                            <Button onClick={() => { setCreating(true); setSelected(null); }} disabled={loading || actionLoading || creating}>
                                Record Expense
                            </Button>
                        )}
                    </>
                }
            />

            <ErrorAlert error={error} inline />

            {creating && (
                <RentalExpenseEditor
                    onSaved={reload}
                    onCancel={() => setCreating(false)}
                />
            )}

            {loading ? (
                <LoadingState label="Loading rental fleet expenses…" />
            ) : error ? null : (
                <div className="mt-5 overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
                    <table className="w-full text-left text-sm">
                        <caption className="sr-only">Rental fleet expenses list</caption>
                        <thead>
                            <tr className="border-b border-slate-200 bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-500">
                                {['Expense #', 'Vehicle', 'Type', 'Date', 'Gross Amount', 'Receipt / Ref', 'Status', 'Actions'].map(label => (
                                    <th className="px-4 py-3" key={label}>{label}</th>
                                ))}
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {rows.map(row => (
                                <tr key={row.id} className="transition-colors hover:bg-blue-50/40">
                                    <td className="px-4 py-3 font-medium text-slate-900">{row.expense_number}</td>
                                    <td className="px-4 py-3 text-slate-700">{row.vehicle.label}</td>
                                    <td className="px-4 py-3 text-slate-600">{row.expense_type_label}</td>
                                    <td className="px-4 py-3 text-slate-600">{formatBusinessDate(row.expense_date)}</td>
                                    <td className="px-4 py-3 text-slate-900 font-medium">
                                        <MoneyDisplay value={row.gross_amount} currency={row.currency.code} />
                                    </td>
                                    <td className="px-4 py-3 text-slate-600">{row.reference_number ?? '—'}</td>
                                    <td className="px-4 py-3">
                                        <StatusBadge status={row.status} />
                                    </td>
                                    <td className="px-4 py-3">
                                        <Button
                                            variant="secondary"
                                            disabled={actionLoading || creating}
                                            onClick={() => setSelected(selected?.id === row.id ? null : row)}
                                        >
                                            {selected?.id === row.id ? 'Close' : 'Review'}
                                        </Button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                    {rows.length === 0 && <p className="p-5 text-slate-500">No expenses recorded.</p>}
                </div>
            )}

            <Pagination meta={meta} disabled={loading || actionLoading} onPageChange={value => { setLoading(true); setPage(value); setSelected(null); }} />

            {selected && (
                <section className="mt-5 space-y-4 rounded-xl border border-slate-200 bg-white p-6 shadow-sm" aria-label="Expense review">
                    <div className="flex flex-wrap items-center justify-between gap-3 border-b pb-3">
                        <div>
                            <h2 className="text-xl font-semibold text-slate-900">{selected.expense_number} · {selected.expense_type_label} for {selected.vehicle.label}</h2>
                            <p className="text-xs text-slate-500">Date: {formatBusinessDate(selected.expense_date)} · Net: <MoneyDisplay value={selected.net_amount} currency={selected.currency.code} /> · Tax: <MoneyDisplay value={selected.tax_amount} currency={selected.currency.code} /></p>
                        </div>
                        <StatusBadge status={selected.status} />
                    </div>

                    {selected.description && (
                        <p className="text-sm text-slate-700"><span className="font-semibold text-slate-900">Description:</span> {selected.description}</p>
                    )}

                    {selected.allocations && selected.allocations.length > 0 && (
                        <div className="space-y-2">
                            <h3 className="text-sm font-semibold text-slate-900">Expense Allocations</h3>
                            <div className="overflow-x-auto rounded-lg border border-slate-200">
                                <table className="w-full text-left text-xs">
                                    <thead>
                                        <tr className="border-b border-slate-200 bg-slate-50 font-semibold uppercase text-slate-500">
                                            {['#', 'Type', 'Party', 'Amount'].map(l => <th className="px-3 py-2" key={l}>{l}</th>)}
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-100">
                                        {selected.allocations.map(a => (
                                            <tr key={a.id}>
                                                <td className="px-3 py-2">{a.sequence}</td>
                                                <td className="px-3 py-2 font-medium">{a.allocation_type_label}</td>
                                                <td className="px-3 py-2">{a.customer?.name ?? a.supplier?.name ?? 'Company'}</td>
                                                <td className="px-3 py-2 font-semibold text-slate-900"><MoneyDisplay value={a.total_amount} currency={selected.currency.code} /></td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    )}

                    {canManage && (
                        <div className="flex flex-wrap gap-2 border-t pt-4">
                            {selected.status === ExpenseStatus.Draft && (
                                <>
                                    <Button onClick={() => handleTransition(selected, ExpenseStatus.Submitted)} loading={actionLoading}>Submit for Approval</Button>
                                    <Button variant="danger" onClick={() => handleTransition(selected, ExpenseStatus.Reversed)} loading={actionLoading}>Cancel / Reverse</Button>
                                </>
                            )}
                            {selected.status === ExpenseStatus.Submitted && (
                                <>
                                    <Button variant="primary" onClick={() => handleTransition(selected, ExpenseStatus.Approved)} loading={actionLoading}>Approve</Button>
                                    <Button variant="secondary" onClick={() => handleTransition(selected, ExpenseStatus.Rejected)} loading={actionLoading}>Reject</Button>
                                    <Button variant="danger" onClick={() => handleTransition(selected, ExpenseStatus.Reversed)} loading={actionLoading}>Reverse</Button>
                                </>
                            )}
                            {selected.status === ExpenseStatus.Approved && (
                                <>
                                    <Button
                                        variant="primary"
                                        onClick={() => handleTransition(selected, ExpenseStatus.Allocated, [
                                            { allocation_type: 'company_cost', net_amount: selected.gross_amount },
                                        ])}
                                        loading={actionLoading}
                                    >
                                        Absorb as Company Cost
                                    </Button>
                                    <Button variant="danger" onClick={() => handleTransition(selected, ExpenseStatus.Reversed)} loading={actionLoading}>Reverse</Button>
                                </>
                            )}
                            {selected.status === ExpenseStatus.Rejected && (
                                <Button variant="secondary" onClick={() => handleTransition(selected, ExpenseStatus.Draft)} loading={actionLoading}>Re-open as Draft</Button>
                            )}
                        </div>
                    )}
                </section>
            )}
        </div>
    );
}
