/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it, vi } from 'vitest';
import { TestRouter } from '@/test/TestRouter';
import ServiceJobFeedbackReportPage from './ServiceJobFeedbackReportPage';
import type { VehicleServiceJobTypeResource } from '@/modules/vehicle-service/vehicleServiceTypes';
import type { ServiceJobFeedbackReportResult } from '../types/serviceJobFeedbackReportTypes';

const apiMocks = vi.hoisted(() => ({
    runServiceJobFeedbackReport: vi.fn(),
}));

const jobTypeMocks = vi.hoisted(() => ({
    lookupVehicleServiceJobTypes: vi.fn(),
}));

vi.mock('../reportingApi', () => apiMocks);
vi.mock('@/modules/vehicle-service/api/jobTypes', () => jobTypeMocks);
vi.mock('../components/ExportActions', () => ({
    ExportActions: () => <div data-testid="export-actions">Export Actions Mock</div>,
}));
vi.mock('@/modules/vehicle/components/VehicleLookupSelect', () => ({
    VehicleLookupSelect: () => <div>Vehicle Select Mock</div>,
}));
vi.mock('@/modules/customer/components/CustomerLookupSelect', () => ({
    CustomerLookupSelect: () => <div>Customer Select Mock</div>,
}));

const mockResult: ServiceJobFeedbackReportResult = {
    data: [
        {
            id: 1,
            job_id: 101,
            job_number: 'JOB-2026-0001',
            job_date: '2026-06-01',
            job_status: 'completed',
            job_status_label: 'Completed',
            job_type_id: 1,
            job_type_code: 'full_service',
            job_type_name: 'Full Service',
            customer_id: 10,
            customer_name: 'Alice Johnson',
            customer_phone: '0771234567',
            customer_email: 'alice@example.com',
            vehicle_id: 20,
            registration_number: 'CAB-1234',
            vehicle_label: 'CAB-1234 (Toyota Corolla)',
            feedback_type: 'review',
            feedback_type_label: 'Customer Review',
            rating: 5,
            title: 'Outstanding Service',
            comment: 'Very professional and finished well ahead of expected delivery time.',
            author_name: 'Alice Johnson',
            is_customer_visible: true,
            created_at: '2026-06-01T14:30:00Z',
            created_at_formatted: '2026-06-01 14:30',
        },
        {
            id: 2,
            job_id: 102,
            job_number: 'JOB-2026-0002',
            job_date: '2026-06-02',
            job_status: 'in_progress',
            job_status_label: 'In Progress',
            job_type_id: 2,
            job_type_code: 'oil_change',
            job_type_name: 'Oil Change',
            customer_id: 11,
            customer_name: 'Bob Smith',
            customer_phone: '0779876543',
            customer_email: 'bob@example.com',
            vehicle_id: 21,
            registration_number: 'CAD-5678',
            vehicle_label: 'CAD-5678 (Honda Civic)',
            feedback_type: 'complaint',
            feedback_type_label: 'Customer Complaint',
            rating: 1,
            title: 'Delayed Pickup',
            comment: 'Service took longer than promised by the front desk advisor.',
            author_name: 'Bob Smith',
            is_customer_visible: false,
            created_at: '2026-06-02T16:00:00Z',
            created_at_formatted: '2026-06-02 16:00',
        },
    ],
    summary: {
        total_jobs: 2,
        total_feedbacks: 2,
        average_rating: 3.0,
        total_rated: 2,
        total_unrated: 0,
        reviews_count: 1,
        internal_notes_count: 0,
        customer_feedback_count: 0,
        complaints_count: 1,
        customer_visible_count: 1,
        internal_only_count: 1,
        positive_ratings_count: 1,
        positive_rating_percentage: 50.0,
        complaint_rate_percentage: 50.0,
        rating_breakdown: {
            5: 1,
            4: 0,
            3: 0,
            2: 0,
            1: 1,
        },
        rating_breakdown_details: {
            5: { stars: 5, count: 1, percentage: 50.0 },
            4: { stars: 4, count: 0, percentage: 0.0 },
            3: { stars: 3, count: 0, percentage: 0.0 },
            2: { stars: 2, count: 0, percentage: 0.0 },
            1: { stars: 1, count: 1, percentage: 50.0 },
        },
    },
    meta: {
        current_page: 1,
        from: 1,
        last_page: 1,
        per_page: 25,
        to: 2,
        total: 2,
    },
    links: {
        first: '?page=1&per_page=25',
        last: '?page=1&per_page=25',
        prev: null,
        next: null,
    },
    report: {
        key: 'vehicle-service/job-feedback',
        title: 'Service Jobs Reviews & Ratings Report',
        group: 'Vehicle Service',
        columns: [],
        filters: [],
        supports_date_range: true,
        default_sort: 'created_at',
        default_direction: 'desc',
    },
};

