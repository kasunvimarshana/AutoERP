import { useState } from 'react';
import { Link, useLocation } from 'react-router-dom';
import { hasPermission } from '@/modules/auth/accessControl';
import { useAuth } from '@/modules/auth/AuthProvider';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { useConfirmDialog } from '@/shared/components/ConfirmDialog';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { MoneyDisplay } from '@/shared/components/MoneyDisplay';
import { Pagination } from '@/shared/components/Pagination';
import { Select } from '@/shared/components/Select';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { useApi } from '@/shared/hooks/useApi';
import { useDebounce } from '@/shared/hooks/useDebounce';
import { notifySuccess } from '@/shared/notifications/appToast';
import { RentalReasonDialog } from '../components/RentalActionDialogs';
import { RentalAgreementDialog, RentalRateVersionDialog } from '../components/RentalAgreementDialogs';
import { RentalAssignmentDialog, RentalCustodyDialog, RentalReplacementDialog } from '../components/RentalAssignmentDialogs';
import { RentalCalculationDialog } from '../components/RentalCalculationDialog';
import { RentalRunningChartDialog } from '../components/RentalRunningChartDialog';
import {
    activateRentalAgreement,
    cancelRentalAssignment,
    cancelRentalCalculation,
    closeRentalAgreement,
    finalizeRentalRunningChart,
    listRentalAgreements,
    listRentalAssignments,
    listRentalCalculations,
    listRentalRunningCharts,
    reverseRentalRunningChart,
} from '../vehicleRentalApi';
import { vehicleRentalPermissions } from '../vehicleRentalPermissions';
import type {
    RentalAgreement,
    RentalAgreementKind,
    RentalAssignment,
    RentalCalculation,
    RentalReference,
    RentalRunningChart,
} from '../vehicleRentalTypes';
import { RentalFinancialDocumentsPage } from './RentalFinancialDocumentsPage';
import { RentalReportsPage } from './RentalReportsPage';
import { RentalSettlementHandoffPage } from './RentalSettlementHandoffPage';

const PAGE_SIZE = 25;

function relationLabel(value?: RentalReference | null): string {
    return value?.name || value?.code || '—';
}

function agreementReference(agreement: RentalAgreement): RentalReference {
    const party = agreement.customer ?? agreement.supplier;

    return {
        id: agreement.id,
        code: agreement.agreement_number,
        name: party?.name || party?.code || agreement.agreement_number,
    };
}

function dateLabel(value?: string | null): string {
    if (!value) return '—';
    const parsed = new Date(value);
    return Number.isNaN(parsed.getTime()) ? value : parsed.toLocaleDateString();
}

function dateTimeLabel(value?: string | null): string {
    if (!value) return '—';
    const parsed = new Date(value);
    return Number.isNaN(parsed.getTime()) ? value : parsed.toLocaleString();
}

function periodLabel(start?: string | null, end?: string | null): string {
    return `${dateLabel(start)} → ${dateLabel(end)}`;
}

