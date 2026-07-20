import { useState } from 'react';
import { Link, useLocation } from 'react-router-dom';
import { hasPermission } from '@/modules/auth/accessControl';
import { useAuth } from '@/modules/auth/AuthProvider';
import { ContentHeader } from '@/shared/components/ContentHeader';
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
import {
    listRentalAgreements,
    listRentalAssignments,
    listRentalCalculations,
    listRentalRunningCharts,
} from '../vehicleRentalApi';
import { vehicleRentalPermissions } from '../vehicleRentalPermissions';
import type {
    RentalAgreement,
    RentalAssignment,
    RentalCalculation,
    RentalReference,
    RentalRunningChart,
} from '../vehicleRentalTypes';

const pageSize = 25;
const emptyOption = { value: '', label: 'All' };

function relationLabel(value?: RentalReference | null): string {
    return value?.name || value?.code || '—';
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
        {
            label: 'Agreements',
            description: 'Customer and owner commercial agreements with effective rate versions.',
            to: '/vehicle-rental/agreements',
            permission: vehicleRentalPermissions.agreementsView,
        },
        {
            label: 'Vehicle assignments',
            description: 'Customer-use and owner-supply vehicle timelines, custody, return, and replacement.',
            to: '/vehicle-rental/assignments',
            permission: vehicleRentalPermissions.assignmentsView,
        },
        {
            label: 'Running charts',
            description: 'Finalized physical usage evidence, odometer continuity, driver facts, and reversals.',
            to: '/vehicle-rental/running-charts',
            permission: vehicleRentalPermissions.runningChartsView,
        },
        {
            label: 'Calculations',
            description: 'Independent customer and owner commercial snapshots from finalized running charts.',
            to: '/vehicle-rental/calculations',
            permission: vehicleRentalPermissions.calculationsView,
        },
    ].filter((workspace) => hasPermission(auth, workspace.permission));

    return (
        <>
            <ContentHeader
                title="Vehicle rental"
                description="Operational rental agreements, effective vehicle assignments, daily running charts, and independent customer and owner calculations."
            />
            <div className="grid gap-4 md:grid-cols-2">
                {workspaces.map((workspace) => (
                    <Link
                        key={workspace.to}
                        to={workspace.to}
                        className="rounded-xl border border-slate-200 bg-white p-5 transition hover:border-sky-300 hover:bg-sky-50"
                    >
                        <h2 className="font-semibold text-slate-900">{workspace.label}</h2>
                        <p className="mt-2 text-sm text-slate-600">{workspace.description}</p>
                    </Link>
                ))}
            </div>
            {workspaces.length === 0 && (
                <p className="rounded-xl border border-amber-200 bg-amber-50 p-4 text-amber-900">
                    Vehicle Rental is enabled for this tenant, but your role does not include a Vehicle Rental view permission.
                </p>
            )}
        </>
    );
}

