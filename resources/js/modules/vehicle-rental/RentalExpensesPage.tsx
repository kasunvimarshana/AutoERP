import { useEffect, useState } from 'react';
import { useAuth } from '@/modules/auth/AuthProvider';
import { hasPermission } from '@/modules/auth/accessControl';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { Input } from '@/shared/components/Input';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { Pagination } from '@/shared/components/Pagination';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { MoneyDisplay } from '@/shared/components/MoneyDisplay';
import { Select } from '@/shared/components/Select';
import { LookupSelect } from '@/shared/components/LookupSelect';
import { formatBusinessDate } from '@/shared/utils/businessDate';
import type { NamedResource } from '@/shared/types/common';
import type { PaginationMeta } from '@/shared/types/pagination';
import {
    listRentalExpenses,
    transitionRentalExpense,
    billRentalExpenseAllocation,
    unlinkRentalExpenseAllocationInvoice,
    ExpenseStatus,
    EXPENSE_PERMISSION,
    type RentalExpense,
    type ExpenseAllocation,
} from './rentalExpenseApi';
import { searchCustomerAgreements, searchOwnerAgreements, getAgreement } from './agreementApi';
import { AgreementKind } from './agreements';
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

    const [allocating, setAllocating] = useState(false);
    const [allocType, setAllocType] = useState<'company_cost' | 'customer_recovery' | 'owner_deduction'>('customer_recovery');
    const [allocCustomerAgreement, setAllocCustomerAgreement] = useState<NamedResource | null>(null);
    const [allocOwnerAgreement, setAllocOwnerAgreement] = useState<NamedResource | null>(null);
    const [search, setSearch] = useState('');
    const [query, setQuery] = useState('');

    const [billingAllocation, setBillingAllocation] = useState<ExpenseAllocation | null>(null);
    const [billingDate, setBillingDate] = useState(() => new Date().toISOString().split('T')[0]);
    const [billingDueDate, setBillingDueDate] = useState('');
    const [billingRate, setBillingRate] = useState('1.000000');
    const [isDebitNote, setIsDebitNote] = useState(true);
    const [billingLoading, setBillingLoading] = useState(false);

    useEffect(() => {
        const controller = new AbortController();
        const filters = query ? { search: query } : undefined;
        listRentalExpenses(page, controller.signal, filters)
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
    }, [page, revision, query]);

    function reload() {
        setCreating(false);
        setSelected(null);
        setAllocating(false);
        setAllocCustomerAgreement(null);
        setAllocOwnerAgreement(null);
        setLoading(true);
        setRevision(v => v + 1);
    }

    async function handleTransition(expense: RentalExpense, next: ExpenseStatus, allocations?: Array<{
        allocation_type: string;
        net_amount: string;
        target_customer_agreement_id?: number | null;
        target_owner_agreement_id?: number | null;
    }>) {
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

    async function handleConfirmBilling(e: React.FormEvent) {
        e.preventDefault();
        if (!billingAllocation || !billingAllocation.target_customer_agreement) return;

        setBillingLoading(true);
        setError(null);
        try {
            const customerAg = await getAgreement(AgreementKind.Customer, billingAllocation.target_customer_agreement.id);
            await billRentalExpenseAllocation(billingAllocation.id, {
                expected_allocation_version: billingAllocation.row_version ?? 1,
                expected_agreement_version: customerAg.row_version,
                invoice_date: billingDate,
                due_date: billingDueDate || null,
                exchange_rate: billingRate || '1.000000',
                is_debit_note: isDebitNote,
            });
            setBillingAllocation(null);
            reload();
        } catch (failure) {
            setError(toApiError(failure));
        } finally {
            setBillingLoading(false);
        }
    }

    async function handleUnlinkInvoice(allocation: ExpenseAllocation) {
        setActionLoading(true);
        setError(null);
        try {
            await unlinkRentalExpenseAllocationInvoice(allocation.id);
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

            <form
                onSubmit={event => {
                    event.preventDefault();
                    setPage(1);
                    setQuery(search.trim());
                }}
                className="mt-4 flex flex-wrap items-center gap-2"
            >
                <div className="w-80">
                    <Input
                        name="search"
                        placeholder="Search by expense #, reference, or vehicle…"
                        value={search}
                        onChange={event => setSearch(event.target.value)}
                    />
                </div>
                <Button type="submit" variant="secondary" disabled={loading}>
                    Search
                </Button>
                {query && (
                    <Button
                        type="button"
                        variant="ghost"
                        onClick={() => {
                            setSearch('');
                            setQuery('');
                            setPage(1);
                        }}
                    >
                        Clear
                    </Button>
                )}
            </form>

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
                                            onClick={() => { setSelected(selected?.id === row.id ? null : row); setAllocating(false); }}
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
                    <div className="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 pb-3">
                        <div>
                            <h2 className="text-xl font-semibold text-slate-900">{selected.expense_number} · {selected.expense_type_label} for {selected.vehicle.label}</h2>
                            <p className="text-xs text-slate-500">Date: {formatBusinessDate(selected.expense_date)} · Net: <MoneyDisplay value={selected.net_amount} currency={selected.currency.code} /> · Tax: <MoneyDisplay value={selected.tax_amount} currency={selected.currency.code} /></p>
                        </div>
                        <div className="flex items-center gap-2">
                            <StatusBadge status={selected.status} />
                            <Button variant="secondary" onClick={() => setSelected(null)}>Close</Button>
                        </div>
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
                                        <tr className="border-b border-slate-200 bg-slate-50 font-semibold uppercase tracking-wider text-slate-500">
                                            {['#', 'Type', 'Party', 'Amount', 'Billing Status', 'Actions'].map(l => <th className="px-3 py-2" key={l}>{l}</th>)}
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-100">
                                        {selected.allocations.map(a => (
                                            <tr key={a.id} className="transition-colors hover:bg-blue-50/40">
                                                <td className="px-3 py-2">{a.sequence}</td>
                                                <td className="px-3 py-2 font-medium">{a.allocation_type_label}</td>
                                                <td className="px-3 py-2">
                                                    {a.target_customer_agreement
                                                        ? `${a.customer?.name ?? 'Customer'} (${a.target_customer_agreement.reference})`
                                                        : a.target_owner_agreement
                                                            ? `${a.supplier?.name ?? 'Owner'} (${a.target_owner_agreement.reference})`
                                                            : (a.customer?.name ?? a.supplier?.name ?? 'Company Overhead')}
                                                </td>
                                                <td className="px-3 py-2 font-semibold text-slate-900"><MoneyDisplay value={a.total_amount} currency={selected.currency.code} /></td>
                                                <td className="px-3 py-2">
                                                    {a.invoice ? (
                                                        <div className="flex items-center gap-1.5">
                                                            <span className="font-semibold text-blue-700">{a.invoice.invoice_number}</span>
                                                            <StatusBadge status={a.invoice.status} />
                                                        </div>
                                                    ) : a.allocation_type === 'customer_recovery' ? (
                                                        <span className="text-amber-700 font-medium">Unbilled recovery</span>
                                                    ) : a.allocation_type === 'owner_deduction' ? (
                                                        <span className="text-slate-600">Settlement deduction</span>
                                                    ) : (
                                                        <span className="text-slate-400">Overhead</span>
                                                    )}
                                                </td>
                                                <td className="px-3 py-2">
                                                    {canManage && a.allocation_type === 'customer_recovery' && !a.invoice && (
                                                        <Button
                                                            className="min-h-8 px-2.5 py-1 text-xs"
                                                            variant="primary"
                                                            onClick={() => setBillingAllocation(a)}
                                                            disabled={actionLoading || billingLoading}
                                                        >
                                                            Bill Customer
                                                        </Button>
                                                    )}
                                                    {canManage && a.invoice && ['cancelled', 'void', 'reversed'].includes(a.invoice.status.toLowerCase()) && (
                                                        <Button
                                                            className="min-h-8 px-2.5 py-1 text-xs"
                                                            variant="secondary"
                                                            onClick={() => handleUnlinkInvoice(a)}
                                                            disabled={actionLoading}
                                                        >
                                                            Unlink
                                                        </Button>
                                                    )}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    )}

                    {billingAllocation && (
                        <form onSubmit={handleConfirmBilling} className="rounded-lg border border-indigo-200 bg-indigo-50/50 p-4 space-y-3">
                            <div className="flex items-center justify-between border-b border-indigo-100 pb-2">
                                <h4 className="text-sm font-semibold text-slate-900">
                                    Bill Customer Recovery: <MoneyDisplay value={billingAllocation.total_amount} currency={selected.currency.code} />
                                </h4>
                                <Button type="button" variant="ghost" onClick={() => setBillingAllocation(null)}>
                                    Cancel
                                </Button>
                            </div>
                            <p className="text-xs text-slate-600">
                                Create an outbound billing document against agreement {billingAllocation.target_customer_agreement?.reference} for this fleet recovery.
                            </p>
                            <div className="grid grid-cols-1 gap-3 sm:grid-cols-3">
                                <Input
                                    label="Invoice Date"
                                    type="date"
                                    value={billingDate}
                                    onChange={e => setBillingDate(e.target.value)}
                                    required
                                />
                                <Input
                                    label="Due Date (Optional)"
                                    type="date"
                                    value={billingDueDate}
                                    onChange={e => setBillingDueDate(e.target.value)}
                                />
                                <Input
                                    label="Exchange Rate"
                                    type="text"
                                    value={billingRate}
                                    onChange={e => setBillingRate(e.target.value)}
                                    required
                                />
                            </div>
                            <div className="flex items-center gap-2">
                                <input
                                    type="checkbox"
                                    id="isDebitNote"
                                    checked={isDebitNote}
                                    onChange={e => setIsDebitNote(e.target.checked)}
                                    className="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                />
                                <label htmlFor="isDebitNote" className="text-xs font-medium text-slate-700">
                                    Issue as Debit Note adjustment (unchecked issues standard Sales Invoice)
                                </label>
                            </div>
                            <div className="flex justify-end gap-2 pt-2">
                                <Button type="button" variant="secondary" onClick={() => setBillingAllocation(null)} disabled={billingLoading}>
                                    Cancel
                                </Button>
                                <Button type="submit" variant="primary" loading={billingLoading}>
                                    Generate & Issue Invoice
                                </Button>
                            </div>
                        </form>
                    )}

                    {canManage && (
                        <div className="flex flex-wrap gap-2 border-t border-slate-200 pt-4">
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
                            {selected.status === ExpenseStatus.Approved && !allocating && (
                                <>
                                    <Button
                                        variant="primary"
                                        onClick={() => setAllocating(true)}
                                        disabled={actionLoading}
                                    >
                                        Allocate to Agreement
                                    </Button>
                                    <Button
                                        variant="secondary"
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

                    {allocating && selected.status === ExpenseStatus.Approved && (
                        <div className="mt-4 rounded-lg border border-blue-200 bg-blue-50/50 p-4 space-y-3">
                            <h3 className="text-sm font-semibold text-slate-900">Allocate Expense: <MoneyDisplay value={selected.gross_amount} currency={selected.currency.code} /></h3>
                            <p className="text-xs text-slate-600">Select how to recover or deduct this fleet expense according to commercial agreements.</p>
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <Select
                                    label="Allocation Treatment"
                                    value={allocType}
                                    onChange={e => setAllocType(e.target.value as 'company_cost' | 'customer_recovery' | 'owner_deduction')}
                                    options={[
                                        { value: 'customer_recovery', label: 'Charge / Recover from Customer Agreement' },
                                        { value: 'owner_deduction', label: 'Deduct from Owner Agreement Payout' },
                                        { value: 'company_cost', label: 'Absorb as Company Cost' },
                                    ]}
                                />
                                {allocType === 'customer_recovery' && (
                                    <LookupSelect
                                        label="Target Customer Agreement"
                                        value={allocCustomerAgreement}
                                        onChange={setAllocCustomerAgreement}
                                        search={searchCustomerAgreements}
                                        required
                                        loadOnOpen
                                        minSearchLength={0}
                                    />
                                )}
                                {allocType === 'owner_deduction' && (
                                    <LookupSelect
                                        label="Target Owner Agreement"
                                        value={allocOwnerAgreement}
                                        onChange={setAllocOwnerAgreement}
                                        search={searchOwnerAgreements}
                                        required
                                        loadOnOpen
                                        minSearchLength={0}
                                    />
                                )}
                            </div>
                            <div className="flex gap-2 pt-2">
                                <Button
                                    variant="primary"
                                    loading={actionLoading}
                                    disabled={
                                        (allocType === 'customer_recovery' && !allocCustomerAgreement) ||
                                        (allocType === 'owner_deduction' && !allocOwnerAgreement)
                                    }
                                    onClick={() => {
                                        const allocs = allocType === 'customer_recovery'
                                            ? [{ allocation_type: 'customer_recovery', net_amount: selected.gross_amount, target_customer_agreement_id: allocCustomerAgreement!.id }]
                                            : allocType === 'owner_deduction'
                                                ? [{ allocation_type: 'owner_deduction', net_amount: selected.gross_amount, target_owner_agreement_id: allocOwnerAgreement!.id }]
                                                : [{ allocation_type: 'company_cost', net_amount: selected.gross_amount }];
                                        handleTransition(selected, ExpenseStatus.Allocated, allocs);
                                    }}
                                >
                                    Confirm Allocation
                                </Button>
                                <Button variant="secondary" disabled={actionLoading} onClick={() => setAllocating(false)}>
                                    Cancel
                                </Button>
                            </div>
                        </div>
                    )}
                </section>
            )}
        </div>
    );
}