function OverviewPage() {
    const auth = useAuth();
    const workspaces = [
        { label: 'Owner / supplier agreements', description: 'Define owner payable terms and select supplied vehicles.', to: '/vehicle-rental/owner-agreements', permission: vehicleRentalPermissions.agreementsView },
        { label: 'Customer agreements', description: 'Define customer billing terms and select the rental vehicle.', to: '/vehicle-rental/customer-agreements', permission: vehicleRentalPermissions.agreementsView },
        { label: 'Daily running charts', description: 'Record the physical usage shared by customer billing and owner settlement.', to: '/vehicle-rental/running-charts', permission: vehicleRentalPermissions.runningChartsView },
        { label: 'Customer invoices', description: 'Prepare billing periods and create posted customer invoices.', to: '/vehicle-rental/customer-invoices', permission: vehicleRentalPermissions.calculationsView },
        { label: 'Owner settlements', description: 'Prepare owner periods and create Owner Payable Vouchers.', to: '/vehicle-rental/owner-settlements', permission: vehicleRentalPermissions.calculationsView },
        { label: 'Customer receipts', description: 'Record and allocate receipts through the shared Payment module.', to: '/vehicle-rental/customer-receipts', permission: vehicleRentalPermissions.calculationsView },
        { label: 'Owner payments', description: 'Record and allocate owner payments through the shared Payment module.', to: '/vehicle-rental/owner-payments', permission: vehicleRentalPermissions.calculationsView },
        { label: 'Reports', description: 'Review usage, revenue, owner costs, outstanding balances, and gross margin.', to: '/vehicle-rental/reports', permission: vehicleRentalPermissions.calculationsView },
    ].filter((workspace) => hasPermission(auth, workspace.permission));

    return (
        <>
            <ContentHeader
                title="Vehicle rental"
                description="Owner agreement → Customer agreement → Select vehicle → Daily running chart → Invoice / Owner settlement → Receipt / Payment → Reports."
            />
            <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                {workspaces.map((workspace) => (
                    <Link key={workspace.to} to={workspace.to} className="rounded-xl border border-slate-200 bg-white p-5 transition hover:border-sky-300 hover:bg-sky-50">
                        <h2 className="font-semibold text-slate-900">{workspace.label}</h2>
                        <p className="mt-2 text-sm text-slate-600">{workspace.description}</p>
                    </Link>
                ))}
            </div>
            {workspaces.length === 0 && <p className="rounded-xl border border-amber-200 bg-amber-50 p-4 text-amber-900">Vehicle Rental is enabled for this tenant, but your role does not include a Vehicle Rental view permission.</p>}
        </>
    );
}

