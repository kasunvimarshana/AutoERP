/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

import { useEffect, useState } from 'react';
import { hasPermission } from '@/modules/auth/accessControl';
import { useAuth } from '@/modules/auth/AuthProvider';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ConfirmDialog } from '@/shared/components/ConfirmDialog';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Select } from '@/shared/components/Select';
import { useApi } from '@/shared/hooks/useApi';
import { formatDate } from '@/shared/utils/formatDate';
import { deleteVehicleServiceFeedback, listVehicleServiceFeedbacks } from '../vehicleServiceApi';
import { vehicleServicePermissions } from '../vehicleServicePermissions';
import type {
    VehicleServiceFeedbackFilterParams,
    VehicleServiceFeedbackType,
    VehicleServiceJobFeedback,
} from '../vehicleServiceTypes';
import { VehicleServiceFeedbackDialog } from './VehicleServiceFeedbackDialog';

interface VehicleServiceFeedbackTabProps {
    jobId: number;
    expectedVersion?: number;
    onChanged?: (nextVersion: number) => void;
}

const TYPE_FILTER_OPTIONS = [
    { value: '', label: 'All Categories' },
    { value: 'review', label: 'Customer Reviews' },
    { value: 'internal_note', label: 'Internal Notes' },
    { value: 'customer_feedback', label: 'General Feedback' },
    { value: 'complaint', label: 'Complaints' },
];

const RATING_FILTER_OPTIONS = [
    { value: '', label: 'All Ratings' },
    { value: '5', label: '5 Stars only' },
    { value: '4', label: '4 Stars only' },
    { value: '3', label: '3 Stars only' },
    { value: '2', label: '2 Stars only' },
    { value: '1', label: '1 Star only' },
];