function AgreementsPage() {
    const [search, setSearch] = useState('');
    const [kind, setKind] = useState('');
    const [status, setStatus] = useState('');
    const [page, setPage] = useState(1);
    const debouncedSearch = useDebounce(search);
    const result = useApi((signal) => listRentalAgreements({
        search: debouncedSearch || undefined,
        kind: kind || undefined,
        agreement_status: status || undefined,
        page,
        per_page: pageSize,
    }, signal), [debouncedSearch, kind, status, page]);

    const columns: DataColumn<RentalAgreement>[] = [
        { key: 'number', header: 'Agreement', render: (row) => <span className="font-semibold">{row.agreement_number}</span> },
        { key: 'kind', header: 'Side', render: (row) => row.kind },
        { key: 'party', header: 'Customer / owner', render: (row) => relationLabel(row.customer ?? row.supplier) },
        { key: 'period', header: 'Period', render: (row) => periodLabel(row.starts_on, row.ends_on) },
        { key: 'basis', header: 'Basis', render: (row) => row.billing_basis || '—' },
        { key: 'included-km', header: 'Included KM', render: (row) => row.included_km },
        { key: 'status', header: 'Status', render: (row) => <StatusBadge status={row.status} /> },
    ];

    return (
        <>
            <ContentHeader title="Vehicle rental agreements" description="Customer and owner agreements remain independent while sharing the same operational vehicle evidence." />
            <div className="mb-4 grid gap-4 md:grid-cols-3">
                <Input label="Search" type="search" placeholder="Agreement, customer, or owner" value={search} onChange={(event) => { setSearch(event.target.value); setPage(1); }} />
                <Select label="Side" value={kind} options={[emptyOption, { value: 'customer', label: 'Customer' }, { value: 'owner', label: 'Owner' }]} onChange={(event) => { setKind(event.target.value); setPage(1); }} />
                <Select label="Status" value={status} options={[emptyOption, { value: 'draft', label: 'Draft' }, { value: 'active', label: 'Active' }, { value: 'closed', label: 'Closed' }]} onChange={(event) => { setStatus(event.target.value); setPage(1); }} />
            </div>
            <ErrorAlert error={result.error} />
            {result.loading ? <LoadingState /> : <DataTable rows={result.data?.data ?? []} columns={columns} rowKey={(row) => row.id} />}
            <Pagination meta={result.data?.meta} onPageChange={setPage} />
        </>
    );
}

function AssignmentsPage() {
    const [side, setSide] = useState('');
    const [page, setPage] = useState(1);
    const result = useApi((signal) => listRentalAssignments({
        assignment_side: side || undefined,
        page,
        per_page: pageSize,
    }, signal), [side, page]);

    const columns: DataColumn<RentalAssignment>[] = [
        { key: 'side', header: 'Side', render: (row) => row.side },
        { key: 'agreement', header: 'Agreement', render: (row) => relationLabel(row.agreement) },
        { key: 'party', header: 'Customer / owner', render: (row) => relationLabel(row.agreement?.party) },
        { key: 'vehicle', header: 'Vehicle', render: (row) => relationLabel(row.vehicle) },
        { key: 'driver', header: 'Driver', render: (row) => row.self_drive ? 'Self-drive' : relationLabel(row.driver) },
        { key: 'period', header: 'Effective period', render: (row) => `${dateTimeLabel(row.starts_at)} → ${dateTimeLabel(row.ends_at)}` },
        { key: 'status', header: 'Status', render: (row) => <StatusBadge status={row.status} /> },
    ];

    return (
        <>
            <ContentHeader title="Vehicle rental assignments" description="Effective-dated customer-use and owner-supply vehicle timelines with custody and replacement history." />
            <div className="mb-4 max-w-sm">
                <Select label="Side" value={side} options={[emptyOption, { value: 'customer_use', label: 'Customer use' }, { value: 'owner_supply', label: 'Owner supply' }]} onChange={(event) => { setSide(event.target.value); setPage(1); }} />
            </div>
            <ErrorAlert error={result.error} />
            {result.loading ? <LoadingState /> : <DataTable rows={result.data?.data ?? []} columns={columns} rowKey={(row) => row.id} />}
            <Pagination meta={result.data?.meta} onPageChange={setPage} />
        </>
    );
}