describe('ServiceJobFeedbackReportPage', () => {
    it('renders header, KPI metric cards, rating distribution, and feedback rows', async () => {
        jobTypeMocks.lookupVehicleServiceJobTypes.mockResolvedValueOnce([
            { id: 1, code: 'full_service', name: 'Full Service', tracks_mileage: true, is_active: true } as unknown as VehicleServiceJobTypeResource,
        ]);
        apiMocks.runServiceJobFeedbackReport.mockResolvedValueOnce(mockResult);

        render(
            <TestRouter>
                <ServiceJobFeedbackReportPage />
            </TestRouter>,
        );

        // Header
        expect(
            await screen.findByRole('heading', { level: 1, name: 'Service Jobs Reviews & Ratings Report' }),
        ).toBeInTheDocument();
        expect(screen.getByTestId('export-actions')).toBeInTheDocument();

        // Top KPI Cards
        expect(screen.getByText('Average Rating')).toBeInTheDocument();
        expect(screen.getByText('3.00')).toBeInTheDocument();
        expect(screen.getByText('Total Feedback')).toBeInTheDocument();
        expect(screen.getByText('Satisfaction Rate')).toBeInTheDocument();
        expect(screen.getAllByText('50%').length).toBeGreaterThanOrEqual(1);
        expect(screen.getByText('Complaint Rate')).toBeInTheDocument();

        // Rating distribution section
        expect(screen.getByText('Rating Distribution (1 – 5 Stars)')).toBeInTheDocument();
        expect(screen.getByText('5 Stars')).toBeInTheDocument();
        expect(screen.getByText('1 Star')).toBeInTheDocument();

        // Feedback Rows
        expect(screen.getAllByText('Outstanding Service').length).toBeGreaterThanOrEqual(1);
        expect(screen.getAllByText('JOB-2026-0001').length).toBeGreaterThanOrEqual(1);
        expect(screen.getAllByText('Alice Johnson').length).toBeGreaterThanOrEqual(1);
        expect(screen.getAllByText('CAB-1234 (Toyota Corolla)').length).toBeGreaterThanOrEqual(1);

        expect(screen.getAllByText('Delayed Pickup').length).toBeGreaterThanOrEqual(1);
        expect(screen.getAllByText('JOB-2026-0002').length).toBeGreaterThanOrEqual(1);
        expect(screen.getAllByText('Bob Smith').length).toBeGreaterThanOrEqual(1);
        expect(screen.getAllByText('CAD-5678 (Honda Civic)').length).toBeGreaterThanOrEqual(1);
    });

    it('handles rating bar click to filter by star rating', async () => {
        jobTypeMocks.lookupVehicleServiceJobTypes.mockResolvedValueOnce([]);
        apiMocks.runServiceJobFeedbackReport.mockResolvedValue(mockResult);

        const user = userEvent.setup();

        render(
            <TestRouter>
                <ServiceJobFeedbackReportPage />
            </TestRouter>,
        );

        await screen.findByRole('heading', { level: 1, name: 'Service Jobs Reviews & Ratings Report' });

        // Click the 5 Stars distribution button
        const fiveStarButton = screen.getByRole('button', { name: /5 Stars/i });
        await user.click(fiveStarButton);

        expect(apiMocks.runServiceJobFeedbackReport).toHaveBeenCalledWith(
            expect.objectContaining({
                rating: '5',
            }),
            expect.anything(),
        );
    });

    it('handles date preset selection and re-fetches report', async () => {
        jobTypeMocks.lookupVehicleServiceJobTypes.mockResolvedValueOnce([]);
        apiMocks.runServiceJobFeedbackReport.mockResolvedValue(mockResult);

        const user = userEvent.setup();

        render(
            <TestRouter>
                <ServiceJobFeedbackReportPage />
            </TestRouter>,
        );

        await screen.findByRole('heading', { level: 1, name: 'Service Jobs Reviews & Ratings Report' });

        const thisWeekButton = screen.getByRole('button', { name: 'This Week' });
        await user.click(thisWeekButton);

        expect(apiMocks.runServiceJobFeedbackReport).toHaveBeenCalledWith(
            expect.objectContaining({
                date_preset: 'this_week',
            }),
            expect.anything(),
        );
    });

    it('submits search filter keyword', async () => {
        jobTypeMocks.lookupVehicleServiceJobTypes.mockResolvedValueOnce([]);
        apiMocks.runServiceJobFeedbackReport.mockResolvedValue(mockResult);

        const user = userEvent.setup();

        render(
            <TestRouter>
                <ServiceJobFeedbackReportPage />
            </TestRouter>,
        );

        await screen.findByRole('heading', { level: 1, name: 'Service Jobs Reviews & Ratings Report' });

        const searchInput = screen.getByPlaceholderText('Job #, author, comment keywords...');
        await user.type(searchInput, 'Corolla');

        const searchButton = screen.getByRole('button', { name: 'Search' });
        await user.click(searchButton);

        expect(apiMocks.runServiceJobFeedbackReport).toHaveBeenCalledWith(
            expect.objectContaining({
                search: 'Corolla',
            }),
            expect.anything(),
        );
    });

    it('renders empty state when no feedback records exist', async () => {
        jobTypeMocks.lookupVehicleServiceJobTypes.mockResolvedValueOnce([]);
        apiMocks.runServiceJobFeedbackReport.mockResolvedValueOnce({
            ...mockResult,
            data: [],
            summary: {
                total_jobs: 0,
                total_feedbacks: 0,
                average_rating: null,
                total_rated: 0,
                total_unrated: 0,
                reviews_count: 0,
                internal_notes_count: 0,
                customer_feedback_count: 0,
                complaints_count: 0,
                customer_visible_count: 0,
                internal_only_count: 0,
                positive_ratings_count: 0,
                positive_rating_percentage: 0,
                complaint_rate_percentage: 0,
                rating_breakdown: { 5: 0, 4: 0, 3: 0, 2: 0, 1: 0 },
                rating_breakdown_details: {},
            },
        });

        render(
            <TestRouter>
                <ServiceJobFeedbackReportPage />
            </TestRouter>,
        );

        expect(
            await screen.findByText('No service job reviews or feedback found'),
        ).toBeInTheDocument();
    });
});