function AgreementsPage({ fixedKind }: { fixedKind?: RentalAgreementKind }) {
    const auth = useAuth();
    const canManage = hasPermission(auth, vehicleRentalPermissions.agreementsManage);
    const canAssign = hasPermission(auth, vehicleRentalPermissions.assignmentsManage);
    const canAct = canManage || canAssign;
    const { confirm, confirmDialog } = useConfirmDialog();
    const [search, setSearch] = useState('');
    const [kind, setKind] = useState(fixedKind ?? '');
    const [status, setStatus] = useState('');
    const [page, setPage] = useState(1);
    const [formOpen, setFormOpen] = useState(false);
    const [editing, setEditing] = useState<RentalAgreement | null>(null);
    const [rateAgreement, setRateAgreement] = useState<RentalAgreement | null>(null);
    const [assignmentAgreement, setAssignmentAgreement] = useState<RentalAgreement | null>(null);
    const [busyId, setBusyId] = useState<number | null>(null);
    const [actionError, setActionError] = useState<ApiError | null>(null);
    const debouncedSearch = useDebounce(search);
    const effectiveKind = fixedKind ?? kind;
    const result = useApi((signal) => listRentalAgreements({
        search: debouncedSearch || undefined,
        kind: effectiveKind || undefined,
        agreement_status: status || undefined,
        page,
        per_page: PAGE_SIZE,
    }, signal), [debouncedSearch, effectiveKind, status, page]);

    const activate = async (agreement: RentalAgreement) => {
        if (!await confirm({ title: 'Activate rental agreement?', message: `Activate ${agreement.agreement_number}? Draft commercial terms will become operational.`, confirmLabel: 'Activate', danger: false })) return;
        await runAgreementAction(agreement, 'activate');
    };
    const close = async (agreement: RentalAgreement) => {
        if (!await confirm({ title: 'Close rental agreement?', message: `Close ${agreement.agreement_number}? All planned and active assignments must already be closed or cancelled.`, confirmLabel: 'Close agreement' })) return;
        await runAgreementAction(agreement, 'close');
    };
    const runAgreementAction = async (agreement: RentalAgreement, action: 'activate' | 'close') => {
        setBusyId(agreement.id);
        setActionError(null);
        try {
            if (action === 'activate') await activateRentalAgreement(agreement.id, agreement.row_version);
            else await closeRentalAgreement(agreement.id, agreement.row_version);
            notifySuccess(action === 'activate' ? 'Rental agreement activated successfully.' : 'Rental agreement closed successfully.');
            result.reload();
        } catch (error) {
            setActionError(toApiError(error));
        } finally {
            setBusyId(null);
        }
    };

    const sideTitle = fixedKind === 'owner' ? 'Owner / supplier agreements' : fixedKind === 'customer' ? 'Customer agreements' : 'Vehicle rental agreements';
    const sideDescription = fixedKind === 'owner'
        ? 'Owner payable terms and the vehicles supplied under each agreement.'
        : fixedKind === 'customer'
            ? 'Customer billing terms and the vehicles selected for each agreement.'
            : 'Customer and owner agreements remain independent while sharing physical running-chart evidence.';
    const partyHeader = fixedKind === 'owner' ? 'Owner / supplier' : fixedKind === 'customer' ? 'Customer' : 'Customer / owner';

    const columns: DataColumn<RentalAgreement>[] = [
        { key: 'number', header: 'Agreement', render: (row) => <span className="font-semibold">{row.agreement_number}</span> },
        ...(!fixedKind ? [{ key: 'kind', header: 'Side', render: (row: RentalAgreement) => row.kind } as DataColumn<RentalAgreement>] : []),
        { key: 'party', header: partyHeader, render: (row) => relationLabel(row.customer ?? row.supplier) },
        { key: 'period', header: 'Period', render: (row) => periodLabel(row.starts_on, row.ends_on) },
        { key: 'basis', header: 'Basis', render: (row) => row.billing_basis },
        { key: 'status', header: 'Status', render: (row) => <StatusBadge status={row.status} /> },
        ...(canAct ? [{
            key: 'actions',
            header: 'Actions',
            render: (row: RentalAgreement) => (
                <div className="flex flex-wrap gap-2">
                    {canManage && row.status === 'draft' && <Button variant="secondary" className="min-h-9 px-3 py-1.5" onClick={() => { setEditing(row); setFormOpen(true); }}>Edit</Button>}
                    {canManage && row.status === 'draft' && <Button className="min-h-9 px-3 py-1.5" loading={busyId === row.id} onClick={() => void activate(row)}>Activate</Button>}
                    {canAssign && row.status === 'active' && <Button className="min-h-9 px-3 py-1.5" onClick={() => setAssignmentAgreement(row)}>Select vehicle</Button>}
                    {canManage && row.status === 'active' && <Button variant="secondary" className="min-h-9 px-3 py-1.5" onClick={() => setRateAgreement(row)}>New rates</Button>}
                    {canManage && row.status === 'active' && <Button variant="danger" className="min-h-9 px-3 py-1.5" loading={busyId === row.id} onClick={() => void close(row)}>Close</Button>}
                </div>
            ),
        } as DataColumn<RentalAgreement>] : []),
    ];

    return (
        <>
            <ContentHeader title={sideTitle} description={sideDescription} actions={canManage ? <Button onClick={() => { setEditing(null); setFormOpen(true); }}>New agreement</Button> : undefined} />
            <div className={`mb-4 grid gap-4 ${fixedKind ? 'md:grid-cols-2' : 'md:grid-cols-3'}`}>
                <Input label="Search" type="search" placeholder="Agreement or party" value={search} onChange={(event) => { setSearch(event.target.value); setPage(1); }} />
                {!fixedKind && <Select label="Side" value={kind} placeholder="All sides" options={[{ value: 'customer', label: 'Customer' }, { value: 'owner', label: 'Owner / supplier' }]} onChange={(event) => { setKind(event.target.value); setPage(1); }} />}
                <Select label="Status" value={status} placeholder="All statuses" options={[{ value: 'draft', label: 'Draft' }, { value: 'active', label: 'Active' }, { value: 'closed', label: 'Closed' }]} onChange={(event) => { setStatus(event.target.value); setPage(1); }} />
            </div>
            <ErrorAlert error={actionError ?? result.error} inline />
            {result.loading ? <LoadingState /> : <DataTable rows={result.data?.data ?? []} columns={columns} rowKey={(row) => row.id} />}
            <Pagination meta={result.data?.meta} onPageChange={setPage} />
            <RentalAgreementDialog open={formOpen} agreement={editing} kind={fixedKind} onClose={() => { setFormOpen(false); setEditing(null); }} onSaved={() => result.reload()} />
            <RentalRateVersionDialog open={Boolean(rateAgreement)} agreement={rateAgreement} onClose={() => setRateAgreement(null)} onSaved={() => result.reload()} />
            <RentalAssignmentDialog
                open={Boolean(assignmentAgreement)}
                agreement={assignmentAgreement ? agreementReference(assignmentAgreement) : null}
                side={assignmentAgreement?.kind === 'owner' ? 'owner_supply' : 'customer_use'}
                lockAgreement
                onClose={() => setAssignmentAgreement(null)}
                onSaved={() => setAssignmentAgreement(null)}
            />
            {confirmDialog}
        </>
    );
}

