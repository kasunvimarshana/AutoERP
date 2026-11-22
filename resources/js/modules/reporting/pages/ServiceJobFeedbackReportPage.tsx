/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

import { useEffect, useState, type FormEvent } from 'react';
import { Link } from 'react-router-dom';
import { CustomerLookupSelect } from '@/modules/customer/components/CustomerLookupSelect';
import type { CustomerSummary } from '@/modules/customer/customerTypes';
import { VehicleLookupSelect } from '@/modules/vehicle/components/VehicleLookupSelect';
import type { VehicleSummary } from '@/modules/vehicle/vehicleTypes';
import { lookupVehicleServiceJobTypes } from '@/modules/vehicle-service/api/jobTypes';
import type { VehicleServiceJobTypeResource } from '@/modules/vehicle-service/vehicleServiceTypes';
import { Button } from '@/shared/components/Button';
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
import { ExportActions } from '../components/ExportActions';
import { runServiceJobFeedbackReport } from '../reportingApi';
import type {
    ServiceJobFeedbackDatePreset,
    ServiceJobFeedbackReportParams,
    ServiceJobFeedbackReportRow,
    ServiceJobFeedbackType,
} from '../types/serviceJobFeedbackReportTypes';

const reportKey = 'vehicle-service/job-feedback';

const categoryOptions = [
    { value: 'all', label: 'All Categories' },
    { value: 'review', label: 'Customer Reviews' },
    { value: 'customer_feedback', label: 'General Customer Feedback' },
    { value: 'internal_note', label: 'Internal Notes' },
    { value: 'complaint', label: 'Customer Complaints' },
];

const ratingFilterOptions = [
    { value: 'all', label: 'All Ratings' },
    { value: '5', label: '5 Stars (★★★★★)' },
    { value: '4', label: '4 Stars (★★★★☆)' },
    { value: '3', label: '3 Stars (★★★☆☆)' },
    { value: '2', label: '2 Stars (★★☆☆☆)' },
    { value: '1', label: '1 Star (★☆☆☆☆)' },
    { value: 'rated', label: 'Rated Only' },
    { value: 'unrated', label: 'Unrated / Notes Only' },
];

const visibilityOptions = [
    { value: 'all', label: 'All Visibility' },
    { value: 'customer_visible', label: 'Customer Visible' },
    { value: 'internal_only', label: 'Internal Only' },
];

const jobStatusOptions = [
    { value: 'all', label: 'All Job Statuses' },
    { value: 'draft', label: 'Draft' },
    { value: 'inspected', label: 'Inspected' },
    { value: 'in_progress', label: 'In Progress' },
    { value: 'completed', label: 'Completed' },
    { value: 'invoiced', label: 'Invoiced' },
    { value: 'cancelled', label: 'Cancelled' },
];

