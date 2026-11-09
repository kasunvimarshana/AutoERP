import { DepositPanel } from './DepositPanel';
import { DEPOSIT_PERMISSION } from './depositApi';
import { BaseRentBillingPanel } from './BaseRentBillingPanel';
import { BILLING_PERMISSION } from './baseRentBillingApi';
import { VehicleUsePanel } from './VehicleUsePanel';
import { BaseRentPreviewPanel } from './BaseRentPreviewPanel';
import { USE_PERMISSION } from './vehicleUse';
import { AgreementHistoryPanel } from './AgreementHistoryPanel';
import { AgreementSuccessorForm } from './AgreementSuccessorForm';
import { AgreementFinancialSummaryPanel } from './AgreementFinancialSummaryPanel';
import { listAgreements, transitionAgreement } from './agreementApi';
import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { useAuth } from '@/modules/auth/AuthProvider';
import { hasPermission } from '@/modules/auth/accessControl';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { Pagination } from '@/shared/components/Pagination';
import { formatBusinessDate } from '@/shared/utils/businessDate';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { Textarea } from '@/shared/components/Textarea';
import { Input } from '@/shared/components/Input';
import { Select } from '@/shared/components/Select';
import type { PaginationMeta } from '@/shared/types/pagination';
import { AgreementEditor } from './AgreementEditor';
import { AgreementTermsGrid } from './AgreementTermsGrid';
import { AgreementAction, AgreementKind, AgreementStatus, DriverMode, RentalBasis, agreementPermissions, type Agreement } from './agreements';

