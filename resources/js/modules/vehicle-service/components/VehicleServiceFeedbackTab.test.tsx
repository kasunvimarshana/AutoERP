/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

import { render, screen, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import type {
    VehicleServiceFeedbackListResult,
    VehicleServiceJobFeedback,
} from '../vehicleServiceTypes';
import VehicleServiceFeedbackTab from './VehicleServiceFeedbackTab';

const apiMocks = vi.hoisted(() => ({
    listVehicleServiceFeedbacks: vi.fn(),
    createVehicleServiceFeedback: vi.fn(),
    updateVehicleServiceFeedback: vi.fn(),
    deleteVehicleServiceFeedback: vi.fn(),
}));

vi.mock('../vehicleServiceApi', () => apiMocks);

vi.mock('@/modules/auth/AuthProvider', () => ({
    useAuth: () => ({
        roles: ['admin'],
        permissions: ['vehicle_service.feedback.manage', 'vehicle_service.feedback.view'],
        permissionsLoaded: true,
    }),
}));

const sampleFeedbackReview: VehicleServiceJobFeedback = {
    id: 101,
    row_version: 1,
    tenant_id: 1,
    vehicle_service_job_id: 7,
    feedback_type: 'review',
    feedback_type_label: 'Customer Review',
    rating: 5,
    title: 'Flawless engine tuning',
    comment: 'Engine runs very smoothly and fuel efficiency improved noticeably.',
    is_customer_visible: true,
    author_name: 'David Silva',
    created_at: '2026-11-20T10:30:00Z',
    updated_at: '2026-11-20T10:30:00Z',
};

const sampleFeedbackNote: VehicleServiceJobFeedback = {
    id: 102,
    row_version: 1,
    tenant_id: 1,
    vehicle_service_job_id: 7,
    feedback_type: 'internal_note',
    feedback_type_label: 'Internal Staff Note',
    rating: null,
    title: 'Brake pads advisory',
    comment: 'Front brake pads are at 40%. Advise replacement on next visit.',
    is_customer_visible: false,
    author_name: 'Kasun Technician',
    created_at: '2026-11-20T11:00:00Z',
    updated_at: '2026-11-20T11:00:00Z',
};

const sampleListResult: VehicleServiceFeedbackListResult = {
    data: [sampleFeedbackReview, sampleFeedbackNote],
    meta: {
        summary: {
            average_rating: 5.0,
            total_count: 2,
            ratings_count: 1,
            reviews_count: 1,
            notes_count: 1,
            customer_feedback_count: 0,
            complaints_count: 0,
            rating_breakdown: {
                5: 1,
                4: 0,
                3: 0,
                2: 0,
                1: 0,
            },
        },
    },
};

describe('VehicleServiceFeedbackTab', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        apiMocks.listVehicleServiceFeedbacks.mockResolvedValue(sampleListResult);
    });

    it('renders rating summary card, badges, and feedback items', async () => {
        render(<VehicleServiceFeedbackTab jobId={7} expectedVersion={3} />);

        await waitFor(() => {
            expect(screen.getByText('5.0')).toBeInTheDocument();
            expect(screen.getByText('Based on 1 rating')).toBeInTheDocument();
        });

        // Check summary badges
        expect(screen.getByText('Reviews:')).toBeInTheDocument();
        expect(screen.getByText('Notes:')).toBeInTheDocument();

        // Check feedback items
        expect(screen.getByText('Flawless engine tuning')).toBeInTheDocument();
        expect(screen.getByText('David Silva')).toBeInTheDocument();
        expect(screen.getByText('5/5')).toBeInTheDocument();
        expect(screen.getByText('Brake pads advisory')).toBeInTheDocument();
        expect(screen.getByText('Kasun Technician')).toBeInTheDocument();
        expect(screen.getByText('Internal Only')).toBeInTheDocument();
    });

    it('filters feedback items by category when category filter changes', async () => {
        const user = userEvent.setup();
        render(<VehicleServiceFeedbackTab jobId={7} expectedVersion={3} />);

        await waitFor(() => {
            expect(screen.getByText('Flawless engine tuning')).toBeInTheDocument();
        });

        const categorySelect = screen.getByDisplayValue('All Categories');
        await user.selectOptions(categorySelect, 'internal_note');

        await waitFor(() => {
            expect(apiMocks.listVehicleServiceFeedbacks).toHaveBeenCalledWith(
                7,
                expect.objectContaining({
                    feedback_type: 'internal_note',
                }),
                expect.any(AbortSignal),
            );
        });
    });

    it('filters feedback items by star rating when selecting a star filter', async () => {
        const user = userEvent.setup();
        render(<VehicleServiceFeedbackTab jobId={7} expectedVersion={3} />);

        await waitFor(() => {
            expect(screen.getByText('5.0')).toBeInTheDocument();
        });

        const ratingSelect = screen.getByDisplayValue('All Ratings');
        await user.selectOptions(ratingSelect, '5');

        await waitFor(() => {
            expect(apiMocks.listVehicleServiceFeedbacks).toHaveBeenCalledWith(
                7,
                expect.objectContaining({
                    rating: 5,
                }),
                expect.any(AbortSignal),
            );
        });
    });

    it('opens create feedback dialog and submits a new review', async () => {
        const user = userEvent.setup();
        const created: VehicleServiceJobFeedback = {
            id: 103,
            row_version: 1,
            tenant_id: 1,
            vehicle_service_job_id: 7,
            feedback_type: 'review',
            feedback_type_label: 'Customer Review',
            rating: 5,
            title: 'Great work',
            comment: 'Very polite and timely service.',
            is_customer_visible: true,
            author_name: 'Sunil Perera',
            created_at: '2026-11-21T09:00:00Z',
        };
        apiMocks.createVehicleServiceFeedback.mockResolvedValue(created);

        const onChanged = vi.fn();
        render(<VehicleServiceFeedbackTab jobId={7} expectedVersion={3} onChanged={onChanged} />);

        await waitFor(() => {
            expect(screen.getByText('+ Add Review or Note')).toBeInTheDocument();
        });

        await user.click(screen.getByText('+ Add Review or Note'));

        expect(screen.getByRole('heading', { name: 'Add Service Job Feedback' })).toBeInTheDocument();

        // Fill out form
        const titleInput = screen.getByPlaceholderText('e.g. Prompt service, Engine running smoothly, Follow-up notes');
        await user.type(titleInput, 'Great work');

        const authorInput = screen.getByPlaceholderText('e.g. John Doe, Customer, Advisor');
        await user.type(authorInput, 'Sunil Perera');

        const commentInput = screen.getByPlaceholderText('Write detailed notes, observations, or customer remarks...');
        await user.type(commentInput, 'Very polite and timely service.');

        await user.click(screen.getByRole('button', { name: 'Submit Feedback' }));

        await waitFor(() => {
            expect(apiMocks.createVehicleServiceFeedback).toHaveBeenCalledWith(
                7,
                expect.objectContaining({
                    feedback_type: 'review',
                    title: 'Great work',
                    author_name: 'Sunil Perera',
                    comment: 'Very polite and timely service.',
                    rating: 5,
                    expected_job_version: 3,
                }),
            );
            expect(onChanged).toHaveBeenCalledWith(4);
        });
    });

    it('submits a new feedback with all optional fields omitted', async () => {
        const user = userEvent.setup();
        const created: VehicleServiceJobFeedback = {
            id: 104,
            row_version: 1,
            tenant_id: 1,
            vehicle_service_job_id: 7,
            feedback_type: 'review',
            feedback_type_label: 'Customer Review',
            rating: 5,
            title: null,
            comment: null,
            is_customer_visible: true,
            author_name: 'Staff',
            created_at: '2026-11-21T09:30:00Z',
        };
        apiMocks.createVehicleServiceFeedback.mockResolvedValue(created);

        render(<VehicleServiceFeedbackTab jobId={7} expectedVersion={3} />);

        await waitFor(() => {
            expect(screen.getByText('+ Add Review or Note')).toBeInTheDocument();
        });

        await user.click(screen.getByText('+ Add Review or Note'));

        // Submit without typing any title, author, or comment
        await user.click(screen.getByRole('button', { name: 'Submit Feedback' }));

        await waitFor(() => {
            expect(apiMocks.createVehicleServiceFeedback).toHaveBeenCalledWith(
                7,
                expect.objectContaining({
                    feedback_type: 'review',
                    title: null,
                    author_name: null,
                    comment: null,
                    rating: 5,
                }),
            );
        });
    });

    it('opens edit dialog and updates existing feedback', async () => {
        const user = userEvent.setup();
        const updated: VehicleServiceJobFeedback = {
            ...sampleFeedbackReview,
            title: 'Updated title review',
            row_version: 2,
        };
        apiMocks.updateVehicleServiceFeedback.mockResolvedValue(updated);

        const onChanged = vi.fn();
        render(<VehicleServiceFeedbackTab jobId={7} expectedVersion={3} onChanged={onChanged} />);

        await waitFor(() => {
            expect(screen.getByText('Flawless engine tuning')).toBeInTheDocument();
        });

        const editButtons = screen.getAllByRole('button', { name: 'Edit' });
        await user.click(editButtons[0]);

        expect(screen.getByRole('heading', { name: 'Edit Feedback / Review' })).toBeInTheDocument();

        const titleInput = screen.getByDisplayValue('Flawless engine tuning');
        await user.clear(titleInput);
        await user.type(titleInput, 'Updated title review');

        await user.click(screen.getByRole('button', { name: 'Save Changes' }));

        await waitFor(() => {
            expect(apiMocks.updateVehicleServiceFeedback).toHaveBeenCalledWith(
                7,
                101,
                expect.objectContaining({
                    title: 'Updated title review',
                    expected_version: 1,
                }),
            );
            expect(onChanged).toHaveBeenCalledWith(4);
        });
    });

    it('opens delete confirm dialog and deletes feedback', async () => {
        const user = userEvent.setup();
        apiMocks.deleteVehicleServiceFeedback.mockResolvedValue(undefined);

        const onChanged = vi.fn();
        render(<VehicleServiceFeedbackTab jobId={7} expectedVersion={3} onChanged={onChanged} />);

        await waitFor(() => {
            expect(screen.getByText('Flawless engine tuning')).toBeInTheDocument();
        });

        const deleteButtons = screen.getAllByRole('button', { name: 'Delete' });
        await user.click(deleteButtons[0]);

        expect(screen.getByRole('heading', { name: 'Delete Feedback' })).toBeInTheDocument();
        expect(screen.getByText(/Are you sure you want to delete this/)).toBeInTheDocument();

        // Click delete confirm inside the dialog
        const dialog = screen.getByRole('dialog');
        const confirmBtn = within(dialog).getByRole('button', { name: 'Delete' });
        await user.click(confirmBtn);

        await waitFor(() => {
            expect(apiMocks.deleteVehicleServiceFeedback).toHaveBeenCalledWith(7, 101, 1);
            expect(onChanged).toHaveBeenCalledWith(4);
        });
    });

    it('renders empty state when no feedbacks exist', async () => {
        apiMocks.listVehicleServiceFeedbacks.mockResolvedValue({
            data: [],
            meta: {
                summary: {
                    average_rating: null,
                    total_count: 0,
                    ratings_count: 0,
                    reviews_count: 0,
                    notes_count: 0,
                    customer_feedback_count: 0,
                    complaints_count: 0,
                    rating_breakdown: { 1: 0, 2: 0, 3: 0, 4: 0, 5: 0 },
                },
            },
        });

        render(<VehicleServiceFeedbackTab jobId={7} />);

        await waitFor(() => {
            expect(screen.getByText('No feedback or reviews yet')).toBeInTheDocument();
            expect(screen.getByText('+ Add First Review or Note')).toBeInTheDocument();
        });
    });
});