function AssignmentsPage() {
    const auth = useAuth();
    const canManage = hasPermission(auth, vehicleRentalPermissions.assignmentsManage);
    const { confirm, confirmDialog } = useConfirmDialog();
    const [search, setSearch] = useState('');
    const [side, setSide] = useState('');
    const [status, setStatus] = useState('');
    const [page, setPage] = useState(1);
    const [createOpen, setCreateOpen] = useState(false);
    const [custody, setCustody] = useState<{ assignment: RentalAssignment; eventType: 'handover' | 'return' } | null>(null);
    const [replacement, setReplacement] = useState<RentalAssignment | null>(null);
    const [busyId, setBusyId] = useState<number | null>(null);
    const [actionError, setActionError] = useState<ApiError | null>(null);
    const debouncedSearch = useDebounce(search);
    const result = useApi((signal) => listRentalAssignments({ search: debouncedSearch || undefined, assignment_side: side || undefined, assignment_status: status || undefined, page, per_page: PAGE_SIZE }, signal), [debouncedSearch, side, status, page]);

    const cancel = async (assignment: RentalAssignment) => {
        if (!await confirm({ title: 'Cancel planned assignment?', message: `Cancel the planned assignment for ${relationLabel(assignment.vehicle)}?`, confirmLabel: 'Cancel assignment' })) return;
        setBusyId(assignment.id);
        setActionError(null);
        try {
            await cancelRentalAssignment(assignment.id, assignment.row_version);
            notifySuccess('Rental assignment cancelled successfully.');
            result.reload();
        } catch (error) {
            setActionError(toApiError(error));
        } finally {
            setBusyId(null);
        }
    };

    const columns: DataColumn<RentalAssignment>[] = [
        { key: 'side', header: 'Side', render: (row) => row.side },
        { key: 'agreement', header: 'Agreement', render: (row) => relationLabel(row.agreement) },
        { key: 'party', header: 'Customer / owner', render: (row) => relationLabel(row.agreement?.party) },
        { key: 'vehicle', header: 'Vehicle', render: (row) => relationLabel(row.vehicle) },
        { key: 'driver', header: 'Driver', render: (row) => row.self_drive ? 'Self-drive' : relationLabel(row.driver) },
        { key: 'period', header: 'Effective period', render: (row) => `${dateTimeLabel(row.starts_at)} → ${dateTimeLabel(row.ends_at)}` },
        { key: 'status', header: 'Status', render: (row) => <StatusBadge status={row.status} /> },
        ...(canManage ? [{
            key: 'actions',
            header: 'Actions',
            render: (row: RentalAssignment) => (
                <div className="flex flex-wrap gap-2">
                    {row.status === 'planned' && <Button className="min-h-9 px-3 py-1.5" onClick={() => setCustody({ assignment: row, eventType: 'handover' })}>Handover</Button>}
                    {row.status === 'planned' && <Button variant="danger" className="min-h-9 px-3 py-1.5" loading={busyId === row.id} onClick={() => void cancel(row)}>Cancel</Button>}
                    {row.status === 'active' && <Button className="min-h-9 px-3 py-1.5" onClick={() => setCustody({ assignment: row, eventType: 'return' })}>Return</Button>}
                    {row.status === 'active' && <Button variant="secondary" className="min-h-9 px-3 py-1.5" onClick={() => setReplacement(row)}>Replace</Button>}
                </div>
            ),
        } as DataColumn<RentalAssignment>] : []),
    ];

    return (
        <>
            <ContentHeader title="Vehicle handover, return, and replacement" description="Operational assignment history. Normal vehicle selection starts from an active agreement." actions={canManage ? <Button onClick={() => setCreateOpen(true)}>New assignment</Button> : undefined} />
            <div className="mb-4 grid gap-4 md:grid-cols-3">
                <Input label="Search" type="search" placeholder="Agreement or vehicle" value={search} onChange={(event) => { setSearch(event.target.value); setPage(1); }} />
                <Select label="Side" value={side} placeholder="All sides" options={[{ value: 'customer_use', label: 'Customer use' }, { value: 'owner_supply', label: 'Owner supply' }]} onChange={(event) => { setSide(event.target.value); setPage(1); }} />
                <Select label="Status" value={status} placeholder="All statuses" options={[{ value: 'planned', label: 'Planned' }, { value: 'active', label: 'Active' }, { value: 'returned', label: 'Returned' }, { value: 'replaced', label: 'Replaced' }, { value: 'cancelled', label: 'Cancelled' }]} onChange={(event) => { setStatus(event.target.value); setPage(1); }} />
            </div>
            <ErrorAlert error={actionError ?? result.error} inline />
            {result.loading ? <LoadingState /> : <DataTable rows={result.data?.data ?? []} columns={columns} rowKey={(row) => row.id} />}
            <Pagination meta={result.data?.meta} onPageChange={setPage} />
            <RentalAssignmentDialog open={createOpen} onClose={() => setCreateOpen(false)} onSaved={() => result.reload()} />
            <RentalCustodyDialog open={Boolean(custody)} assignment={custody?.assignment ?? null} eventType={custody?.eventType ?? 'handover'} onClose={() => setCustody(null)} onSaved={() => result.reload()} />
            <RentalReplacementDialog open={Boolean(replacement)} assignment={replacement} onClose={() => setReplacement(null)} onSaved={() => result.reload()} />
            {confirmDialog}
        </>
    );
}