function RunningChartsPage() {
    const [status, setStatus] = useState('');
    const [dateFrom, setDateFrom] = useState('');
    const [dateTo, setDateTo] = useState('');
    const [page, setPage] = useState(1);
    const result = useApi((signal) => listRentalRunningCharts({
        running_chart_status: status || undefined,
        date_from: dateFrom || undefined,
        date_to: dateTo || undefined,
        page,
        per_page: pageSize,
    }, signal), [status, dateFrom, dateTo, page]);

    const columns: DataColumn<RentalRunningChart>[] = [
        { key: 'chart', header: 'Running chart', render: (row) => <span className="font-semibold">{row.chart_number}</span> },
        { key: 'date', header: 'Date', render: (row) => dateLabel(row.operational_date) },
        { key: 'vehicle', header: 'Vehicle', render: (row) => relationLabel(row.assignment?.vehicle) },
        { key: 'agreement', header: 'Customer agreement', render: (row) => relationLabel(row.assignment?.agreement) },
        { key: 'distance', header: 'Commercial KM', render: (row) => row.commercial_km },
        { key: 'ac', header: 'AC mode', render: (row) => row.ac_mode || '—' },
        { key: 'status', header: 'Status', render: (row) => <StatusBadge status={row.status} /> },
    ];

    return (
        <>
            <ContentHeader title="Daily running charts" description="Physical usage evidence used independently by customer billing and owner settlement calculations." />
            <div className="mb-4 grid gap-4 md:grid-cols-3">
                <Select label="Status" value={status} options={[emptyOption, { value: 'draft', label: 'Draft' }, { value: 'finalized', label: 'Finalized' }, { value: 'reversed', label: 'Reversed' }]} onChange={(event) => { setStatus(event.target.value); setPage(1); }} />
                <Input label="From" type="date" value={dateFrom} onChange={(event) => { setDateFrom(event.target.value); setPage(1); }} />
                <Input label="To" type="date" value={dateTo} onChange={(event) => { setDateTo(event.target.value); setPage(1); }} />
            </div>
            <ErrorAlert error={result.error} />
            {result.loading ? <LoadingState /> : <DataTable rows={result.data?.data ?? []} columns={columns} rowKey={(row) => row.id} />}
            <Pagination meta={result.data?.meta} onPageChange={setPage} />
        </>
    );
}

function CalculationsPage() {
    const [side, setSide] = useState('');
    const [status, setStatus] = useState('');
    const [page, setPage] = useState(1);
    const result = useApi((signal) => listRentalCalculations({
        calculation_side: side || undefined,
        calculation_status: status || undefined,
        page,
        per_page: pageSize,
    }, signal), [side, status, page]);

    const columns: DataColumn<RentalCalculation>[] = [
        { key: 'number', header: 'Calculation', render: (row) => <span className="font-semibold">{row.calculation_number}</span> },
        { key: 'side', header: 'Side', render: (row) => row.side },
        { key: 'agreement', header: 'Agreement', render: (row) => relationLabel(row.agreement) },
        { key: 'period', header: 'Period', render: (row) => periodLabel(row.period_start, row.period_end) },
        { key: 'charts', header: 'Charts', render: (row) => row.chart_count },
        { key: 'excess-km', header: 'Excess KM', render: (row) => row.excess_km },
        { key: 'subtotal', header: 'Commercial subtotal', render: (row) => <MoneyDisplay value={row.subtotal_amount} currency={row.currency?.code ?? undefined} /> },
        { key: 'status', header: 'Status', render: (row) => <StatusBadge status={row.status} /> },
    ];

    return (
        <>
            <ContentHeader title="Vehicle rental calculations" description="Immutable customer and owner commercial snapshots. One side never blocks the other." />
            <div className="mb-4 grid gap-4 md:grid-cols-2">
                <Select label="Side" value={side} options={[emptyOption, { value: 'customer', label: 'Customer' }, { value: 'owner', label: 'Owner' }]} onChange={(event) => { setSide(event.target.value); setPage(1); }} />
                <Select label="Status" value={status} options={[emptyOption, { value: 'active', label: 'Active' }, { value: 'cancelled', label: 'Cancelled' }]} onChange={(event) => { setStatus(event.target.value); setPage(1); }} />
            </div>
            <ErrorAlert error={result.error} />
            {result.loading ? <LoadingState /> : <DataTable rows={result.data?.data ?? []} columns={columns} rowKey={(row) => row.id} />}
            <Pagination meta={result.data?.meta} onPageChange={setPage} />
        </>
    );
}

export default function VehicleRentalWorkspacePage() {
    const { pathname } = useLocation();
    const section = pathname.split('/').filter(Boolean)[1] ?? 'overview';

    if (section === 'agreements') return <AgreementsPage />;
    if (section === 'assignments') return <AssignmentsPage />;
    if (section === 'running-charts') return <RunningChartsPage />;
    if (section === 'calculations') return <CalculationsPage />;

    return <OverviewPage />;
}
