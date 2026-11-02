import { apiClient } from '@/shared/api/apiClient';

export interface RentalDashboardSummary {
    metrics: {
        active_customer_agreements: number;
        active_owner_agreements: number;
        vehicles_in_custody: number;
        planned_vehicle_uses: number;
        pending_reservations: number;
        confirmed_reservations: number;
        due_finance_installments: number;
        pending_expenses: number;
        active_vehicle_incidents: number;
        total_fleet: number;
        available_fleet: number;
    };
    recent_running_charts: Array<{
        id: number;
        reference: string;
        vehicle_label: string;
        customer_reference?: string | null;
        starts_at: string;
        ends_at: string;
        total_km: string | null;
        status: string;
    }>;
}

export const DASHBOARD_PERMISSION = 'vehicle-rental.dashboard.view';
export const DASHBOARD_PATH = '/vehicle-rental/dashboard';

export async function fetchRentalDashboard(signal?: AbortSignal): Promise<RentalDashboardSummary> {
    const response = await apiClient.get<{ data: RentalDashboardSummary }>('/api/v1/vehicle-rental/dashboard', { signal });
    return response.data.data;
}
