/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

import type { ReportDefinition } from '../reportingTypes';

export type ServiceJobFeedbackType = 'review' | 'internal_note' | 'customer_feedback' | 'complaint';

export interface ServiceJobFeedbackReportRow {
    id: number;
    job_id: number;
    job_number: string;
    job_date: string;
    job_status: string;
    job_status_label: string;
    job_type_id: number | null;
    job_type_code: string;
    job_type_name: string;
    customer_id: number | null;
    customer_name: string;
    customer_phone: string | null;
    customer_email: string | null;
    vehicle_id: number | null;
    registration_number: string;
    vehicle_label: string;
    feedback_type: ServiceJobFeedbackType;
    feedback_type_label: string;
    rating: number | null;
    title: string | null;
    comment: string;
    author_name: string;
    is_customer_visible: boolean;
    created_at: string;
    created_at_formatted: string;
}

export interface ServiceJobFeedbackRatingDetail {
    stars: number;
    count: number;
    percentage: number;
}

export interface ServiceJobFeedbackReportSummary {
    total_jobs: number;
    total_feedbacks: number;
    average_rating: number | null;
    total_rated: number;
    total_unrated: number;
    reviews_count: number;
    internal_notes_count: number;
    customer_feedback_count: number;
    complaints_count: number;
    customer_visible_count: number;
    internal_only_count: number;
    positive_ratings_count: number;
    positive_rating_percentage: number;
    complaint_rate_percentage: number;
    rating_breakdown: Record<number, number>;
    rating_breakdown_details: Record<number, ServiceJobFeedbackRatingDetail>;
}

export type ServiceJobFeedbackDatePreset = 'today' | 'this_week' | 'this_month' | 'this_year' | 'all' | 'custom';

export interface ServiceJobFeedbackReportParams {
    tenant_id?: number;
    organization_unit_id?: number;
    date_from?: string;
    date_to?: string;
    date_preset?: ServiceJobFeedbackDatePreset;
    date_by?: 'job_date' | 'feedback_date';
    feedback_type?: string;
    rating?: string;
    is_customer_visible?: string;
    job_status?: string;
    job_type_id?: number;
    job_type_code?: string;
    customer_id?: number;
    vehicle_id?: number;
    search?: string;
    page?: number;
    per_page?: number;
}

export interface ServiceJobFeedbackReportResult {
    data: ServiceJobFeedbackReportRow[];
    summary: ServiceJobFeedbackReportSummary;
    meta: {
        current_page: number;
        from: number;
        last_page: number;
        per_page: number;
        to: number;
        total: number;
    };
    links: {
        first: string | null;
        last: string | null;
        prev: string | null;
        next: string | null;
    };
    report: ReportDefinition;
}