export default function AgreementsPage({ kind }: { kind: AgreementKind }) {
    const auth = useAuth();
    const canBill = hasPermission(auth, BILLING_PERMISSION[kind]);
    const canManage = hasPermission(auth, agreementPermissions[kind].manage);
    const canViewUse = hasPermission(auth, USE_PERMISSION.view);
    const canManageUse = hasPermission(auth, USE_PERMISSION.manage);
    const [rows, setRows] = useState<Agreement[]>([]);
    const [meta, setMeta] = useState<PaginationMeta>();
    const [page, setPage] = useState(1);
    const [revision, setRevision] = useState(0);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<ApiError | null>(null);
    const [editing, setEditing] = useState<Agreement | 'new' | null>(null);
    const [selected, setSelected] = useState<Agreement | null>(null);
    const [action, setAction] = useState<AgreementAction | null>(null);
    const [reason, setReason] = useState('');
    const [saving, setSaving] = useState(false);
    const [activeReviewTab, setActiveReviewTab] = useState<'billing' | 'vehicles' | 'deposits' | 'financials' | 'history'>('billing');
    const [showSuccessor, setShowSuccessor] = useState(false);
    const [search, setSearch] = useState(() => {
        if (typeof window === 'undefined') return '';
        return new URLSearchParams(window.location.search).get('search') ?? '';
    });
    const [query, setQuery] = useState(() => {
        if (typeof window === 'undefined') return '';
        return new URLSearchParams(window.location.search).get('search') ?? '';
    });
    const [statusFilter, setStatusFilter] = useState<AgreementStatus | ''>(() => {
        if (typeof window === 'undefined') return '';
        const param = new URLSearchParams(window.location.search).get('status');
        return Object.values(AgreementStatus).includes(param as AgreementStatus) ? (param as AgreementStatus) : '';
    });
    const [vehicleIdParam, setVehicleIdParam] = useState<string | null>(() => {
        if (typeof window === 'undefined') return null;
        return new URLSearchParams(window.location.search).get('vehicle_id');
    });

    function clearVehicleParam() {
        setVehicleIdParam(null);
        if (typeof window !== 'undefined' && window.location.search.includes('vehicle_id')) {
            const url = new URL(window.location.href);
            url.searchParams.delete('vehicle_id');
            window.history.replaceState({}, '', url.toString());
        }
    }
    useEffect(() => {
        const controller = new AbortController();
        listAgreements(kind, page, controller.signal, query, statusFilter).then(result => {
            if (!controller.signal.aborted) { setRows(result.data); setMeta(result.meta); setError(null); }
        }).catch(failure => { if (!controller.signal.aborted) { setRows([]); setMeta(undefined); setError(toApiError(failure)); } })
            .finally(() => { if (!controller.signal.aborted) setLoading(false); });
        return () => controller.abort();
    }, [kind, page, revision, query, statusFilter]);
    function reload() { setSelected(null); setAction(null); setEditing(null); setShowSuccessor(false); setLoading(true); setRevision(value => value + 1); }
    async function confirm() {
        if (!selected || !action) return;
        setSaving(true); setError(null);
        try { await transitionAgreement(kind, selected, action, reason); reload(); }
        catch (failure) { setError(toApiError(failure)); }
        finally { setSaving(false); }
    }
    return <div>
        <ContentHeader title={kind === AgreementKind.Customer ? 'Customer Rental Agreements' : 'Owner Rental Agreements'} description={kind === AgreementKind.Customer ? 'Record customer commercial terms, vehicle use, billing and deposit context while preserving history.' : 'Record owner/lessor payable terms and supplied vehicle context independently from customer billing.'}
            actions={<><Button variant="secondary" onClick={reload} disabled={loading || saving || editing !== null}>Reload</Button>{canManage && <Button onClick={() => { setEditing('new'); setSelected(null); }} disabled={loading || saving || editing !== null}>New agreement</Button>}</>} />
        <ErrorAlert error={error} inline />
        {vehicleIdParam && (
            <div className="mt-3 flex items-center justify-between rounded-lg border border-blue-200 bg-blue-50/80 p-3 text-sm text-blue-900">
                <span>Vehicle selected for dispatch. Select an active customer agreement to assign this vehicle.</span>
                <Button type="button" variant="ghost" onClick={clearVehicleParam}>Dismiss</Button>
            </div>
        )}
        {editing !== null && <AgreementEditor key={editing === 'new' ? 'new' : editing.id} kind={kind} record={editing === 'new' ? undefined : editing} onSaved={reload} onCancel={() => setEditing(null)} />}
        <form
            onSubmit={event => {
                event.preventDefault();
                setPage(1);
                setQuery(search.trim());
            }}
            className="mt-4 flex flex-wrap items-center gap-2"
        >
            <div className="w-72">
                <Input
                    name="search"
                    placeholder="Search by reference or party…"
                    value={search}
                    onChange={event => setSearch(event.target.value)}
                />
            </div>
            <div className="w-40">
                <Select
                    name="status"
                    options={[
                        { value: AgreementStatus.Draft, label: 'Draft' },
                        { value: AgreementStatus.Active, label: 'Active' },
                        { value: AgreementStatus.Closed, label: 'Closed' },
                    ]}
                    placeholder="All statuses"
                    value={statusFilter}
                    onChange={event => {
                        setStatusFilter(event.target.value as AgreementStatus | '');
                        setPage(1);
                    }}
                />
            </div>
            <Button type="submit" variant="secondary" disabled={loading}>
                Search
            </Button>
            {(query || statusFilter) && (
                <Button
                    type="button"
                    variant="ghost"
                    onClick={() => {
                        setSearch('');
                        setQuery('');
                        setStatusFilter('');
                        setPage(1);
                    }}
                >
                    Clear
                </Button>
            )}
        </form>
        {loading ? <LoadingState label="Loading agreements…" /> : error ? null : <div className="mt-5 overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
            <table className="w-full text-left text-sm"><caption className="sr-only">Rental agreements</caption><thead><tr className="border-b border-slate-200 bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-500">{['Reference', kind === AgreementKind.Customer ? 'Party' : 'Owner / Vehicle', 'Period', 'Basis', 'Status', 'Details'].map(label => <th className="px-4 py-3" key={label}>{label}</th>)}</tr></thead>
                <tbody className="divide-y divide-slate-100">{rows.map(row => <tr key={row.id} className="transition-colors hover:bg-blue-50/40"><td className="px-4 py-3 font-medium text-slate-900">{row.reference}</td><td className="px-4 py-3 text-slate-700"><div className="font-medium">{row.party.name}</div>{row.vehicle && <div className="text-xs text-slate-500 font-mono">{row.vehicle.registration_number || row.vehicle.vehicle_number}</div>}</td><td className="px-4 py-3 text-slate-600">{formatBusinessDate(row.starts_on)} – {formatBusinessDate(row.ends_on, 'Open-ended')}</td><td className="px-4 py-3 text-slate-700">{row.basis === RentalBasis.Daily ? 'Daily' : 'Monthly'}</td><td className="px-4 py-3"><StatusBadge status={row.status} /></td><td className="px-4 py-3"><Button variant="secondary" disabled={saving || editing !== null} onClick={() => { if (selected?.id === row.id) { setSelected(null); } else { setSelected(row); setAction(null); setReason(''); setShowSuccessor(false); setActiveReviewTab(vehicleIdParam && kind === AgreementKind.Customer && row.status === AgreementStatus.Active ? 'vehicles' : 'billing'); } }}>{selected?.id === row.id ? 'Close review' : `Review ${row.reference}`}
</Button></td></tr>)}</tbody>
            </table>{rows.length === 0 && <p className="p-5 text-slate-500">No agreements have been recorded.</p>}
        </div>}
        <Pagination meta={meta} disabled={loading || saving} onPageChange={value => { setLoading(true); setPage(value); setSelected(null); }} />
        {selected && <section className="mt-5 space-y-4 rounded-xl border border-slate-200 bg-white p-5 shadow-sm" aria-label="Agreement review">
            <div className="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 pb-3">
                <div className="flex flex-wrap items-center gap-3">
                    <h2 className="text-xl font-semibold text-slate-900">{selected.reference} · {selected.party.name}</h2>
                    <StatusBadge status={selected.status} />
                </div>
                <div className="flex flex-wrap items-center gap-2">
                    {canManage && !action && !showSuccessor && (
                        <>
                            {selected.status === AgreementStatus.Draft && (
                                <>
                                    <Button variant="secondary" onClick={() => { setEditing(selected); setSelected(null); }}>Edit draft</Button>
                                    <Button onClick={() => setAction(AgreementAction.Activate)}>Activate</Button>
                                    <Button variant="danger" onClick={() => setAction(AgreementAction.Close)}>Close draft</Button>
                                </>
                            )}
                            {selected.status === AgreementStatus.Active && (
                                <>
                                    {selected.successor_agreement ? (
                                        <span className="inline-flex items-center rounded-lg border border-amber-200 bg-amber-50 px-2.5 py-1.5 text-xs font-medium text-amber-800">
                                            Successor in progress ({selected.successor_agreement.status}): {selected.successor_agreement.reference}
                                        </span>
                                    ) : (
                                        <Button variant="secondary" onClick={() => setShowSuccessor(true)}>Create successor</Button>
                                    )}
                                    <Button variant="danger" onClick={() => setAction(AgreementAction.Close)}>Close agreement</Button>
                                </>
                            )}
                        </>
                    )}
                    <Button variant="secondary" onClick={() => setSelected(null)}>Close review</Button>
                </div>
            </div>
            {action && <div className="space-y-3 rounded-lg border border-amber-200 bg-amber-50/60 p-4">
                <p className="text-sm text-slate-700">
                    {action === AgreementAction.Activate
                        ? 'Activation freezes these terms. Confirm that the recorded details match the agreement. This does not reserve the vehicle or create a financial document.'
                        : selected.status === AgreementStatus.Draft
                            ? 'Close this draft agreement without activating it while preserving its history.'
                            : 'Close this agreement while preserving its original terms and history.'}
                </p>
                {action === AgreementAction.Close && <Textarea label="Closure reason" value={reason} onChange={event => setReason(event.target.value)} required disabled={saving} error={error?.fields.reason?.[0]} />}
                <div className="flex flex-wrap gap-2">
                    <Button variant={action === AgreementAction.Close ? 'danger' : 'primary'} onClick={confirm} loading={saving} disabled={action === AgreementAction.Close && !reason.trim()}>Confirm {action === AgreementAction.Activate ? 'activation' : 'closure'}</Button>
                    <Button type="button" variant="secondary" disabled={saving} onClick={() => setAction(null)}>Cancel</Button>
                </div>
            </div>}
            {showSuccessor && selected.status === AgreementStatus.Active && <AgreementSuccessorForm kind={kind} agreement={selected} onSaved={reload} onCancel={() => setShowSuccessor(false)} />}
            <p className="text-sm text-slate-600">{selected.currency.code} · {selected.driver_mode === DriverMode.SelfDrive ? 'Self-drive' : 'With driver'}{selected.vehicle ? ` · ${selected.vehicle.registration_number ?? selected.vehicle.vehicle_number}` : ''}</p>
            <p className="text-xs text-slate-500">Agreement date: {formatBusinessDate(selected.agreed_on)} · Executing date: {formatBusinessDate(selected.executing_on, 'Not recorded')}</p>
            {selected.supersedes_agreement && <p className="text-xs text-blue-600 font-medium">Successor of {selected.supersedes_agreement.reference}</p>}
            {selected.successor_agreement && <p className="text-xs text-amber-700 font-medium">Successor ({selected.successor_agreement.status}): {selected.successor_agreement.reference}</p>}
            <AgreementTermsGrid kind={kind} currency={selected.currency.code} terms={selected.terms} />
            {selected.notes && <p className="text-sm text-slate-700"><span className="font-semibold text-slate-900">Notes:</span> {selected.notes}</p>}

            <div className="flex flex-wrap gap-2 border-b border-slate-200 pt-3">
                <button
                    type="button"
                    onClick={() => setActiveReviewTab('billing')}
                    className={`px-3 py-2 text-xs font-semibold rounded-t-lg transition-colors ${
                        activeReviewTab === 'billing'
                            ? 'bg-blue-50 text-blue-700 border-b-2 border-blue-600'
                            : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50'
                    }`}
                >
                    Billing & Rate Estimates
                </button>
                {kind === AgreementKind.Customer && canViewUse && (
                    <button
                        type="button"
                        onClick={() => setActiveReviewTab('vehicles')}
                        className={`px-3 py-2 text-xs font-semibold rounded-t-lg transition-colors ${
                            activeReviewTab === 'vehicles'
                                ? 'bg-blue-50 text-blue-700 border-b-2 border-blue-600'
                                : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50'
                        }`}
                    >
                        Assigned Vehicles & Handover
                    </button>
                )}
                {kind === AgreementKind.Owner && canViewUse && (
                    <Link
                        to={`/vehicle-rental/vehicle-uses?search=${encodeURIComponent(selected.reference)}`}
                        className="px-3 py-2 text-xs font-semibold rounded-t-lg transition-colors text-slate-600 hover:text-slate-900 hover:bg-slate-50 flex items-center gap-1"
                        title="View vehicle uses utilizing this owner's vehicle in the vehicle-use register"
                    >
                        <span>Vehicle Uses</span>
                        <span aria-hidden="true">&rarr;</span>
                    </Link>
                )}
                {kind === AgreementKind.Customer && hasPermission(auth, DEPOSIT_PERMISSION.view) && (
                    <button
                        type="button"
                        onClick={() => setActiveReviewTab('deposits')}
                        className={`px-3 py-2 text-xs font-semibold rounded-t-lg transition-colors ${
                            activeReviewTab === 'deposits'
                                ? 'bg-blue-50 text-blue-700 border-b-2 border-blue-600'
                                : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50'
                        }`}
                    >
                        Security Deposits
                    </button>
                )}
                <button
                    type="button"
                    onClick={() => setActiveReviewTab('financials')}
                    className={`px-3 py-2 text-xs font-semibold rounded-t-lg transition-colors ${
                        activeReviewTab === 'financials'
                            ? 'bg-blue-50 text-blue-700 border-b-2 border-blue-600'
                            : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50'
                    }`}
                >
                    Financial Position & Notes
                </button>
                <button
                    type="button"
                    onClick={() => setActiveReviewTab('history')}
                    className={`px-3 py-2 text-xs font-semibold rounded-t-lg transition-colors ${
                        activeReviewTab === 'history'
                            ? 'bg-blue-50 text-blue-700 border-b-2 border-blue-600'
                            : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50'
                    }`}
                >
                    Audit History
                </button>
            </div>

            {activeReviewTab === 'billing' && (
                <div className="space-y-4 pt-1">
                    <BaseRentPreviewPanel key={`${kind}-${selected.id}-${selected.row_version}`} kind={kind} agreement={selected} />
                    {canBill && selected.status !== AgreementStatus.Draft && (
                        <BaseRentBillingPanel key={`billing-${kind}-${selected.id}-${selected.row_version}`} kind={kind} agreement={selected} />
                    )}
                </div>
            )}

            {activeReviewTab === 'vehicles' && kind === AgreementKind.Customer && canViewUse && (
                <VehicleUsePanel
                    key={selected.id}
                    agreement={selected}
                    canManage={canManageUse}
                    initialVehicleId={vehicleIdParam ? Number(vehicleIdParam) : undefined}
                    onVehicleAssigned={clearVehicleParam}
                />
            )}

            {activeReviewTab === 'deposits' && kind === AgreementKind.Customer && hasPermission(auth, DEPOSIT_PERMISSION.view) && (
                <DepositPanel key={selected.id} agreement={selected} canCreate={hasPermission(auth, DEPOSIT_PERMISSION.create)} />
            )}

            {activeReviewTab === 'financials' && (
                <AgreementFinancialSummaryPanel
                    key={`financials-${selected.id}-${selected.row_version}`}
                    kind={kind}
                    agreement={selected}
                    canManageAdjustments={canBill}
                />
            )}

            {activeReviewTab === 'history' && (
                <AgreementHistoryPanel key={selected.id} kind={kind} id={selected.id} />
            )}
        </section>}
    </div>;
}
