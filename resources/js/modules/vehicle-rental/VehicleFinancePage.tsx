import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
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
import { formatBusinessDate, businessDateInputValue } from '@/shared/utils/businessDate';
import type { PaginationMeta } from '@/shared/types/pagination';
import {
    listFinanceAgreements,
    getFinanceAgreement,
    activateFinanceAgreement,
    cancelFinanceAgreement,
    createInstallmentPayable,
    refreshFinanceDueStatuses,
    FinanceStatus,
    FINANCE_PERMISSION,
    type VehicleFinanceAgreement,
    type FinanceInstallment,
} from './vehicleFinanceApi';
import { VehicleFinanceEditor } from './VehicleFinanceEditor';

export default function VehicleFinancePage() {
    const auth = useAuth();
    const canManage = hasPermission(auth, FINANCE_PERMISSION.manage);

    const [rows, setRows] = useState<VehicleFinanceAgreement[]>([]);
    const [meta, setMeta] = useState<PaginationMeta>();
    const [page, setPage] = useState(1);
    const [revision, setRevision] = useState(0);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<ApiError | null>(null);

    const [creating, setCreating] = useState(false);
    const [selected, setSelected] = useState<VehicleFinanceAgreement | null>(null);
    const [actionLoading, setActionLoading] = useState(false);
    const [search, setSearch] = useState('');
    const [query, setQuery] = useState('');

    useEffect(() => {
        const controller = new AbortController();
        const filters = query ? { search: query } : undefined;
        listFinanceAgreements(page, controller.signal, filters)
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
        setLoading(true);
        setRevision(v => v + 1);
    }

    async function handleSelect(agreement: VehicleFinanceAgreement) {
        setActionLoading(true);
        setError(null);
        try {
            const full = await getFinanceAgreement(agreement.id);
            setSelected(full);
        } catch (failure) {
            setError(toApiError(failure));
        } finally {
            setActionLoading(false);
        }
    }

    async function handleActivate(agreement: VehicleFinanceAgreement) {
        setActionLoading(true);
        setError(null);
        try {
            await activateFinanceAgreement(agreement.id, agreement.row_version);
            reload();
        } catch (failure) {
            setError(toApiError(failure));
        } finally {
            setActionLoading(false);
        }
    }

    async function handleCancel(agreement: VehicleFinanceAgreement) {
        setActionLoading(true);
        setError(null);
        try {
            await cancelFinanceAgreement(agreement.id, agreement.row_version, 'Cancelled by operator');
            reload();
        } catch (failure) {
            setError(toApiError(failure));
        } finally {
            setActionLoading(false);
        }
    }

    async function handleCreatePayable(installment: FinanceInstallment) {
        setActionLoading(true);
        setError(null);
        try {
            const today = businessDateInputValue();
            await createInstallmentPayable(installment.id, installment.row_version, today);
            if (selected) {
                const updated = await getFinanceAgreement(selected.id);
                setSelected(updated);
            }
        } catch (failure) {
            setError(toApiError(failure));
        } finally {
            setActionLoading(false);
        }
    }

    async function handleRefreshStatuses() {
        setActionLoading(true);
        setError(null);
        try {
            await refreshFinanceDueStatuses();
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
                title="Vehicle Finance Agreements"
                description="Manage fleet lease and hire-purchase financing schedules, installment payments, and supplier payable invoices."
                actions={
                    <>
                        <Button variant="secondary" onClick={handleRefreshStatuses} disabled={loading || actionLoading}>
                            Refresh Due Statuses
                        </Button>
                        <Button variant="secondary" onClick={reload} disabled={loading || actionLoading || creating}>
                            Reload
                        </Button>
                        {canManage && (
                            <Button onClick={() => { setCreating(true); setSelected(null); }} disabled={loading || actionLoading || creating}>
                                New Finance Agreement
                            </Button>
                        )}
                    </>
                }
            />

            <ErrorAlert error={error} inline />

            {creating && (
                <VehicleFinanceEditor
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
                        placeholder="Search by agreement, supplier, or vehicle…"
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
                <LoadingState label="Loading vehicle finance agreements…" />
            ) : error ? null : (
                <div className="mt-5 overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
                    <table className="w-full text-left text-sm">
                        <caption className="sr-only">Vehicle finance agreements list</caption>
                        <thead>
                            <tr className="border-b border-slate-200 bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-500">
                                {['Agreement #', 'Supplier / Bank', 'Vehicle', 'Maturity', 'Principal', 'Rate', 'Status', 'Actions'].map(label => (
                                    <th className="px-4 py-3" key={label}>{label}</th>
                                ))}
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {rows.map(row => (
                                <tr key={row.id} className="transition-colors hover:bg-blue-50/40">
                                    <td className="px-4 py-3 font-medium text-slate-900">{row.agreement_number}</td>
                                    <td className="px-4 py-3 text-slate-700">{row.supplier.name}</td>
                                    <td className="px-4 py-3 text-slate-600">{row.vehicle.label}</td>
                                    <td className="px-4 py-3 text-slate-600">{formatBusinessDate(row.matures_at)}</td>
                                    <td className="px-4 py-3 font-medium text-slate-900">
                                        <MoneyDisplay value={row.principal_amount} currency={row.currency.code} />
                                    </td>
                                    <td className="px-4 py-3 text-slate-600">{row.annual_interest_rate}%</td>
                                    <td className="px-4 py-3">
                                        <StatusBadge status={row.status} />
                                    </td>
                                    <td className="px-4 py-3">
                                        <Button
                                            variant="secondary"
                                            disabled={actionLoading || creating}
                                            onClick={() => handleSelect(row)}
                                        >
                                            Installments ({row.installment_count})
                                        </Button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                    {rows.length === 0 && <p className="p-5 text-slate-500">No finance agreements recorded.</p>}
                </div>
            )}

            <Pagination meta={meta} disabled={loading || actionLoading} onPageChange={value => { setLoading(true); setPage(value); setSelected(null); }} />

            {selected && (
                <section className="mt-5 space-y-4 rounded-xl border border-slate-200 bg-white p-6 shadow-sm" aria-label="Finance agreement installments">
                    <div className="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 pb-3">
                        <div>
                            <h2 className="text-xl font-semibold text-slate-900">{selected.agreement_number} · {selected.supplier.name} ({selected.vehicle.label})</h2>
                            <p className="text-xs text-slate-500">
                                Principal: <MoneyDisplay value={selected.principal_amount} currency={selected.currency.code} /> · {selected.installment_count} {selected.installment_frequency} installments · Interest: {selected.annual_interest_rate}% ({selected.interest_method})
                            </p>
                        </div>
                        <div className="flex items-center gap-2">
                            <StatusBadge status={selected.status} />
                            {canManage && selected.status === FinanceStatus.Draft && (
                                <>
                                    <Button onClick={() => handleActivate(selected)} loading={actionLoading}>
                                        Activate Agreement
                                    </Button>
                                    <Button variant="danger" onClick={() => handleCancel(selected)} loading={actionLoading}>
                                        Cancel
                                    </Button>
                                </>
                            )}
                            <Button variant="secondary" onClick={() => setSelected(null)}>Close</Button>
                        </div>
                    </div>

                    <h3 className="text-base font-semibold text-slate-900">Amortization Schedule</h3>
                    <div className="overflow-x-auto rounded-lg border border-slate-200">
                        <table className="w-full text-left text-xs">
                            <thead>
                                <tr className="border-b border-slate-200 bg-slate-50 font-semibold uppercase tracking-wider text-slate-500">
                                    {['#', 'Due Date', 'Principal', 'Interest', 'Total Due', 'Paid', 'Balance', 'Status', 'Payable Invoice'].map(l => (
                                        <th className="px-3 py-2" key={l}>{l}</th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {selected.installments?.map(inst => (
                                    <tr key={inst.id} className="transition-colors hover:bg-blue-50/40">
                                        <td className="px-3 py-2 font-medium">{inst.installment_number}</td>
                                        <td className="px-3 py-2 text-slate-600">{formatBusinessDate(inst.due_date)}</td>
                                        <td className="px-3 py-2"><MoneyDisplay value={inst.principal_due} currency={selected.currency.code} /></td>
                                        <td className="px-3 py-2"><MoneyDisplay value={inst.interest_due} currency={selected.currency.code} /></td>
                                        <td className="px-3 py-2 font-semibold text-slate-900"><MoneyDisplay value={inst.total_due} currency={selected.currency.code} /></td>
                                        <td className="px-3 py-2 text-slate-600"><MoneyDisplay value={inst.paid_amount} currency={selected.currency.code} /></td>
                                        <td className="px-3 py-2 font-medium text-slate-900"><MoneyDisplay value={inst.balance_due} currency={selected.currency.code} /></td>
                                        <td className="px-3 py-2"><StatusBadge status={inst.status} /></td>
                                        <td className="px-3 py-2">
                                            {inst.invoice ? (
                                                <Link
                                                    to={`/invoices/${inst.invoice.id}`}
                                                    className="font-medium text-blue-600 hover:text-blue-800 hover:underline"
                                                >
                                                    {inst.invoice.invoice_number} ({inst.invoice.status})
                                                </Link>
                                            ) : canManage && selected.status === FinanceStatus.Active ? (
                                                <Button
                                                    variant="secondary"
                                                    disabled={actionLoading}
                                                    onClick={() => handleCreatePayable(inst)}
                                                >
                                                    Generate Invoice
                                                </Button>
                                            ) : (
                                                <span className="text-slate-400">Not generated</span>
                                            )}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </section>
            )}
        </div>
    );
}