function RunningChartsPage() {
    const auth = useAuth();
    const canManage = hasPermission(auth, vehicleRentalPermissions.runningChartsManage);
    const { confirm, confirmDialog } = useConfirmDialog();
    const [status, setStatus] = useState('');
    const [dateFrom, setDateFrom] = useState('');
    const [dateTo, setDateTo] = useState('');
    const [page, setPage] = useState(1);
    const [formOpen, setFormOpen] = useState(false);
    const [editing, setEditing] = useState<RentalRunningChart | null>(null);
    const [reversing, setReversing] = useState<RentalRunningChart | null>(null);
    const [busyId, setBusyId] = useState<number | null>(null);
    const [actionError, setActionError] = useState<ApiError | null>(null);
    const result = useApi((signal) => listRentalRunningCharts({ running_chart_status: status || undefined, date_from: dateFrom || undefined, date_to: dateTo || undefined, page, per_page: PAGE_SIZE }, signal), [status, dateFrom, dateTo, page]);

    const finalize = async (chart: RentalRunningChart) => {
        if (!await confirm({ title: 'Finalize running chart?', message: `Finalize ${chart.chart_number}? Finalized operational evidence cannot be edited.`, confirmLabel: 'Finalize', danger: false })) return;
        setBusyId(chart.id);
        setActionError(null);
        try {
            await finalizeRentalRunningChart(chart.id, chart.row_version);
            notifySuccess('Running chart finalized successfully.');
            result.reload();
        } catch (error) {
            setActionError(toApiError(error));
        } finally {
            setBusyId(null);
        }
    };

    const columns: DataColumn<RentalRunningChart>[] = [
        { key: 'chart', header: 'Running chart', render: (row) => <span className="font-semibold">{row.chart_number}</span> },
        { key: 'date', header: 'Date', render: (row) => dateLabel(row.operational_date) },
        { key: 'vehicle', header: 'Vehicle', render: (row) => relationLabel(row.assignment?.vehicle) },
        { key: 'agreement', header: 'Customer agreement', render: (row) => relationLabel(row.assignment?.agreement) },
        { key: 'distance', header: 'Commercial KM', render: (row) => row.commercial_km },
        { key: 'status', header: 'Status', render: (row) => <StatusBadge status={row.status} /> },
        ...(canManage ? [{
            key: 'actions',
            header: 'Actions',
            render: (row: RentalRunningChart) => (
                <div className="flex flex-wrap gap-2">
                    {row.status === 'draft' && <Button variant="secondary" className="min-h-9 px-3 py-1.5" onClick={() => { setEditing(row); setFormOpen(true); }}>Edit</Button>}
                    {row.status === 'draft' && <Button className="min-h-9 px-3 py-1.5" loading={busyId === row.id} onClick={() => void finalize(row)}>Finalize</Button>}
                    {row.status === 'finalized' && <Button variant="danger" className="min-h-9 px-3 py-1.5" onClick={() => setReversing(row)}>Reverse</Button>}
                </div>
            ),
        } as DataColumn<RentalRunningChart>] : []),
    ];

    return (
        <>
            <ContentHeader title="Daily running charts" description="Physical usage evidence used independently by customer billing and owner settlement." actions={canManage ? <Button onClick={() => { setEditing(null); setFormOpen(true); }}>New running chart</Button> : undefined} />
            <div className="mb-4 grid gap-4 md:grid-cols-3">
                <Select label="Status" value={status} placeholder="All statuses" options={[{ value: 'draft', label: 'Draft' }, { value: 'finalized', label: 'Finalized' }, { value: 'reversed', label: 'Reversed' }]} onChange={(event) => { setStatus(event.target.value); setPage(1); }} />
                <Input label="From" type="date" value={dateFrom} onChange={(event) => { setDateFrom(event.target.value); setPage(1); }} />
                <Input label="To" type="date" min={dateFrom || undefined} value={dateTo} onChange={(event) => { setDateTo(event.target.value); setPage(1); }} />
            </div>
            <ErrorAlert error={actionError ?? result.error} inline />
            {result.loading ? <LoadingState /> : <DataTable rows={result.data?.data ?? []} columns={columns} rowKey={(row) => row.id} />}
            <Pagination meta={result.data?.meta} onPageChange={setPage} />
            <RentalRunningChartDialog open={formOpen} chart={editing} onClose={() => { setFormOpen(false); setEditing(null); }} onSaved={() => result.reload()} />
            <RentalReasonDialog
                open={Boolean(reversing)}
                title={`Reverse ${reversing?.chart_number ?? 'running chart'}`}
                message="Active customer and owner periods consuming this chart must be cancelled first. A corrected draft can then be created."
                confirmLabel="Reverse chart"
                onClose={() => setReversing(null)}
                onConfirm={async (reason) => {
                    if (!reversing) return;
                    await reverseRentalRunningChart(reversing.id, reversing.row_version, reason);
                    notifySuccess('Running chart reversed successfully.');
                    result.reload();
                }}
            />
            {confirmDialog}
        </>
    );
}

