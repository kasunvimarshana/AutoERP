import { useEffect, useState, type FormEvent } from 'react';
import { Link } from 'react-router-dom';
import { CustomerLookupSelect } from '@/modules/customer/components/CustomerLookupSelect';
import type { CustomerSummary } from '@/modules/customer/customerTypes';
import { VehicleLookupSelect } from '@/modules/vehicle/components/VehicleLookupSelect';
import type { VehicleSummary } from '@/modules/vehicle/vehicleTypes';
import { lookupVehicleServiceJobTypes } from '@/modules/vehicle-service/api/jobTypes';
import type { VehicleServiceJobTypeResource } from '@/modules/vehicle-service/vehicleServiceTypes';
import { Button, LinkButton } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Panel } from '@/shared/components/Panel';
import { Select } from '@/shared/components/Select';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { useApi } from '@/shared/hooks/useApi';
import { businessDateInputValue } from '@/shared/utils/businessDate';
import { formatDate } from '@/shared/utils/formatDate';
import { formatMoney } from '@/shared/utils/formatMoney';
import { ExportActions } from '../components/ExportActions';
import { runServiceJobsByJobTypeReport } from '../reportingApi';
import type {
    ServiceJobsByJobTypeDatePreset,
    ServiceJobsByJobTypeGroup,
    ServiceJobsByJobTypeJob,
    ServiceJobsByJobTypeParams,
} from '../types/serviceJobsByJobTypeReportTypes';

const reportKey = 'vehicle-service/jobs-by-type';

const statusOptions = [
    { value: 'all', label: 'All Active Statuses' },
    { value: 'draft', label: 'Draft' },
    { value: 'inspected', label: 'Inspected' },
    { value: 'in_progress', label: 'In Progress' },
    { value: 'completed', label: 'Completed' },
    { value: 'invoiced', label: 'Invoiced' },
    { value: 'partially_paid', label: 'Partially Paid' },
    { value: 'paid', label: 'Paid' },
    { value: 'cancelled', label: 'Cancelled' },
];

function calculateRangeForPreset(preset: Exclude<ServiceJobsByJobTypeDatePreset, 'custom'>): { date_from?: string; date_to?: string } {
    const today = businessDateInputValue();
    if (preset === 'today') {
        return { date_from: today, date_to: today };
    }
    if (preset === 'this_month') {
        return { date_from: `${today.slice(0, 7)}-01`, date_to: today };
    }
    if (preset === 'this_year') {
        return { date_from: `${today.slice(0, 4)}-01-01`, date_to: today };
    }
    if (preset === 'this_week') {
        const [year, month, day] = today.split('-').map(Number);
        const date = new Date(Date.UTC(year, month - 1, day));
        const dayOfWeek = (date.getUTCDay() + 6) % 7; // Monday = 0
        date.setUTCDate(date.getUTCDate() - dayOfWeek);
        const start = [
            date.getUTCFullYear(),
            String(date.getUTCMonth() + 1).padStart(2, '0'),
            String(date.getUTCDate()).padStart(2, '0'),
        ].join('-');
        return { date_from: start, date_to: today };
    }
    return {};
}

