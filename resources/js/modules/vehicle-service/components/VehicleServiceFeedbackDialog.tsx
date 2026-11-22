/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

import { useState, type FormEvent } from 'react';
import { fieldError, toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { Modal } from '@/shared/components/Modal';
import { Select } from '@/shared/components/Select';
import { Textarea } from '@/shared/components/Textarea';
import { createVehicleServiceFeedback, updateVehicleServiceFeedback } from '../vehicleServiceApi';
import type {
    VehicleServiceFeedbackType,
    VehicleServiceJobFeedback,
    VehicleServiceJobFeedbackPayload,
} from '../vehicleServiceTypes';

interface VehicleServiceFeedbackDialogProps {
    open: boolean;
    jobId: number;
    expectedJobVersion?: number;
    feedback?: VehicleServiceJobFeedback | null;
    onClose: () => void;
    onSaved: (feedback: VehicleServiceJobFeedback) => void;
}

const FEEDBACK_TYPE_OPTIONS = [
    { value: 'review', label: 'Customer Review' },
    { value: 'internal_note', label: 'Internal Staff Note' },
    { value: 'customer_feedback', label: 'General Customer Feedback' },
    { value: 'complaint', label: 'Customer Complaint' },
];

const RATING_LABELS: Record<number, string> = {
    1: '1 - Poor',
    2: '2 - Fair',
    3: '3 - Good',
    4: '4 - Very Good',
    5: '5 - Excellent',
};

export function VehicleServiceFeedbackDialog({
    open,
    jobId,
    expectedJobVersion,
    feedback,
    onClose,
    onSaved,
}: VehicleServiceFeedbackDialogProps) {
    const isEditing = Boolean(feedback);

    const [type, setType] = useState<VehicleServiceFeedbackType>(feedback?.feedback_type ?? 'review');
    const [rating, setRating] = useState<number | null>(feedback?.rating ?? (feedback ? null : 5));
    const [hoverRating, setHoverRating] = useState<number | null>(null);
    const [title, setTitle] = useState(feedback?.title ?? '');
    const [comment, setComment] = useState(feedback?.comment ?? '');
    const [authorName, setAuthorName] = useState(feedback?.author_name ?? '');
    const [isCustomerVisible, setIsCustomerVisible] = useState<boolean>(
        feedback ? feedback.is_customer_visible : type !== 'internal_note',
    );
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);

    const handleTypeChange = (newType: string) => {
        const parsedType = newType as VehicleServiceFeedbackType;
        setType(parsedType);
        if (parsedType === 'internal_note') {
            setIsCustomerVisible(false);
            if (!feedback) setRating(null);
        } else if (parsedType === 'review') {
            setIsCustomerVisible(true);
            if (rating === null) setRating(5);
        } else {
            setIsCustomerVisible(true);
        }
    };

    const handleSubmit = async (event: FormEvent) => {
        event.preventDefault();
        setSaving(true);
        setError(null);

        const payload: VehicleServiceJobFeedbackPayload = {
            feedback_type: type,
            comment: comment.trim(),
            rating: rating !== null ? rating : null,
            title: title.trim() || null,
            author_name: authorName.trim() || null,
            is_customer_visible: isCustomerVisible,
            expected_version: feedback?.row_version,
            expected_job_version: expectedJobVersion,
        };

        try {
            let saved: VehicleServiceJobFeedback;
            if (isEditing && feedback) {
                saved = await updateVehicleServiceFeedback(jobId, feedback.id, payload);
            } else {
                saved = await createVehicleServiceFeedback(jobId, payload);
            }
            onSaved(saved);
            onClose();
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setSaving(false);
        }
    };

    const displayedRating = hoverRating !== null ? hoverRating : rating;

    return (
        <Modal
            open={open}
            title={isEditing ? 'Edit Feedback / Review' : 'Add Service Job Feedback'}
            onClose={onClose}
            closeDisabled={saving}
        >
            <form onSubmit={handleSubmit} className="space-y-4 p-6">
                <ErrorAlert error={error} />

                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <Select
                        label="Feedback Category"
                        value={type}
                        onChange={(e) => handleTypeChange(e.target.value)}
                        options={FEEDBACK_TYPE_OPTIONS}
                        error={fieldError(error, 'feedback_type')}
                        required
                    />

                    <Input
                        label="Author / Submitter Name"
                        placeholder="e.g. John Doe, Customer, Advisor"
                        value={authorName}
                        onChange={(e) => setAuthorName(e.target.value)}
                        error={fieldError(error, 'author_name')}
                        hint="Leave blank to use current logged-in user"
                    />
                </div>

                {/* Interactive Star Rating */}
                <div className="rounded-xl border border-slate-200 bg-slate-50/60 p-4">
                    <div className="flex items-center justify-between">
                        <label className="block text-sm font-medium text-slate-700">
                            Star Rating {type === 'review' ? '(Recommended for reviews)' : '(Optional)'}
                        </label>
                        {rating !== null && (
                            <button
                                type="button"
                                onClick={() => setRating(null)}
                                className="text-xs font-medium text-slate-500 hover:text-rose-600 transition-colors"
                            >
                                Clear rating
                            </button>
                        )}
                    </div>

                    <div className="mt-2 flex items-center gap-2">
                        <div className="flex items-center gap-1" role="radiogroup" aria-label="Rating scale 1 to 5 stars">
                            {[1, 2, 3, 4, 5].map((star) => {
                                const isFilled = displayedRating !== null && star <= displayedRating;
                                return (
                                    <button
                                        key={star}
                                        type="button"
                                        aria-label={`${star} star${star > 1 ? 's' : ''}`}
                                        role="radio"
                                        aria-checked={rating === star}
                                        onClick={() => setRating(star)}
                                        onMouseEnter={() => setHoverRating(star)}
                                        onMouseLeave={() => setHoverRating(null)}
                                        className="p-1 text-slate-300 transition-transform hover:scale-110 focus:outline-none focus:ring-2 focus:ring-amber-400 rounded"
                                    >
                                        <svg
                                            className={`h-7 w-7 transition-colors ${
                                                isFilled ? 'text-amber-400 fill-amber-400' : 'text-slate-300'
                                            }`}
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            strokeWidth={1.5}
                                            fill={isFilled ? 'currentColor' : 'none'}
                                        >
                                            <path
                                                strokeLinecap="round"
                                                strokeLinejoin="round"
                                                d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"
                                            />
                                        </svg>
                                    </button>
                                );
                            })}
                        </div>
                        <span className="text-sm font-medium text-slate-600">
                            {displayedRating !== null ? RATING_LABELS[displayedRating] : 'No rating selected'}
                        </span>
                    </div>
                    {fieldError(error, 'rating') && (
                        <p className="mt-1 text-xs text-rose-600">{fieldError(error, 'rating')}</p>
                    )}
                </div>

                <Input
                    label="Subject / Title"
                    placeholder="e.g. Prompt service, Engine running smoothly, Follow-up notes"
                    value={title}
                    onChange={(e) => setTitle(e.target.value)}
                    error={fieldError(error, 'title')}
                />

                <Textarea
                    label="Feedback / Comments"
                    placeholder="Write detailed notes, observations, or customer remarks..."
                    value={comment}
                    onChange={(e) => setComment(e.target.value)}
                    error={fieldError(error, 'comment')}
                    rows={4}
                    required
                />

                <div className="flex items-start gap-2.5 pt-1">
                    <input
                        type="checkbox"
                        id="is_customer_visible"
                        checked={isCustomerVisible}
                        onChange={(e) => setIsCustomerVisible(e.target.checked)}
                        className="mt-0.5 h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                    />
                    <label htmlFor="is_customer_visible" className="text-sm text-slate-700 select-none">
                        <span className="font-medium text-slate-900">Visible to customer</span>
                        <span className="block text-xs text-slate-500">
                            When enabled, this feedback or review can appear on customer-facing printouts and job summaries.
                        </span>
                    </label>
                </div>

                <div className="flex justify-end gap-2 border-t border-slate-100 pt-4">
                    <Button type="button" variant="ghost" onClick={onClose} disabled={saving}>
                        Cancel
                    </Button>
                    <Button type="submit" variant="primary" disabled={saving}>
                        {saving ? 'Saving...' : isEditing ? 'Save Changes' : 'Submit Feedback'}
                    </Button>
                </div>
            </form>
        </Modal>
    );
}