function CalculationsPage() {
    const auth = useAuth();
    const canManage = hasPermission(auth, vehicleRentalPermissions.calculationsManage);
    const [side, setSide] = useState('');
    const [status, setStatus] = useState('');
    const [page, setPage] = useState(1);
    const [createOpen, setCreateOpen] = useState(false);
    const [cancelling, setCancelling] = useState<RentalCalculation | null>(null);
    const result = useApi((signal) => listRentalCalculations({ calculation_side: side || undefined, calculation_status: status || undefined, page, per_page: PAGE_SIZE }, signal), [side, status, page]);

    const columns: DataColumn<RentalCalculation>[] = [
        { key: 'number', header: 'Calculation', render: (row) => <span className="font-semibold">{row.calculation_number}</span> },
        { key: 'side', header: 'Side', render: (row) => row.side },
        { key: 'agreement', header: 'Agreement', render: (row) => relationLabel(row.agreement) },
        { key: 'period', header: 'Period', render: (row) => periodLabel(row.period_start, row.period_end) },
        { key: 'charts', header: 'Charts', render: (row) => row.chart_count },
        { key: 'excess-km', header: 'Excess KM', render: (row) => row.excess_km },
        { key: 'subtotal', header: 'Commercial subtotal', render: (row) => <MoneyDisplay value={row.subtotal_amount} currency={row.currency?.code ?? undefined} /> },
        { key: 'status', header: 'Status', render: (row) => <StatusBadge status={row.status} /> },
        ...(canManage ? [{
            key: 'actions',
            header: 'Actions',
            render: (row: RentalCalculation) => row.status === 'calculated' && !row.financial_document
                ? <Button variant="danger" className="min-h-9 px-3 py-1.5" onClick={() => setCancelling(row)}>Cancel</Button>
                : null,
        } as DataColumn<RentalCalculation>] : []),
    ];

    return (
        <>
            <ContentHeader title="Rental calculation audit" description="Technical customer and owner commercial snapshots. Normal billing and settlement actions are available in their business workspaces." actions={canManage ? <Button onClick={() => setCreateOpen(true)}>New calculation</Button> : undefined} />
            <div className="mb-4 grid gap-4 md:grid-cols-2">
                <Select label="Side" value={side} placeholder="All sides" options={[{ value: 'customer', label: 'Customer' }, { value: 'owner', label: 'Owner' }]} onChange={(event) => { setSide(event.target.value); setPage(1); }} />
                <Select label="Status" value={status} placeholder="All statuses" options={[{ value: 'calculated', label: 'Calculated' }, { value: 'cancelled', label: 'Cancelled' }]} onChange={(event) => { setStatus(event.target.value); setPage(1); }} />
            </div>
            <ErrorAlert error={result.error} inline />
            {result.loading ? <LoadingState /> : <DataTable rows={result.data?.data ?? []} columns={columns} rowKey={(row) => row.id} />}
            <Pagination meta={result.data?.meta} onPageChange={setPage} />
            <RentalCalculationDialog open={createOpen} onClose={() => setCreateOpen(false)} onSaved={() => result.reload()} />
            <RentalReasonDialog
                open={Boolean(cancelling)}
                title={`Cancel ${cancelling?.calculation_number ?? 'calculation'}`}
                message="Cancelling releases the active source locks for this side. It does not alter the independent calculation on the other side."
                confirmLabel="Cancel calculation"
                onClose={() => setCancelling(null)}
                onConfirm={async (reason) => {
                    if (!cancelling) return;
                    await cancelRentalCalculation(cancelling.id, cancelling.row_version, reason);
                    notifySuccess('Rental calculation cancelled successfully.');
                    result.reload();
                }}
            />
        </>
    );
}

export default function VehicleRentalWorkspacePage() {
    const { pathname } = useLocation();
    const section = pathname.split('/').filter(Boolean)[1] ?? 'overview';
    if (section === 'owner-agreements') return <AgreementsPage fixedKind="owner" />;
    if (section === 'customer-agreements') return <AgreementsPage fixedKind="customer" />;
    if (section === 'agreements') return <AgreementsPage />;
    if (section === 'assignments') return <AssignmentsPage />;
    if (section === 'running-charts') return <RunningChartsPage />;
    if (section === 'customer-invoices') return <RentalFinancialDocumentsPage side="customer" />;
    if (section === 'owner-settlements') return <RentalFinancialDocumentsPage side="owner" />;
    if (section === 'customer-receipts') return <RentalSettlementHandoffPage side="customer" />;
    if (section === 'owner-payments') return <RentalSettlementHandoffPage side="owner" />;
    if (section === 'reports') return <RentalReportsPage />;
    if (section === 'calculations') return <CalculationsPage />;
    return <OverviewPage />;
}