export default function ServiceJobsByJobTypeReportPage() {
    const [availableJobTypes, setAvailableJobTypes] = useState<VehicleServiceJobTypeResource[]>([]);
    const [vehicle, setVehicle] = useState<VehicleSummary | null>(null);
    const [customer, setCustomer] = useState<CustomerSummary | null>(null);
    const [datePreset, setDatePreset] = useState<ServiceJobsByJobTypeDatePreset>('this_month');

    const [collapsedGroups, setCollapsedGroups] = useState<Record<string, boolean>>({});

    const initialRange = calculateRangeForPreset('this_month');
    const [filters, setFilters] = useState<ServiceJobsByJobTypeParams>({
        date_from: initialRange.date_from,
        date_to: initialRange.date_to,
        date_preset: 'this_month',
        job_status: 'all',
        include_cancelled: false,
    });
    const [draft, setDraft] = useState<ServiceJobsByJobTypeParams>(filters);

    useEffect(() => {
        const abortCtrl = new AbortController();
        lookupVehicleServiceJobTypes(abortCtrl.signal)
            .then((types) => setAvailableJobTypes(types))
            .catch(() => {});
        return () => abortCtrl.abort();
    }, []);

    const report = useApi(
        (signal) =>
            runServiceJobsByJobTypeReport(
                {
                    ...filters,
                    vehicle_id: vehicle?.id,
                    customer_id: customer?.id,
                },
                signal,
            ),
        [filters, vehicle?.id, customer?.id],
    );

    const { data: result, loading, error } = report;

    const selectPreset = (preset: ServiceJobsByJobTypeDatePreset) => {
        setDatePreset(preset);
        if (preset === 'custom') {
            const next = { ...filters, date_preset: preset };
            setDraft(next);
            setFilters(next);
            return;
        }
        const range = calculateRangeForPreset(preset);
        const next: ServiceJobsByJobTypeParams = {
            ...filters,
            date_preset: preset,
            date_from: range.date_from,
            date_to: range.date_to,
        };
        setDraft(next);
        setFilters(next);
    };

    const apply = (event: FormEvent) => {
        event.preventDefault();
        setFilters({
            ...draft,
            vehicle_id: vehicle?.id,
            customer_id: customer?.id,
        });
    };

    const reset = () => {
        const range = calculateRangeForPreset('this_month');
        const defaultFilters: ServiceJobsByJobTypeParams = {
            date_from: range.date_from,
            date_to: range.date_to,
            date_preset: 'this_month',
            job_status: 'all',
            job_type_id: undefined,
            search: undefined,
            include_cancelled: false,
        };
        setDatePreset('this_month');
        setVehicle(null);
        setCustomer(null);
        setDraft(defaultFilters);
        setFilters(defaultFilters);
    };

    const toggleGroupCollapse = (groupCode: string) => {
        setCollapsedGroups((prev) => ({
            ...prev,
            [groupCode]: !prev[groupCode],
        }));
    };

    const toggleAllGroups = (collapse: boolean) => {
        if (!result) return;
        const nextState: Record<string, boolean> = {};
        for (const g of result.groups) {
            nextState[g.job_type_code] = collapse;
        }
        setCollapsedGroups(nextState);
    };

    const exportParams: ServiceJobsByJobTypeParams = {
        ...filters,
        vehicle_id: vehicle?.id,
        customer_id: customer?.id,
    };

    return (
        <div className="space-y-6">
            <ContentHeader
                title="Service Jobs by Job Type"
                description="Comprehensive breakdown and financial comparison of vehicle service jobs grouped by Job Type."
                actions={
                    <div className="flex flex-wrap items-center gap-2">
                        <LinkButton to="/reports" variant="secondary">
                            All reports
                        </LinkButton>
                        <ExportActions reportKey={reportKey} params={exportParams} />
                    </div>
                }
            />

            <ErrorAlert error={error} title="Could not load service jobs by job type report" />

            {/* Filter Panel */}
            <Panel title="Report Filters">
                <div className="space-y-4">
                    {/* Date Presets */}
                    <div>
                        <span className="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1.5">
                            Date Preset
                        </span>
                        <div className="flex flex-wrap gap-2" role="group" aria-label="Date range preset">
                            {(['today', 'this_week', 'this_month', 'this_year', 'all'] as const).map((preset) => (
                                <Button
                                    key={preset}
                                    type="button"
                                    variant={datePreset === preset ? 'primary' : 'secondary'}
                                    onClick={() => selectPreset(preset)}
                                >
                                    {preset === 'today'
                                        ? 'Today'
                                        : preset === 'this_week'
                                          ? 'This Week'
                                          : preset === 'this_month'
                                            ? 'This Month'
                                            : preset === 'this_year'
                                              ? 'This Year'
                                              : 'All Time'}
                                </Button>
                            ))}
                        </div>
                    </div>

                    <form className="space-y-4" onSubmit={apply}>
                        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            <Input
                                label="From"
                                type="date"
                                value={draft.date_from ?? ''}
                                onChange={(e) => {
                                    setDatePreset('custom');
                                    setDraft((cur) => ({ ...cur, date_from: e.target.value || undefined, date_preset: 'custom' }));
                                }}
                            />
                            <Input
                                label="To"
                                type="date"
                                value={draft.date_to ?? ''}
                                onChange={(e) => {
                                    setDatePreset('custom');
                                    setDraft((cur) => ({ ...cur, date_to: e.target.value || undefined, date_preset: 'custom' }));
                                }}
                            />
                            <Select
                                label="Job Type"
                                value={draft.job_type_id ? String(draft.job_type_id) : ''}
                                placeholder={null}
                                options={[
                                    { value: '', label: 'All Job Types' },
                                    ...availableJobTypes.map((t) => ({
                                        value: String(t.id),
                                        label: `${t.name} (${t.tracks_mileage ? 'Tracks mileage' : 'No mileage'})`,
                                    })),
                                ]}
                                onChange={(e) => {
                                    const val = e.target.value;
                                    setDraft((cur) => ({ ...cur, job_type_id: val ? Number(val) : undefined }));
                                }}
                            />
                            <Select
                                label="Job Status"
                                value={draft.job_status ?? 'all'}
                                placeholder={null}
                                options={statusOptions}
                                onChange={(e) => {
                                    setDraft((cur) => ({ ...cur, job_status: e.target.value }));
                                }}
                            />
                        </div>

                        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            <CustomerLookupSelect
                                label="Filter by Customer"
                                value={customer}
                                onChange={setCustomer}
                            />
                            <div>
                                <VehicleLookupSelect
                                    value={vehicle}
                                    onChange={setVehicle}
                                    kind="all"
                                />
                            </div>
                            <Input
                                label="Search Keyword"
                                placeholder="Job number, registration, customer name..."
                                value={draft.search ?? ''}
                                onChange={(e) => {
                                    setDraft((cur) => ({ ...cur, search: e.target.value || undefined }));
                                }}
                            />
                        </div>

                        <div className="flex flex-wrap items-center justify-between gap-4 border-t border-slate-100 pt-3">
                            <label className="flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                                <input
                                    type="checkbox"
                                    className="rounded border-slate-300 text-primary-600 focus:ring-primary-500"
                                    checked={Boolean(draft.include_cancelled)}
                                    onChange={(e) => setDraft((cur) => ({ ...cur, include_cancelled: e.target.checked }))}
                                />
                                <span>Include cancelled jobs</span>
                            </label>

                            <div className="flex items-center gap-2">
                                <Button type="button" variant="secondary" onClick={reset}>
                                    Reset
                                </Button>
                                <Button type="submit" loading={loading}>
                                    Apply filters
                                </Button>
                            </div>
                        </div>
                    </form>
                </div>
            </Panel>

            {loading ? (
                <LoadingState label="Loading service jobs report..." />
            ) : result ? (
                <div className="space-y-6">
                    {/* Overall KPI Metric Cards */}
                    <section className="grid gap-3 sm:grid-cols-2 lg:grid-cols-5" aria-label="Summary KPI Metrics">
                        <MetricCard
                            label="Total Jobs"
                            value={String(result.summary.total_jobs)}
                            subtext={`${result.groups.length} job ${result.groups.length === 1 ? 'type' : 'types'} active`}
                            tone="blue"
                        />
                        <MetricCard
                            label="Total Revenue"
                            value={formatMoney(result.summary.total_revenue)}
                            subtext="Gross invoiced / completed total"
                            tone="emerald"
                        />
                        <MetricCard
                            label="Average Job Value"
                            value={formatMoney(result.summary.average_job_value)}
                            subtext="Mean revenue per service job"
                            tone="purple"
                        />
                        <MetricCard
                            label="Total Discounts"
                            value={formatMoney(result.summary.total_discount)}
                            subtext="Promotions & job adjustments"
                            tone="amber"
                        />
                        <MetricCard
                            label="Total Subtotal"
                            value={formatMoney(result.summary.total_subtotal)}
                            subtext="Before discounts & taxes"
                            tone="slate"
                        />
                    </section>

                    {/* Job Type Comparative Breakdown Table */}
                    <Panel
                        title="Job Type Comparison & Performance"
                        headerAction={
                            <div className="flex items-center gap-2">
                                <Button
                                    type="button"
                                    variant="secondary"
                                    onClick={() => toggleAllGroups(false)}
                                >
                                    Expand All
                                </Button>
                                <Button
                                    type="button"
                                    variant="secondary"
                                    onClick={() => toggleAllGroups(true)}
                                >
                                    Collapse All
                                </Button>
                            </div>
                        }
                    >
                        {result.groups.length === 0 ? (
                            <div className="py-6 text-center text-sm text-slate-500">
                                No service jobs found matching the selected filters.
                            </div>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-left text-sm text-slate-700">
                                    <thead className="bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-500 border-b border-slate-200">
                                        <tr>
                                            <th className="px-4 py-3">Job Type</th>
                                            <th className="px-4 py-3 text-center">Tracking</th>
                                            <th className="px-4 py-3 text-right">Jobs Count</th>
                                            <th className="px-4 py-3 text-right">Job Share</th>
                                            <th className="px-4 py-3 text-right">Subtotal</th>
                                            <th className="px-4 py-3 text-right">Discounts</th>
                                            <th className="px-4 py-3 text-right">Total Revenue</th>
                                            <th className="px-4 py-3 text-right">Revenue Share</th>
                                            <th className="px-4 py-3 text-right">Avg Value</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-100">
                                        {result.groups.map((group) => (
                                            <tr
                                                key={group.job_type_code}
                                                className="hover:bg-slate-50 transition-colors"
                                            >
                                                <td className="px-4 py-3 font-medium text-slate-900">
                                                    <div className="flex items-center gap-2">
                                                        <span>{group.job_type_name}</span>
                                                        <span className="rounded bg-slate-100 px-1.5 py-0.5 text-xs text-slate-600 font-mono">
                                                            {group.job_type_code}
                                                        </span>
                                                    </div>
                                                </td>
                                                <td className="px-4 py-3 text-center">
                                                    {group.tracks_mileage ? (
                                                        <span className="inline-flex rounded-full bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-700 border border-blue-200">
                                                            Mileage
                                                        </span>
                                                    ) : (
                                                        <span className="inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">
                                                            Standard
                                                        </span>
                                                    )}
                                                </td>
                                                <td className="px-4 py-3 text-right font-semibold text-slate-900 tabular-nums">
                                                    {group.total_jobs}
                                                </td>
                                                <td className="px-4 py-3 text-right text-slate-600 tabular-nums">
                                                    {group.jobs_share_percentage.toFixed(1)}%
                                                </td>
                                                <td className="px-4 py-3 text-right text-slate-700 tabular-nums">
                                                    {formatMoney(group.total_subtotal)}
                                                </td>
                                                <td className="px-4 py-3 text-right text-amber-700 tabular-nums">
                                                    {formatMoney(group.total_discount)}
                                                </td>
                                                <td className="px-4 py-3 text-right font-bold text-slate-900 tabular-nums">
                                                    {formatMoney(group.total_revenue)}
                                                </td>
                                                <td className="px-4 py-3 text-right tabular-nums">
                                                    <div className="flex items-center justify-end gap-2">
                                                        <div className="w-16 bg-slate-200 rounded-full h-1.5 overflow-hidden">
                                                            <div
                                                                className="bg-emerald-500 h-1.5 rounded-full"
                                                                style={{ width: `${Math.min(100, Math.max(0, group.revenue_share_percentage))}%` }}
                                                            />
                                                        </div>
                                                        <span className="text-xs font-medium text-slate-700 min-w-[36px]">
                                                            {group.revenue_share_percentage.toFixed(1)}%
                                                        </span>
                                                    </div>
                                                </td>
                                                <td className="px-4 py-3 text-right font-medium text-purple-700 tabular-nums">
                                                    {formatMoney(group.average_job_value)}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                    <tfoot className="bg-slate-100/80 font-semibold border-t-2 border-slate-300 text-slate-900">
                                        <tr>
                                            <td className="px-4 py-3" colSpan={2}>
                                                Total Across All Types
                                            </td>
                                            <td className="px-4 py-3 text-right tabular-nums">
                                                {result.summary.total_jobs}
                                            </td>
                                            <td className="px-4 py-3 text-right tabular-nums">100.0%</td>
                                            <td className="px-4 py-3 text-right tabular-nums">
                                                {formatMoney(result.summary.total_subtotal)}
                                            </td>
                                            <td className="px-4 py-3 text-right tabular-nums text-amber-700">
                                                {formatMoney(result.summary.total_discount)}
                                            </td>
                                            <td className="px-4 py-3 text-right tabular-nums font-bold">
                                                {formatMoney(result.summary.total_revenue)}
                                            </td>
                                            <td className="px-4 py-3 text-right tabular-nums">100.0%</td>
                                            <td className="px-4 py-3 text-right tabular-nums text-purple-700">
                                                {formatMoney(result.summary.average_job_value)}
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        )}
                    </Panel>

                    {/* Grouped Detailed Jobs Sections */}
                    <div className="space-y-4">
                        <div className="flex items-center justify-between">
                            <h2 className="text-base font-semibold text-slate-900">
                                Detailed Service Jobs by Group
                            </h2>
                            <span className="text-xs text-slate-500">
                                Showing {result.data.length} {result.data.length === 1 ? 'job' : 'jobs'} across {result.groups.length} {result.groups.length === 1 ? 'group' : 'groups'}
                            </span>
                        </div>

                        {result.groups.map((group) => {
                            const isCollapsed = Boolean(collapsedGroups[group.job_type_code]);
                            return (
                                <JobTypeGroupSection
                                    key={group.job_type_code}
                                    group={group}
                                    isCollapsed={isCollapsed}
                                    onToggleCollapse={() => toggleGroupCollapse(group.job_type_code)}
                                />
                            );
                        })}
                    </div>
                </div>
            ) : null}
        </div>
    );
}

function MetricCard({
    label,
    value,
    subtext,
    tone = 'slate',
}: {
    label: string;
    value: string;
    subtext: string;
    tone?: 'blue' | 'emerald' | 'purple' | 'amber' | 'slate';
}) {
    const toneClasses = {
        blue: 'border-l-4 border-l-blue-500 bg-white text-slate-900',
        emerald: 'border-l-4 border-l-emerald-500 bg-white text-slate-900',
        purple: 'border-l-4 border-l-purple-500 bg-white text-slate-900',
        amber: 'border-l-4 border-l-amber-500 bg-white text-slate-900',
        slate: 'border-l-4 border-l-slate-500 bg-white text-slate-900',
    };

    return (
        <div className={`rounded-lg border border-slate-200 p-4 shadow-xs ${toneClasses[tone]}`}>
            <span className="text-xs font-medium text-slate-500 block">{label}</span>
            <div className="mt-1 text-2xl font-bold tracking-tight tabular-nums">{value}</div>
            <span className="mt-1 block text-xs text-slate-400">{subtext}</span>
        </div>
    );
}

function JobTypeGroupSection({
    group,
    isCollapsed,
    onToggleCollapse,
}: {
    group: ServiceJobsByJobTypeGroup;
    isCollapsed: boolean;
    onToggleCollapse: () => void;
}) {
    return (
        <div className="rounded-lg border border-slate-200 bg-white shadow-xs overflow-hidden">
            {/* Header Accordion */}
            <div
                className="flex flex-wrap items-center justify-between gap-3 bg-slate-50/80 px-4 py-3 cursor-pointer select-none hover:bg-slate-100/70 transition-colors border-b border-slate-200"
                onClick={onToggleCollapse}
                role="button"
                tabIndex={0}
                onKeyDown={(e) => {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        onToggleCollapse();
                    }
                }}
                aria-expanded={!isCollapsed}
            >
                <div className="flex items-center gap-3">
                    <span className="text-slate-400 transition-transform duration-200">
                        {isCollapsed ? '▶' : '▼'}
                    </span>
                    <div>
                        <div className="flex items-center gap-2">
                            <h3 className="font-semibold text-slate-900 text-base">
                                {group.job_type_name}
                            </h3>
                            <span className="rounded bg-slate-200/80 px-1.5 py-0.5 text-xs text-slate-700 font-mono">
                                {group.job_type_code}
                            </span>
                            {group.tracks_mileage ? (
                                <span className="inline-flex rounded-full bg-blue-100 px-2 py-0.5 text-xs font-semibold text-blue-800">
                                    Tracks Mileage
                                </span>
                            ) : null}
                        </div>
                        <p className="text-xs text-slate-500 mt-0.5">
                            {group.total_jobs} {group.total_jobs === 1 ? 'job' : 'jobs'} · Average {formatMoney(group.average_job_value)}
                        </p>
                    </div>
                </div>

                <div className="flex flex-wrap items-center gap-3">
                    <div className="text-right">
                        <span className="text-xs text-slate-500 block">Total Revenue</span>
                        <span className="text-base font-bold text-slate-900 tabular-nums">
                            {formatMoney(group.total_revenue)}
                        </span>
                    </div>
                    <span className="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-800">
                        {group.revenue_share_percentage.toFixed(1)}% share
                    </span>
                    <Button
                        type="button"
                        variant="secondary"
                        className="text-xs py-1 px-2.5"
                        onClick={(e) => {
                            e.stopPropagation();
                            onToggleCollapse();
                        }}
                    >
                        {isCollapsed ? 'Show Jobs' : 'Hide Jobs'}
                    </Button>
                </div>
            </div>

            {/* Collapsible Content */}
            {!isCollapsed && (
                <div>
                    {group.jobs.length === 0 ? (
                        <div className="p-6 text-center text-sm text-slate-500">
                            No jobs in this group.
                        </div>
                    ) : (
                        <>
                            {/* Desktop Table View */}
                            <div className="hidden md:block overflow-x-auto">
                                <table className="w-full text-left text-sm text-slate-700">
                                    <thead className="bg-slate-50/50 text-xs font-semibold uppercase tracking-wider text-slate-500 border-b border-slate-100">
                                        <tr>
                                            <th className="px-4 py-2.5">Job No.</th>
                                            <th className="px-4 py-2.5">Date</th>
                                            <th className="px-4 py-2.5">Customer</th>
                                            <th className="px-4 py-2.5">Vehicle</th>
                                            <th className="px-4 py-2.5">Supervisor</th>
                                            <th className="px-4 py-2.5">Status</th>
                                            <th className="px-4 py-2.5 text-right">Subtotal</th>
                                            <th className="px-4 py-2.5 text-right">Discount</th>
                                            <th className="px-4 py-2.5 text-right">Grand Total</th>
                                            <th className="px-4 py-2.5 text-right">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-100">
                                        {group.jobs.map((job) => (
                                            <JobTableRow key={job.id} job={job} />
                                        ))}
                                    </tbody>
                                    <tfoot className="bg-slate-50 font-semibold text-slate-900 border-t border-slate-200">
                                        <tr>
                                            <td className="px-4 py-2.5" colSpan={6}>
                                                Subtotal for {group.job_type_name} ({group.total_jobs} {group.total_jobs === 1 ? 'job' : 'jobs'})
                                            </td>
                                            <td className="px-4 py-2.5 text-right tabular-nums">
                                                {formatMoney(group.total_subtotal)}
                                            </td>
                                            <td className="px-4 py-2.5 text-right tabular-nums text-amber-700">
                                                {formatMoney(group.total_discount)}
                                            </td>
                                            <td className="px-4 py-2.5 text-right tabular-nums font-bold">
                                                {formatMoney(group.total_revenue)}
                                            </td>
                                            <td className="px-4 py-2.5" />
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>

                            {/* Mobile Card View */}
                            <div className="md:hidden divide-y divide-slate-100">
                                {group.jobs.map((job) => (
                                    <JobMobileCard key={job.id} job={job} />
                                ))}
                            </div>
                        </>
                    )}
                </div>
            )}
        </div>
    );
}

function JobTableRow({ job }: { job: ServiceJobsByJobTypeJob }) {
    return (
        <tr className="hover:bg-slate-50/70 transition-colors">
            <td className="px-4 py-3 font-semibold text-primary-600">
                <Link
                    to={`/vehicle-service/jobs/${job.id}`}
                    className="hover:underline focus:outline-hidden"
                >
                    {job.job_number}
                </Link>
            </td>
            <td className="px-4 py-3 text-slate-600 whitespace-nowrap">
                {formatDate(job.job_date)}
            </td>
            <td className="px-4 py-3">
                <div className="font-medium text-slate-900">{job.customer_name}</div>
                {job.customer_phone ? (
                    <span className="text-xs text-slate-500">{job.customer_phone}</span>
                ) : null}
            </td>
            <td className="px-4 py-3">
                <div className="font-medium text-slate-900">{job.registration_number}</div>
                <span className="text-xs text-slate-500">{job.vehicle.make} {job.vehicle.model}</span>
            </td>
            <td className="px-4 py-3 text-slate-600">
                {job.supervisor_name ?? <span className="text-slate-400 italic">Unassigned</span>}
            </td>
            <td className="px-4 py-3">
                <StatusBadge status={job.status} label={job.status_label} />
            </td>
            <td className="px-4 py-3 text-right tabular-nums text-slate-700">
                {formatMoney(job.subtotal)}
            </td>
            <td className="px-4 py-3 text-right tabular-nums text-amber-700">
                {formatMoney(job.discount_total)}
            </td>
            <td className="px-4 py-3 text-right tabular-nums font-bold text-slate-900">
                {formatMoney(job.grand_total)}
            </td>
            <td className="px-4 py-3 text-right">
                <Link
                    to={`/vehicle-service/jobs/${job.id}`}
                    className="text-xs font-semibold text-primary-600 hover:text-primary-800 hover:underline"
                >
                    View
                </Link>
            </td>
        </tr>
    );
}

function JobMobileCard({ job }: { job: ServiceJobsByJobTypeJob }) {
    return (
        <div className="p-4 space-y-2">
            <div className="flex items-center justify-between gap-2">
                <Link
                    to={`/vehicle-service/jobs/${job.id}`}
                    className="font-semibold text-primary-600 hover:underline"
                >
                    {job.job_number}
                </Link>
                <StatusBadge status={job.status} label={job.status_label} />
            </div>

            <div className="text-xs text-slate-500">
                Date: <span className="font-medium text-slate-700">{formatDate(job.job_date)}</span>
            </div>

            <div className="grid grid-cols-2 gap-2 text-xs">
                <div>
                    <span className="text-slate-400 block">Customer</span>
                    <span className="font-medium text-slate-800">{job.customer_name}</span>
                </div>
                <div>
                    <span className="text-slate-400 block">Vehicle</span>
                    <span className="font-medium text-slate-800">{job.vehicle_label}</span>
                </div>
            </div>

            <div className="flex items-baseline justify-between border-t border-slate-100 pt-2 text-xs">
                <div>
                    <span className="text-slate-400">Supervisor: </span>
                    <span className="font-medium text-slate-700">{job.supervisor_name ?? 'None'}</span>
                </div>
                <div className="text-right">
                    <span className="text-slate-400">Total: </span>
                    <span className="font-bold text-slate-900 text-sm tabular-nums">
                        {formatMoney(job.grand_total)}
                    </span>
                </div>
            </div>
        </div>
    );
}