function calculateRangeForPreset(preset: Exclude<ServiceJobFeedbackDatePreset, 'custom'>): { date_from?: string; date_to?: string } {
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

export default function ServiceJobFeedbackReportPage() {
    const [availableJobTypes, setAvailableJobTypes] = useState<VehicleServiceJobTypeResource[]>([]);
    const [vehicle, setVehicle] = useState<VehicleSummary | null>(null);
    const [customer, setCustomer] = useState<CustomerSummary | null>(null);
    const [datePreset, setDatePreset] = useState<ServiceJobFeedbackDatePreset>('this_month');

    const initialRange = calculateRangeForPreset('this_month');
    const [filters, setFilters] = useState<ServiceJobFeedbackReportParams>({
        date_from: initialRange.date_from,
        date_to: initialRange.date_to,
        date_preset: 'this_month',
        date_by: 'job_date',
        feedback_type: 'all',
        rating: 'all',
        is_customer_visible: 'all',
        job_status: 'all',
        job_type_id: undefined,
        customer_id: undefined,
        vehicle_id: undefined,
        search: '',
        page: 1,
        per_page: 25,
    });

    const [searchInput, setSearchInput] = useState<string>('');

    // Load available job types for lookup
    useEffect(() => {
        let mounted = true;
        lookupVehicleServiceJobTypes()
            .then((jobTypes) => {
                if (mounted) {
                    const resolved = Array.isArray(jobTypes)
                        ? jobTypes
                        : Array.isArray((jobTypes as unknown as { data?: VehicleServiceJobTypeResource[] })?.data)
                            ? (jobTypes as unknown as { data: VehicleServiceJobTypeResource[] }).data
                            : [];
                    setAvailableJobTypes(resolved);
                }
            })
            .catch(() => {
                // Ignore lookup errors
            });
        return () => {
            mounted = false;
        };
    }, []);

    const result = useApi(
        (signal) => runServiceJobFeedbackReport(filters, signal),
        [filters],
    );

    const reportData = result.data;
    const summary = reportData?.summary;
    const rows = reportData?.data ?? [];
    const meta = reportData?.meta;

    const handlePresetChange = (preset: ServiceJobFeedbackDatePreset) => {
        setDatePreset(preset);
        if (preset === 'custom') {
            setFilters((prev) => ({ ...prev, date_preset: 'custom', page: 1 }));
            return;
        }
        const range = calculateRangeForPreset(preset);
        setFilters((prev) => ({
            ...prev,
            date_preset: preset,
            date_from: range.date_from,
            date_to: range.date_to,
            page: 1,
        }));
    };

    const handleSearchSubmit = (e: FormEvent) => {
        e.preventDefault();
        setFilters((prev) => ({
            ...prev,
            search: searchInput.trim() || undefined,
            page: 1,
        }));
    };

    const handleClearFilters = () => {
        const defaultRange = calculateRangeForPreset('this_month');
        setDatePreset('this_month');
        setSearchInput('');
        setCustomer(null);
        setVehicle(null);
        setFilters({
            date_from: defaultRange.date_from,
            date_to: defaultRange.date_to,
            date_preset: 'this_month',
            date_by: 'job_date',
            feedback_type: 'all',
            rating: 'all',
            is_customer_visible: 'all',
            job_status: 'all',
            job_type_id: undefined,
            customer_id: undefined,
            vehicle_id: undefined,
            search: '',
            page: 1,
            per_page: 25,
        });
    };

    const handleRatingBarClick = (star: number) => {
        const newRating = filters.rating === String(star) ? 'all' : String(star);
        setFilters((prev) => ({
            ...prev,
            rating: newRating,
            page: 1,
        }));
    };

    const hasActiveFilters = Boolean(
        filters.search ||
        (filters.feedback_type && filters.feedback_type !== 'all') ||
        (filters.rating && filters.rating !== 'all') ||
        (filters.is_customer_visible && filters.is_customer_visible !== 'all') ||
        (filters.job_status && filters.job_status !== 'all') ||
        filters.job_type_id ||
        filters.customer_id ||
        filters.vehicle_id ||
        datePreset === 'all' ||
        datePreset === 'custom'
    );

    return (
        <div className="space-y-6">
            <ContentHeader
                title="Service Jobs Reviews & Ratings Report"
                description="Comprehensive audit of service job reviews, ratings distribution, comments, internal notes, and customer complaints."
                actions={
                    <div className="flex items-center gap-2">
                        <ExportActions reportKey={reportKey} params={filters} />
                    </div>
                }
            />

            <ErrorAlert error={result.error} />

            {/* Top KPI Cards */}
            {summary && (
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {/* Average Rating Card */}
                    <div className="rounded-xl border border-slate-200 bg-white p-5 shadow-2xs">
                        <div className="flex items-center justify-between">
                            <span className="text-xs font-semibold uppercase tracking-wider text-slate-500">Average Rating</span>
                            <span className="inline-flex items-center rounded-full bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700">
                                ★ {summary.average_rating !== null ? `${summary.average_rating.toFixed(2)} / 5` : 'N/A'}
                            </span>
                        </div>
                        <div className="mt-2 flex items-baseline gap-2">
                            <span className="text-3xl font-bold tracking-tight text-slate-900">
                                {summary.average_rating !== null ? summary.average_rating.toFixed(2) : '—'}
                            </span>
                            <span className="text-sm font-medium text-slate-500">out of 5.0</span>
                        </div>
                        <div className="mt-2">
                            <StarVisualizer rating={summary.average_rating ?? 0} />
                        </div>
                        <p className="mt-2 text-xs text-slate-500">
                            Based on {summary.total_rated} rating{summary.total_rated === 1 ? '' : 's'} across {summary.total_jobs} service job{summary.total_jobs === 1 ? '' : 's'}
                        </p>
                    </div>

                    {/* Total Feedbacks & Category Breakdown */}
                    <div className="rounded-xl border border-slate-200 bg-white p-5 shadow-2xs">
                        <div className="flex items-center justify-between">
                            <span className="text-xs font-semibold uppercase tracking-wider text-slate-500">Total Feedback</span>
                            <span className="rounded-full bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-700">
                                {summary.total_feedbacks} items
                            </span>
                        </div>
                        <div className="mt-2 text-3xl font-bold tracking-tight text-slate-900">
                            {summary.total_feedbacks}
                        </div>
                        <div className="mt-3 flex flex-wrap gap-1.5 text-xs">
                            <span className="inline-flex items-center rounded bg-emerald-50 px-2 py-0.5 font-medium text-emerald-700">
                                {summary.reviews_count} Reviews
                            </span>
                            <span className="inline-flex items-center rounded bg-blue-50 px-2 py-0.5 font-medium text-blue-700">
                                {summary.customer_feedback_count} Feedback
                            </span>
                            <span className="inline-flex items-center rounded bg-slate-100 px-2 py-0.5 font-medium text-slate-700">
                                {summary.internal_notes_count} Notes
                            </span>
                            <span className="inline-flex items-center rounded bg-rose-50 px-2 py-0.5 font-medium text-rose-700">
                                {summary.complaints_count} Complaints
                            </span>
                        </div>
                    </div>

                    {/* Satisfaction & Positive Sentiment */}
                    <div className="rounded-xl border border-slate-200 bg-white p-5 shadow-2xs">
                        <div className="flex items-center justify-between">
                            <span className="text-xs font-semibold uppercase tracking-wider text-slate-500">Satisfaction Rate</span>
                            <span className="rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700">
                                4★ & 5★ Ratings
                            </span>
                        </div>
                        <div className="mt-2 flex items-baseline gap-2">
                            <span className="text-3xl font-bold tracking-tight text-emerald-600">
                                {summary.positive_rating_percentage}%
                            </span>
                            <span className="text-xs text-slate-500">positive</span>
                        </div>
                        <div className="mt-2 h-2 w-full overflow-hidden rounded-full bg-slate-100">
                            <div
                                className="h-full rounded-full bg-emerald-500 transition-all duration-300"
                                style={{ width: `${Math.min(100, summary.positive_rating_percentage)}%` }}
                            />
                        </div>
                        <p className="mt-2 text-xs text-slate-500">
                            {summary.positive_ratings_count} of {summary.total_rated} ratings are 4 or 5 stars
                        </p>
                    </div>

                    {/* Complaint Rate Card */}
                    <div className="rounded-xl border border-slate-200 bg-white p-5 shadow-2xs">
                        <div className="flex items-center justify-between">
                            <span className="text-xs font-semibold uppercase tracking-wider text-slate-500">Complaint Rate</span>
                            <span className={`rounded-full px-2 py-0.5 text-xs font-medium ${summary.complaints_count > 0 ? 'bg-rose-50 text-rose-700' : 'bg-slate-100 text-slate-600'}`}>
                                {summary.complaints_count} {summary.complaints_count === 1 ? 'case' : 'cases'}
                            </span>
                        </div>
                        <div className="mt-2 flex items-baseline gap-2">
                            <span className={`text-3xl font-bold tracking-tight ${summary.complaints_count > 0 ? 'text-rose-600' : 'text-slate-900'}`}>
                                {summary.complaint_rate_percentage}%
                            </span>
                            <span className="text-xs text-slate-500">of feedback</span>
                        </div>
                        <div className="mt-2 h-2 w-full overflow-hidden rounded-full bg-slate-100">
                            <div
                                className={`h-full rounded-full transition-all duration-300 ${summary.complaints_count > 0 ? 'bg-rose-500' : 'bg-slate-300'}`}
                                style={{ width: `${Math.min(100, summary.complaint_rate_percentage)}%` }}
                            />
                        </div>
                        <p className="mt-2 text-xs text-slate-500">
                            {summary.customer_visible_count} customer visible, {summary.internal_only_count} internal notes
                        </p>
                    </div>
                </div>
            )}

            {/* Rating Distribution Breakdown Bars */}
            {summary && summary.total_rated > 0 && (
                <Panel
                    title="Rating Distribution (1 – 5 Stars)"
                    description="Click on any rating row to filter the feedback list by that star rating."
                >
                    <div className="grid grid-cols-1 gap-2 pt-2 sm:grid-cols-5">
                        {[5, 4, 3, 2, 1].map((star) => {
                            const detail = summary.rating_breakdown_details?.[star] ?? {
                                stars: star,
                                count: summary.rating_breakdown?.[star] ?? 0,
                                percentage: 0,
                            };
                            const isSelected = filters.rating === String(star);

                            return (
                                <button
                                    key={star}
                                    type="button"
                                    onClick={() => handleRatingBarClick(star)}
                                    className={`group flex flex-col justify-between rounded-xl border p-3 text-left transition-all ${
                                        isSelected
                                            ? 'border-amber-400 bg-amber-50/70 shadow-xs ring-2 ring-amber-400/40'
                                            : 'border-slate-200 bg-slate-50/50 hover:border-slate-300 hover:bg-slate-100/60'
                                    }`}
                                >
                                    <div className="flex items-center justify-between">
                                        <span className="text-sm font-semibold text-slate-800">
                                            {star} Star{star > 1 ? 's' : ''}
                                        </span>
                                        <span className="text-xs font-medium text-slate-500">
                                            {detail.count} ({detail.percentage}%)
                                        </span>
                                    </div>
                                    <div className="mt-2 text-amber-400 text-xs tracking-wider">
                                        {'★'.repeat(star)}{'☆'.repeat(5 - star)}
                                    </div>
                                    <div className="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-slate-200">
                                        <div
                                            className="h-full rounded-full bg-amber-400 transition-all duration-300"
                                            style={{ width: `${Math.min(100, detail.percentage)}%` }}
                                        />
                                    </div>
                                </button>
                            );
                        })}
                    </div>
                </Panel>
            )}

            {/* Filter Panel */}
            <div className="space-y-4 rounded-xl border border-slate-200 bg-white p-4 shadow-2xs sm:p-5">
                {/* Date Presets Row */}
                <div className="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 pb-3">
                    <div className="flex flex-wrap items-center gap-1.5">
                        <span className="mr-1 text-xs font-medium uppercase tracking-wider text-slate-400">Date Presets:</span>
                        {(['today', 'this_week', 'this_month', 'this_year', 'all'] as const).map((preset) => (
                            <button
                                key={preset}
                                type="button"
                                onClick={() => handlePresetChange(preset)}
                                className={`rounded-lg px-2.5 py-1 text-xs font-medium transition-colors ${
                                    datePreset === preset
                                        ? 'bg-blue-600 text-white shadow-2xs'
                                        : 'bg-slate-100 text-slate-600 hover:bg-slate-200 hover:text-slate-900'
                                }`}
                            >
                                {preset === 'today' && 'Today'}
                                {preset === 'this_week' && 'This Week'}
                                {preset === 'this_month' && 'This Month'}
                                {preset === 'this_year' && 'This Year'}
                                {preset === 'all' && 'All Time'}
                            </button>
                        ))}
                    </div>

                    {hasActiveFilters && (
                        <Button type="button" variant="ghost" size="sm" onClick={handleClearFilters}>
                            Clear all filters
                        </Button>
                    )}
                </div>

                {/* Filter Controls Grid */}
                <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    {/* Date From */}
                    <Input
                        label="From Date"
                        type="date"
                        value={filters.date_from ?? ''}
                        onChange={(e) => {
                            setDatePreset('custom');
                            setFilters((prev) => ({ ...prev, date_from: e.target.value || undefined, date_preset: 'custom', page: 1 }));
                        }}
                    />

                    {/* Date To */}
                    <Input
                        label="To Date"
                        type="date"
                        value={filters.date_to ?? ''}
                        onChange={(e) => {
                            setDatePreset('custom');
                            setFilters((prev) => ({ ...prev, date_to: e.target.value || undefined, date_preset: 'custom', page: 1 }));
                        }}
                    />

                    {/* Feedback Category */}
                    <Select
                        label="Feedback Category"
                        value={filters.feedback_type ?? 'all'}
                        onChange={(e) => setFilters((prev) => ({ ...prev, feedback_type: e.target.value, page: 1 }))}
                        options={categoryOptions}
                    />

                    {/* Rating Filter */}
                    <Select
                        label="Star Rating"
                        value={filters.rating ?? 'all'}
                        onChange={(e) => setFilters((prev) => ({ ...prev, rating: e.target.value, page: 1 }))}
                        options={ratingFilterOptions}
                    />

                    {/* Job Status Filter */}
                    <Select
                        label="Job Status"
                        value={filters.job_status ?? 'all'}
                        onChange={(e) => setFilters((prev) => ({ ...prev, job_status: e.target.value, page: 1 }))}
                        options={jobStatusOptions}
                    />

                    {/* Job Type Filter */}
                    <Select
                        label="Job Type"
                        value={filters.job_type_id ? String(filters.job_type_id) : ''}
                        onChange={(e) => setFilters((prev) => ({ ...prev, job_type_id: e.target.value ? Number(e.target.value) : undefined, page: 1 }))}
                        options={[
                            { value: '', label: 'All Job Types' },
                            ...(Array.isArray(availableJobTypes) ? availableJobTypes : []).map((jt) => ({ value: String(jt.id), label: jt.name })),
                        ]}
                    />

                    {/* Visibility Filter */}
                    <Select
                        label="Customer Visibility"
                        value={filters.is_customer_visible ?? 'all'}
                        onChange={(e) => setFilters((prev) => ({ ...prev, is_customer_visible: e.target.value, page: 1 }))}
                        options={visibilityOptions}
                    />

                    {/* Date Scope By */}
                    <Select
                        label="Filter Date By"
                        value={filters.date_by ?? 'job_date'}
                        onChange={(e) => setFilters((prev) => ({ ...prev, date_by: e.target.value as 'job_date' | 'feedback_date', page: 1 }))}
                        options={[
                            { value: 'job_date', label: 'Service Job Date' },
                            { value: 'feedback_date', label: 'Feedback Submission Date' },
                        ]}
                    />
                </div>

                {/* Second Row: Autocomplete Selects & Search Input */}
                <div className="grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <CustomerLookupSelect
                        label="Filter Customer"
                        value={customer}
                        onChange={(selected) => {
                            setCustomer(selected);
                            setFilters((prev) => ({ ...prev, customer_id: selected?.id, page: 1 }));
                        }}
                    />

                    <div>
                        <VehicleLookupSelect
                            value={vehicle}
                            onChange={(selected) => {
                                setVehicle(selected);
                                setFilters((prev) => ({ ...prev, vehicle_id: selected?.id, page: 1 }));
                            }}
                            kind="all"
                        />
                    </div>

                    <div>
                        <form onSubmit={handleSearchSubmit} className="flex flex-col gap-1">
                            <label className="text-xs font-medium text-slate-700">Keyword Search</label>
                            <div className="flex gap-2">
                                <Input
                                    placeholder="Job #, author, comment keywords..."
                                    value={searchInput}
                                    onChange={(e) => setSearchInput(e.target.value)}
                                    className="flex-1"
                                />
                                <Button type="submit" variant="secondary" size="md">
                                    Search
                                </Button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            {/* Feedbacks Data Table / Card View */}
            {result.loading && rows.length === 0 ? (
                <LoadingState label="Loading service job reviews and ratings report..." />
            ) : rows.length === 0 ? (
                <div className="rounded-2xl border border-dashed border-slate-300 bg-white p-12 text-center">
                    <svg
                        className="mx-auto h-12 w-12 text-slate-400"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        aria-hidden="true"
                    >
                        <path
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            strokeWidth={1.5}
                            d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a.75.75 0 01-.874-.95 4.394 4.394 0 00.373-1.61A8.204 8.204 0 013 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z"
                        />
                    </svg>
                    <h3 className="mt-3 text-sm font-semibold text-slate-900">
                        No service job reviews or feedback found
                    </h3>
                    <p className="mt-1 text-sm text-slate-500">
                        {hasActiveFilters
                            ? 'No records match your selected filters. Try broadening the date range or clearing category filters.'
                            : 'No feedback or rating records exist in the system yet.'}
                    </p>
                    {hasActiveFilters && (
                        <div className="mt-4">
                            <Button type="button" variant="primary" onClick={handleClearFilters}>
                                Reset Filters
                            </Button>
                        </div>
                    )}
                </div>
            ) : (
                <div className="space-y-4">
                    {/* Desktop Table View */}
                    <div className="hidden overflow-hidden rounded-xl border border-slate-200 bg-white shadow-2xs md:block">
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-slate-200 text-left text-sm">
                                <thead className="bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    <tr>
                                        <th scope="col" className="px-4 py-3">Rating & Category</th>
                                        <th scope="col" className="px-4 py-3">Service Job</th>
                                        <th scope="col" className="px-4 py-3">Customer & Vehicle</th>
                                        <th scope="col" className="px-4 py-3">Feedback Details</th>
                                        <th scope="col" className="px-4 py-3">Author & Visibility</th>
                                        <th scope="col" className="px-4 py-3">Submitted</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100 bg-white">
                                    {rows.map((row) => (
                                        <TableRow key={row.id} row={row} />
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {/* Mobile Card List */}
                    <div className="space-y-3 md:hidden">
                        {rows.map((row) => (
                            <MobileCard key={row.id} row={row} />
                        ))}
                    </div>

                    {/* Pagination Bar */}
                    {meta && meta.last_page > 1 && (
                        <div className="flex items-center justify-between rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-2xs">
                            <div className="text-xs text-slate-500">
                                Showing <span className="font-semibold text-slate-900">{meta.from}</span> to{' '}
                                <span className="font-semibold text-slate-900">{meta.to}</span> of{' '}
                                <span className="font-semibold text-slate-900">{meta.total}</span> records
                            </div>
                            <div className="flex items-center gap-2">
                                <Button
                                    type="button"
                                    variant="secondary"
                                    size="sm"
                                    disabled={meta.current_page <= 1}
                                    onClick={() => setFilters((prev) => ({ ...prev, page: Math.max(1, (prev.page ?? 1) - 1) }))}
                                >
                                    Previous
                                </Button>
                                <span className="text-xs font-medium text-slate-600">
                                    Page {meta.current_page} of {meta.last_page}
                                </span>
                                <Button
                                    type="button"
                                    variant="secondary"
                                    size="sm"
                                    disabled={meta.current_page >= meta.last_page}
                                    onClick={() => setFilters((prev) => ({ ...prev, page: Math.min(meta.last_page, (prev.page ?? 1) + 1) }))}
                                >
                                    Next
                                </Button>
                            </div>
                        </div>
                    )}
                </div>
            )}
        </div>
    );
}

function TableRow({ row }: { row: ServiceJobFeedbackReportRow }) {
    const [expanded, setExpanded] = useState(false);
    const isLongComment = row.comment.length > 120;

    return (
        <tr className="hover:bg-slate-50/70 transition-colors">
            {/* Rating & Category */}
            <td className="whitespace-nowrap px-4 py-3.5 align-top">
                <div className="space-y-1">
                    <FeedbackCategoryBadge type={row.feedback_type} label={row.feedback_type_label} />
                    <div>
                        {row.rating !== null ? (
                            <span className="inline-flex items-center gap-1 rounded bg-amber-50 px-2 py-0.5 text-xs font-semibold text-amber-800 ring-1 ring-amber-600/20">
                                <span>★ {row.rating}</span>
                                <span className="text-amber-500 text-[10px]">{'★'.repeat(row.rating)}</span>
                            </span>
                        ) : (
                            <span className="inline-flex items-center rounded bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-500">
                                Not Rated
                            </span>
                        )}
                    </div>
                </div>
            </td>

            {/* Service Job */}
            <td className="px-4 py-3.5 align-top">
                <div className="space-y-1">
                    <Link
                        to={`/vehicle-service/jobs/${row.job_id}`}
                        className="font-medium text-blue-600 hover:text-blue-800 hover:underline inline-flex items-center gap-1"
                    >
                        <span>{row.job_number}</span>
                    </Link>
                    <div className="text-xs text-slate-500">
                        Date: {formatDate(row.job_date)}
                    </div>
                    <div className="flex items-center gap-1.5 pt-0.5">
                        <span className="inline-flex items-center rounded-md bg-slate-100 px-1.5 py-0.5 text-[11px] font-medium text-slate-600">
                            {row.job_type_name}
                        </span>
                        <StatusBadge status={row.job_status} />
                    </div>
                </div>
            </td>

            {/* Customer & Vehicle */}
            <td className="px-4 py-3.5 align-top">
                <div className="space-y-1">
                    <div className="font-medium text-slate-900">{row.customer_name}</div>
                    {row.customer_phone && (
                        <div className="text-xs text-slate-500">{row.customer_phone}</div>
                    )}
                    <div className="text-xs font-medium text-slate-700">
                        {row.vehicle_label}
                    </div>
                </div>
            </td>

            {/* Feedback Details */}
            <td className="px-4 py-3.5 align-top max-w-md">
                <div className="space-y-1">
                    {row.title && (
                        <div className="font-semibold text-slate-900 text-xs sm:text-sm">
                            {row.title}
                        </div>
                    )}
                    <p className="text-xs text-slate-600 whitespace-pre-wrap leading-relaxed">
                        {expanded || !isLongComment ? row.comment : `${row.comment.slice(0, 120)}...`}
                    </p>
                    {isLongComment && (
                        <button
                            type="button"
                            onClick={() => setExpanded(!expanded)}
                            className="text-xs font-medium text-blue-600 hover:text-blue-800 hover:underline"
                        >
                            {expanded ? 'Show less' : 'Read full comment'}
                        </button>
                    )}
                </div>
            </td>

            {/* Author & Visibility */}
            <td className="whitespace-nowrap px-4 py-3.5 align-top">
                <div className="space-y-1">
                    <div className="text-xs font-medium text-slate-900">{row.author_name}</div>
                    <div>
                        {row.is_customer_visible ? (
                            <span className="inline-flex items-center rounded-md bg-emerald-50 px-2 py-0.5 text-[11px] font-medium text-emerald-700 ring-1 ring-emerald-600/20">
                                Customer Visible
                            </span>
                        ) : (
                            <span className="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-600">
                                Internal Only
                            </span>
                        )}
                    </div>
                </div>
            </td>

            {/* Submitted Date */}
            <td className="whitespace-nowrap px-4 py-3.5 align-top text-xs text-slate-500">
                {row.created_at_formatted || formatDate(row.created_at)}
            </td>
        </tr>
    );
}

function MobileCard({ row }: { row: ServiceJobFeedbackReportRow }) {
    const [expanded, setExpanded] = useState(false);
    const isLongComment = row.comment.length > 120;

    return (
        <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-2xs space-y-3">
            <div className="flex items-start justify-between gap-2">
                <div className="space-y-1">
                    <div className="flex items-center gap-1.5 flex-wrap">
                        <FeedbackCategoryBadge type={row.feedback_type} label={row.feedback_type_label} />
                        {row.rating !== null && (
                            <span className="inline-flex items-center rounded bg-amber-50 px-2 py-0.5 text-xs font-semibold text-amber-800">
                                ★ {row.rating}
                            </span>
                        )}
                        {row.is_customer_visible ? (
                            <span className="rounded bg-emerald-50 px-1.5 py-0.5 text-[10px] font-medium text-emerald-700">
                                Public
                            </span>
                        ) : (
                            <span className="rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-medium text-slate-600">
                                Internal
                            </span>
                        )}
                    </div>
                    <Link
                        to={`/vehicle-service/jobs/${row.job_id}`}
                        className="font-medium text-blue-600 hover:underline block text-sm"
                    >
                        {row.job_number} — {row.job_type_name}
                    </Link>
                </div>
                <StatusBadge status={row.job_status} />
            </div>

            <div className="text-xs text-slate-600 space-y-0.5 border-t border-slate-100 pt-2">
                <div><span className="font-medium text-slate-800">Customer:</span> {row.customer_name} {row.customer_phone ? `(${row.customer_phone})` : ''}</div>
                <div><span className="font-medium text-slate-800">Vehicle:</span> {row.vehicle_label}</div>
                <div><span className="font-medium text-slate-800">Job Date:</span> {formatDate(row.job_date)}</div>
            </div>

            <div className="bg-slate-50 rounded-lg p-3 text-xs space-y-1">
                {row.title && <div className="font-semibold text-slate-900">{row.title}</div>}
                <p className="text-slate-700 whitespace-pre-wrap">
                    {expanded || !isLongComment ? row.comment : `${row.comment.slice(0, 120)}...`}
                </p>
                {isLongComment && (
                    <button
                        type="button"
                        onClick={() => setExpanded(!expanded)}
                        className="text-xs font-medium text-blue-600 hover:underline"
                    >
                        {expanded ? 'Show less' : 'Read full comment'}
                    </button>
                )}
            </div>

            <div className="flex items-center justify-between text-xs text-slate-400 pt-1">
                <span>By: <span className="text-slate-600 font-medium">{row.author_name}</span></span>
                <span>{row.created_at_formatted || formatDate(row.created_at)}</span>
            </div>
        </div>
    );
}

function FeedbackCategoryBadge({ type, label }: { type: ServiceJobFeedbackType; label: string }) {
    switch (type) {
        case 'review':
            return (
                <span className="inline-flex items-center rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700 ring-1 ring-emerald-600/20">
                    {label}
                </span>
            );
        case 'internal_note':
            return (
                <span className="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700 ring-1 ring-slate-300">
                    {label}
                </span>
            );
        case 'customer_feedback':
            return (
                <span className="inline-flex items-center rounded-md bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-700 ring-1 ring-blue-600/20">
                    {label}
                </span>
            );
        case 'complaint':
            return (
                <span className="inline-flex items-center rounded-md bg-rose-50 px-2 py-0.5 text-xs font-medium text-rose-700 ring-1 ring-rose-600/20">
                    {label}
                </span>
            );
        default:
            return (
                <span className="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700">
                    {label}
                </span>
            );
    }
}

function StarVisualizer({ rating }: { rating: number }) {
    const fullStars = Math.floor(rating);
    const hasHalfStar = rating - fullStars >= 0.3 && rating - fullStars < 0.8;
    const extraFull = rating - fullStars >= 0.8 ? 1 : 0;
    const totalFilled = fullStars + extraFull;
    const totalEmpty = 5 - totalFilled - (hasHalfStar ? 1 : 0);

    return (
        <div className="flex items-center gap-0.5 text-amber-400">
            {Array.from({ length: totalFilled }).map((_, i) => (
                <span key={`filled-${i}`} className="text-base">★</span>
            ))}
            {hasHalfStar && <span className="text-base text-amber-400">★</span>}
            {Array.from({ length: Math.max(0, totalEmpty) }).map((_, i) => (
                <span key={`empty-${i}`} className="text-base text-slate-300">★</span>
            ))}
        </div>
    );
}