export default function VehicleServiceFeedbackTab({
    jobId,
    expectedVersion,
    onChanged,
}: VehicleServiceFeedbackTabProps) {
    const auth = useAuth();
    const canManage = hasPermission(auth, vehicleServicePermissions.feedbackManage);

    // Filters
    const [typeFilter, setTypeFilter] = useState<string>('');
    const [ratingFilter, setRatingFilter] = useState<string>('');
    const [customerVisibleOnly, setCustomerVisibleOnly] = useState<boolean>(false);
    const [searchQuery, setSearchQuery] = useState<string>('');
    const [debouncedSearch, setDebouncedSearch] = useState<string>('');

    // Dialog state
    const [dialogOpen, setDialogOpen] = useState(false);
    const [editingFeedback, setEditingFeedback] = useState<VehicleServiceJobFeedback | null>(null);

    // Delete state
    const [deleteTarget, setDeleteTarget] = useState<VehicleServiceJobFeedback | null>(null);
    const [deleting, setDeleting] = useState(false);
    const [actionError, setActionError] = useState<ApiError | null>(null);

    // Debounce search
    useEffect(() => {
        const timer = setTimeout(() => {
            setDebouncedSearch(searchQuery);
        }, 300);
        return () => clearTimeout(timer);
    }, [searchQuery]);

    const result = useApi(
        (signal) => {
            const params: VehicleServiceFeedbackFilterParams = {
                feedback_type: typeFilter ? (typeFilter as VehicleServiceFeedbackType) : undefined,
                rating: ratingFilter ? Number(ratingFilter) : undefined,
                customer_visible_only: customerVisibleOnly ? true : undefined,
                search: debouncedSearch.trim() || undefined,
            };
            return listVehicleServiceFeedbacks(jobId, params, signal);
        },
        [jobId, typeFilter, ratingFilter, customerVisibleOnly, debouncedSearch],
    );

    const feedbacks = result.data?.data ?? [];
    const summary = result.data?.meta.summary ?? null;
    const loading = result.loading;
    const error = actionError ?? result.error;

    const handleCreateClick = () => {
        setEditingFeedback(null);
        setDialogOpen(true);
    };

    const handleEditClick = (item: VehicleServiceJobFeedback) => {
        setEditingFeedback(item);
        setDialogOpen(true);
    };

    const handleSaved = (_saved: VehicleServiceJobFeedback) => {
        result.reload();
        if (expectedVersion !== undefined && onChanged) {
            onChanged(expectedVersion + 1);
        }
    };

    const handleDeleteConfirm = async () => {
        if (!deleteTarget) return;
        setDeleting(true);
        setActionError(null);
        try {
            await deleteVehicleServiceFeedback(jobId, deleteTarget.id, deleteTarget.row_version, expectedVersion);
            setDeleteTarget(null);
            result.reload();
            if (expectedVersion !== undefined && onChanged) {
                onChanged(expectedVersion + 1);
            }
        } catch (requestError) {
            setActionError(toApiError(requestError));
        } finally {
            setDeleting(false);
        }
    };

    const hasActiveFilters = Boolean(typeFilter || ratingFilter || customerVisibleOnly || searchQuery);

    const clearFilters = () => {
        setTypeFilter('');
        setRatingFilter('');
        setCustomerVisibleOnly(false);
        setSearchQuery('');
    };

    const totalRatings = summary?.ratings_count ?? 0;

    return (
        <div className="space-y-6">
            <ErrorAlert error={error} />

            {/* Rating Summary & Overview Card */}
            {summary && (
                <div className="rounded-2xl border border-slate-200 bg-linear-to-b from-slate-50/70 to-white p-5 shadow-xs">
                    <div className="grid grid-cols-1 gap-6 lg:grid-cols-12 lg:items-center">
                        {/* Overall Score */}
                        <div className="flex flex-col items-center justify-center border-b border-slate-200/80 pb-5 text-center lg:col-span-3 lg:border-b-0 lg:border-r lg:pb-0 lg:pr-5">
                            <span className="text-4xl font-extrabold tracking-tight text-slate-900">
                                {summary.average_rating !== null ? summary.average_rating.toFixed(1) : '-'}
                            </span>
                            <div className="mt-1 flex items-center gap-1 text-amber-400">
                                {[1, 2, 3, 4, 5].map((star) => {
                                    const avg = summary.average_rating ?? 0;
                                    const isFilled = star <= Math.round(avg);
                                    return (
                                        <svg
                                            key={star}
                                            className={`h-5 w-5 ${isFilled ? 'fill-amber-400 text-amber-400' : 'text-slate-200 fill-slate-200'}`}
                                            viewBox="0 0 20 20"
                                        >
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                        </svg>
                                    );
                                })}
                            </div>
                            <span className="mt-1.5 text-xs text-slate-500">
                                {totalRatings > 0
                                    ? `Based on ${totalRatings} rating${totalRatings > 1 ? 's' : ''}`
                                    : 'No ratings submitted yet'}
                            </span>
                        </div>

                        {/* Star Distribution Progress Bars */}
                        <div className="space-y-1.5 lg:col-span-5">
                            {[5, 4, 3, 2, 1].map((star) => {
                                const count = summary.rating_breakdown?.[star] ?? 0;
                                const pct = totalRatings > 0 ? Math.round((count / totalRatings) * 100) : 0;
                                const isSelected = ratingFilter === String(star);

                                return (
                                    <button
                                        key={star}
                                        type="button"
                                        onClick={() => setRatingFilter(isSelected ? '' : String(star))}
                                        className={`group flex w-full items-center gap-2 text-xs text-slate-600 transition-colors rounded-md px-1 py-0.5 hover:bg-slate-100 ${
                                            isSelected ? 'bg-amber-50 font-semibold text-amber-900 ring-1 ring-amber-300' : ''
                                        }`}
                                    >
                                        <span className="w-12 text-left font-medium">{star} stars</span>
                                        <div className="h-2 flex-1 overflow-hidden rounded-full bg-slate-200">
                                            <div
                                                className="h-full rounded-full bg-amber-400 transition-all duration-300 group-hover:bg-amber-500"
                                                style={{ width: `${pct}%` }}
                                            />
                                        </div>
                                        <span className="w-10 text-right text-slate-500">{count}</span>
                                    </button>
                                );
                            })}
                        </div>

                        {/* Breakdown Badges & Quick Action */}
                        <div className="flex flex-col gap-3 lg:col-span-4 lg:items-end">
                            <div className="flex flex-wrap gap-1.5" role="group" aria-label="Filter by category">
                                <button
                                    type="button"
                                    onClick={() => setTypeFilter(typeFilter === 'review' ? '' : 'review')}
                                    className={`inline-flex items-center gap-1 rounded-md px-2 py-1 text-xs font-medium transition-colors cursor-pointer ${
                                        typeFilter === 'review'
                                            ? 'bg-emerald-600 text-white shadow-xs'
                                            : 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20 hover:bg-emerald-100'
                                    }`}
                                >
                                    Reviews: <strong>{summary.reviews_count}</strong>
                                </button>
                                <button
                                    type="button"
                                    onClick={() => setTypeFilter(typeFilter === 'internal_note' ? '' : 'internal_note')}
                                    className={`inline-flex items-center gap-1 rounded-md px-2 py-1 text-xs font-medium transition-colors cursor-pointer ${
                                        typeFilter === 'internal_note'
                                            ? 'bg-slate-700 text-white shadow-xs'
                                            : 'bg-slate-100 text-slate-700 ring-1 ring-slate-300 hover:bg-slate-200'
                                    }`}
                                >
                                    Notes: <strong>{summary.notes_count}</strong>
                                </button>
                                <button
                                    type="button"
                                    onClick={() => setTypeFilter(typeFilter === 'customer_feedback' ? '' : 'customer_feedback')}
                                    className={`inline-flex items-center gap-1 rounded-md px-2 py-1 text-xs font-medium transition-colors cursor-pointer ${
                                        typeFilter === 'customer_feedback'
                                            ? 'bg-blue-600 text-white shadow-xs'
                                            : 'bg-blue-50 text-blue-700 ring-1 ring-blue-600/20 hover:bg-blue-100'
                                    }`}
                                >
                                    Feedback: <strong>{summary.customer_feedback_count}</strong>
                                </button>
                                <button
                                    type="button"
                                    onClick={() => setTypeFilter(typeFilter === 'complaint' ? '' : 'complaint')}
                                    className={`inline-flex items-center gap-1 rounded-md px-2 py-1 text-xs font-medium transition-colors cursor-pointer ${
                                        typeFilter === 'complaint'
                                            ? 'bg-rose-600 text-white shadow-xs'
                                            : 'bg-rose-50 text-rose-700 ring-1 ring-rose-600/20 hover:bg-rose-100'
                                    }`}
                                >
                                    Complaints: <strong>{summary.complaints_count}</strong>
                                </button>
                            </div>

                            {canManage && (
                                <Button
                                    type="button"
                                    variant="primary"
                                    onClick={handleCreateClick}
                                    className="mt-2"
                                >
                                    + Add Review or Note
                                </Button>
                            )}
                        </div>
                    </div>
                </div>
            )}

            {/* Filter & Search Bar */}
            <div className="flex flex-col gap-3 rounded-xl border border-slate-200 bg-white p-3 shadow-2xs sm:flex-row sm:items-center sm:justify-between">
                <div className="flex flex-wrap items-center gap-2.5">
                    <div className="w-44">
                        <Select
                            value={typeFilter}
                            onChange={(e) => setTypeFilter(e.target.value)}
                            options={TYPE_FILTER_OPTIONS}
                            placeholder={null}
                        />
                    </div>
                    <div className="w-40">
                        <Select
                            value={ratingFilter}
                            onChange={(e) => setRatingFilter(e.target.value)}
                            options={RATING_FILTER_OPTIONS}
                            placeholder={null}
                        />
                    </div>
                    <label className="flex items-center gap-1.5 text-xs font-medium text-slate-600 select-none cursor-pointer">
                        <input
                            type="checkbox"
                            checked={customerVisibleOnly}
                            onChange={(e) => setCustomerVisibleOnly(e.target.checked)}
                            className="h-3.5 w-3.5 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                        />
                        <span>Customer visible only</span>
                    </label>
                    {hasActiveFilters && (
                        <Button type="button" variant="ghost" size="sm" onClick={clearFilters}>
                            Clear filters
                        </Button>
                    )}
                </div>

                <div className="w-full sm:w-64">
                    <Input
                        placeholder="Search comments, titles, authors..."
                        value={searchQuery}
                        onChange={(e) => setSearchQuery(e.target.value)}
                    />
                </div>
            </div>

            {/* List / Timeline of Feedbacks */}
            {loading && feedbacks.length === 0 ? (
                <LoadingState label="Loading feedbacks..." />
            ) : feedbacks.length === 0 ? (
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
                        {hasActiveFilters ? 'No matching feedback found' : 'No feedback or reviews yet'}
                    </h3>
                    <p className="mt-1 text-sm text-slate-500">
                        {hasActiveFilters
                            ? 'Try clearing your category, rating, or search filters.'
                            : 'Add the first customer review, internal note, or service feedback for this job.'}
                    </p>
                    {canManage && !hasActiveFilters && (
                        <div className="mt-4">
                            <Button type="button" variant="primary" onClick={handleCreateClick}>
                                + Add First Review or Note
                            </Button>
                        </div>
                    )}
                </div>
            ) : (
                <div className="space-y-3.5">
                    {feedbacks.map((item) => (
                        <div
                            key={item.id}
                            className="rounded-xl border border-slate-200 bg-white p-4 shadow-2xs transition-shadow hover:shadow-xs"
                        >
                            <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                <div className="flex flex-wrap items-center gap-2">
                                    <FeedbackTypeBadge type={item.feedback_type} label={item.feedback_type_label} />

                                    {item.rating !== null && item.rating !== undefined && (
                                        <div className="flex items-center gap-0.5 text-amber-400">
                                            {[1, 2, 3, 4, 5].map((star) => (
                                                <svg
                                                    key={star}
                                                    className={`h-4 w-4 ${
                                                        star <= (item.rating ?? 0)
                                                            ? 'fill-amber-400 text-amber-400'
                                                            : 'fill-slate-200 text-slate-200'
                                                    }`}
                                                    viewBox="0 0 20 20"
                                                >
                                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                                </svg>
                                            ))}
                                            <span className="ml-1 text-xs font-bold text-slate-700">
                                                {item.rating}/5
                                            </span>
                                        </div>
                                    )}

                                    <span
                                        className={`inline-flex items-center rounded-full px-2 py-0.5 text-2xs font-medium ${
                                            item.is_customer_visible
                                                ? 'bg-blue-50 text-blue-700 ring-1 ring-blue-600/20'
                                                : 'bg-slate-100 text-slate-600 ring-1 ring-slate-300'
                                        }`}
                                    >
                                        {item.is_customer_visible ? 'Customer Visible' : 'Internal Only'}
                                    </span>
                                </div>

                                <div className="flex items-center gap-3 text-xs text-slate-500">
                                    <span>
                                        By <strong className="text-slate-800">{item.author_name}</strong>
                                    </span>
                                    <span>&bull;</span>
                                    <span>{formatDate(item.created_at)}</span>
                                    {item.updated_at && item.created_at && item.updated_at !== item.created_at && (
                                        <span className="text-2xs italic text-slate-400" title={`Edited on ${formatDate(item.updated_at)}`}>
                                            (edited)
                                        </span>
                                    )}
                                </div>
                            </div>

                            {item.title && (
                                <h4 className="mt-2 text-sm font-semibold text-slate-900">{item.title}</h4>
                            )}

                            {item.comment ? (
                                <p className="mt-1.5 whitespace-pre-line text-sm leading-relaxed text-slate-700">
                                    {item.comment}
                                </p>
                            ) : !item.title ? (
                                <p className="mt-1 text-xs italic text-slate-400">No additional comments provided.</p>
                            ) : null}

                            {canManage && (
                                <div className="mt-3 flex justify-end gap-2 border-t border-slate-100 pt-2.5">
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => handleEditClick(item)}
                                    >
                                        Edit
                                    </Button>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        className="text-rose-600 hover:text-rose-700 hover:bg-rose-50"
                                        onClick={() => setDeleteTarget(item)}
                                    >
                                        Delete
                                    </Button>
                                </div>
                            )}
                        </div>
                    ))}
                </div>
            )}

            {/* Modal Dialog for Add / Edit */}
            {dialogOpen && (
                <VehicleServiceFeedbackDialog
                    key={editingFeedback?.id ?? 'create'}
                    open={dialogOpen}
                    jobId={jobId}
                    expectedJobVersion={expectedVersion}
                    feedback={editingFeedback}
                    onClose={() => setDialogOpen(false)}
                    onSaved={handleSaved}
                />
            )}

            {/* Confirm Dialog for Delete */}
            <ConfirmDialog
                open={Boolean(deleteTarget)}
                title="Delete Feedback"
                message={
                    <div className="space-y-3">
                        <ErrorAlert error={actionError} inline />
                        <p>
                            Are you sure you want to delete this {deleteTarget?.feedback_type_label?.toLowerCase() ?? 'feedback'}?
                            This action cannot be undone.
                        </p>
                    </div>
                }
                confirmLabel="Delete"
                cancelLabel="Cancel"
                danger
                loading={deleting}
                onCancel={() => {
                    setDeleteTarget(null);
                    setActionError(null);
                }}
                onConfirm={handleDeleteConfirm}
            />
        </div>
    );
}

function FeedbackTypeBadge({ type, label }: { type: VehicleServiceFeedbackType; label: string }) {
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
