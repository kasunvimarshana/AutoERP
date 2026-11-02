import { apiClient } from '@/shared/api/apiClient';
import type { PaginationMeta } from '@/shared/types/pagination';

export interface PaginatedResponse<T> {
    data: T[];
    meta: PaginationMeta;
}

export enum ReservationStatus {
    Draft = 'draft',
    Pending = 'pending',
    Confirmed = 'confirmed',
    Converted = 'converted',
    Cancelled = 'cancelled',
    Expired = 'expired',
}

export interface RentalReservation {
    id: number;
    row_version: number;
    reservation_number: string;
    customer: { id: number; name: string };
    requested_vehicle: {
        id: number;
        vehicle_number: string;
        registration_number?: string | null;
        label: string;
        make?: string | null;
        model?: string | null;
    } | null;
    requested_vehicle_category: { id: number; name: string } | null;
    rental_mode: string;
    billing_cycle: string;
    requested_start_at: string;
    requested_end_at: string;
    currency: { id: number; code: string };
    estimated_amount: string;
    estimated_deposit_amount: string;
    status: ReservationStatus;
    source?: string | null;
    remarks?: string | null;
    converted_agreement?: { id: number; reference: string } | null;
    confirmed_at?: string | null;
    cancelled_at?: string | null;
    created_at?: string | null;
    updated_at?: string | null;
    history?: Array<{
        id: number;
        from_status?: string | null;
        to_status: string;
        reason?: string | null;
        changed_by: string;
        changed_at: string;
    }>;
}

export interface StoreReservationPayload {
    customer_id: number;
    requested_vehicle_id?: number | null;
    requested_vehicle_category_id?: number | null;
    rental_mode: string;
    billing_cycle: string;
    requested_start_at: string;
    requested_end_at: string;
    currency_id: number;
    estimated_amount?: string;
    estimated_deposit_amount?: string;
    source?: string | null;
    remarks?: string | null;
}

export const RESERVATION_PERMISSION = {
    view: 'vehicle-rental.reservations.view',
    manage: 'vehicle-rental.reservations.manage',
};

export const RESERVATION_REGISTER_PATH = '/vehicle-rental/reservations';

export async function listReservations(
    page: number = 1,
    signal?: AbortSignal,
    filters?: Record<string, string | number>,
): Promise<PaginatedResponse<RentalReservation>> {
    const params = new URLSearchParams({ page: String(page) });
    if (filters) {
        Object.entries(filters).forEach(([key, value]) => {
            if (value !== undefined && value !== '') params.append(key, String(value));
        });
    }
    const response = await apiClient.get<PaginatedResponse<RentalReservation>>(`/api/v1/vehicle-rental/reservations?${params.toString()}`, { signal });
    return response.data;
}

export async function getReservation(id: number, signal?: AbortSignal): Promise<RentalReservation> {
    const response = await apiClient.get<{ data: RentalReservation }>(`/api/v1/vehicle-rental/reservations/${id}`, { signal });
    return response.data.data;
}

export async function createReservation(payload: StoreReservationPayload): Promise<RentalReservation> {
    const response = await apiClient.post<{ data: RentalReservation }>('/api/v1/vehicle-rental/reservations', payload);
    return response.data.data;
}

export async function updateReservation(id: number, version: number, payload: Partial<StoreReservationPayload>): Promise<RentalReservation> {
    const response = await apiClient.put<{ data: RentalReservation }>(`/api/v1/vehicle-rental/reservations/${id}`, {
        expected_version: version,
        ...payload,
    });
    return response.data.data;
}

export async function transitionReservation(
    id: number,
    version: number,
    status: ReservationStatus,
    reason?: string,
): Promise<RentalReservation> {
    const response = await apiClient.patch<{ data: RentalReservation }>(`/api/v1/vehicle-rental/reservations/${id}/transition`, {
        expected_version: version,
        status,
        reason,
    });
    return response.data.data;
}

export async function convertReservation(
    id: number,
    version: number,
    payload: { reference?: string; notes?: string },
): Promise<{ id: number; reference: string }> {
    const response = await apiClient.post<{ data: { id: number; reference: string } }>(`/api/v1/vehicle-rental/reservations/${id}/convert`, {
        expected_version: version,
        ...payload,
    });
    return response.data.data;
}
