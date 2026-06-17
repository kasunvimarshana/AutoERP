import { apiClient } from '@/shared/api/apiClient';
import type { ApiResource, ListParams } from '@/shared/types/api';
import type {
    RentalAgreement,
    RentalAgreementCollection,
    RentalAgreementVehicle,
    RentalAgreementVehicleLink,
    RentalAvailabilityRow,
    RentalCharge,
    RentalExpense,
    RentalInspection,
    RentalInvoicePreview,
    RentalReservation,
    RentalReservationCollection,
    RentalUsageEvent,
    RentalUsageLog,
    RunningChartAgreementCollection,
    RunningChartContext,
    RunningChartPreview,
    RunningChartTripPayload,
} from './vehicleRentalTypes';

const endpoint = '/api/v1/vehicle-rental';

export const listRentalReservations = async (params: ListParams, signal?: AbortSignal): Promise<RentalReservationCollection> =>
    (await apiClient.get<RentalReservationCollection>(`${endpoint}/reservations`, { params, signal })).data;
export const createRentalReservation = async (payload: Record<string, unknown>): Promise<RentalReservation> =>
    (await apiClient.post<ApiResource<RentalReservation>>(`${endpoint}/reservations`, payload)).data.data;
export const getRentalReservation = async (id: number, signal?: AbortSignal): Promise<RentalReservation> =>
    (await apiClient.get<ApiResource<RentalReservation>>(`${endpoint}/reservations/${id}`, { signal })).data.data;
export const changeRentalReservationStatus = async (id: number, status: 'pending' | 'confirm' | 'cancel'): Promise<RentalReservation> =>
    (await apiClient.patch<ApiResource<RentalReservation>>(`${endpoint}/reservations/${id}/${status}`)).data.data;

export const listRentalAgreements = async (params: ListParams, signal?: AbortSignal): Promise<RentalAgreementCollection> =>
    (await apiClient.get<RentalAgreementCollection>(`${endpoint}/agreements`, { params, signal })).data;
export const createRentalAgreement = async (payload: Record<string, unknown>): Promise<RentalAgreement> =>
    (await apiClient.post<ApiResource<RentalAgreement>>(`${endpoint}/agreements`, payload)).data.data;
export const getRentalAgreement = async (id: number, signal?: AbortSignal): Promise<RentalAgreement> =>
    (await apiClient.get<ApiResource<RentalAgreement>>(`${endpoint}/agreements/${id}`, { signal })).data.data;
export const changeRentalAgreementStatus = async (id: number, status: 'confirm' | 'activate' | 'returned' | 'complete' | 'cancel'): Promise<RentalAgreement> =>
    (await apiClient.patch<ApiResource<RentalAgreement>>(`${endpoint}/agreements/${id}/${status}`)).data.data;

export const getVehicleAvailability = async (params: Record<string, unknown>, signal?: AbortSignal): Promise<RentalAvailabilityRow[]> =>
    (await apiClient.get<ApiResource<RentalAvailabilityRow[]>>(`${endpoint}/availability`, { params, signal })).data.data;
export const allocateRentalVehicle = async (agreementId: number, payload: Record<string, unknown>): Promise<RentalAgreementVehicle> =>
    (await apiClient.post<ApiResource<RentalAgreementVehicle>>(`${endpoint}/agreements/${agreementId}/vehicles`, payload)).data.data;
export const replaceRentalVehicle = async (agreementId: number, allocationId: number, payload: Record<string, unknown>): Promise<RentalAgreementVehicle> =>
    (await apiClient.post<ApiResource<RentalAgreementVehicle>>(`${endpoint}/agreements/${agreementId}/vehicles/${allocationId}/replace`, payload)).data.data;
export const savePickupInspection = async (agreementId: number, allocationId: number, payload: Record<string, unknown>): Promise<RentalInspection> =>
    (await apiClient.put<ApiResource<RentalInspection>>(`${endpoint}/agreements/${agreementId}/vehicles/${allocationId}/pickup`, payload)).data.data;
export const saveReturnInspection = async (agreementId: number, allocationId: number, payload: Record<string, unknown>): Promise<RentalInspection> =>
    (await apiClient.put<ApiResource<RentalInspection>>(`${endpoint}/agreements/${agreementId}/vehicles/${allocationId}/return`, payload)).data.data;

export const listRentalUsageLogs = async (agreementId: number, signal?: AbortSignal): Promise<RentalUsageLog[]> =>
    (await apiClient.get<ApiResource<RentalUsageLog[]>>(`${endpoint}/agreements/${agreementId}/usage-logs`, { signal })).data.data;
export const createRentalUsageLog = async (agreementId: number, payload: Record<string, unknown>): Promise<RentalUsageLog> =>
    (await apiClient.post<ApiResource<RentalUsageLog>>(`${endpoint}/agreements/${agreementId}/usage-logs`, payload)).data.data;
export const updateRentalUsageLog = async (agreementId: number, usageLogId: number, payload: Record<string, unknown>): Promise<RentalUsageLog> =>
    (await apiClient.put<ApiResource<RentalUsageLog>>(`${endpoint}/agreements/${agreementId}/usage-logs/${usageLogId}`, payload)).data.data;
export const deleteRentalUsageLog = async (agreementId: number, usageLogId: number): Promise<void> => {
    await apiClient.delete(`${endpoint}/agreements/${agreementId}/usage-logs/${usageLogId}`);
};
export const createRentalUsageEvent = async (agreementId: number, usageLogId: number, payload: Record<string, unknown>): Promise<RentalUsageEvent> =>
    (await apiClient.post<ApiResource<RentalUsageEvent>>(`${endpoint}/agreements/${agreementId}/usage-logs/${usageLogId}/events`, payload)).data.data;
export const updateRentalUsageEvent = async (agreementId: number, usageLogId: number, eventId: number, payload: Record<string, unknown>): Promise<RentalUsageEvent> =>
    (await apiClient.put<ApiResource<RentalUsageEvent>>(`${endpoint}/agreements/${agreementId}/usage-logs/${usageLogId}/events/${eventId}`, payload)).data.data;
export const deleteRentalUsageEvent = async (agreementId: number, usageLogId: number, eventId: number): Promise<void> => {
    await apiClient.delete(`${endpoint}/agreements/${agreementId}/usage-logs/${usageLogId}/events/${eventId}`);
};
export const changeRentalUsageStatus = async (agreementId: number, usageLogId: number, status: 'submit' | 'approve' | 'reject', reason?: string): Promise<RentalUsageLog> =>
    (await apiClient.patch<ApiResource<RentalUsageLog>>(`${endpoint}/agreements/${agreementId}/usage-logs/${usageLogId}/${status}`, { reason })).data.data;
export const listRunningChartAgreements = async (
    params: ListParams = {},
    signal?: AbortSignal,
): Promise<RunningChartAgreementCollection> =>
    (await apiClient.get<RunningChartAgreementCollection>(
        `${endpoint}/running-chart/agreements`,
        { params, signal },
    )).data;
export const getRunningChartContext = async (params: {
    mode?: string;
    agreement_id?: number;
    agreement_vehicle_id?: number;
    lessee_agreement_id?: number;
    lessee_agreement_vehicle_id?: number;
    lessor_agreement_id?: number;
    lessor_agreement_vehicle_id?: number;
    usage_date: string;
    start_time?: string;
}, signal?: AbortSignal): Promise<RunningChartContext> =>
    (await apiClient.get<ApiResource<RunningChartContext>>(`${endpoint}/running-chart/context`, { params, signal })).data.data;
export const listRunningChartTrips = async (params: {
    mode?: string;
    agreement_id?: number;
    agreement_vehicle_id?: number;
    lessee_agreement_id?: number;
    lessee_agreement_vehicle_id?: number;
    lessor_agreement_id?: number;
    lessor_agreement_vehicle_id?: number;
    usage_date: string;
}, signal?: AbortSignal): Promise<RentalUsageLog[]> =>
    (await apiClient.get<ApiResource<RentalUsageLog[]>>(`${endpoint}/running-chart/trips`, { params, signal })).data.data;
export const createRunningChartTrip = async (payload: RunningChartTripPayload): Promise<RentalUsageLog> =>
    (await apiClient.post<ApiResource<RentalUsageLog>>(`${endpoint}/running-chart/trips`, payload)).data.data;
export const updateRunningChartTrip = async (usageLogId: number, payload: RunningChartTripPayload): Promise<RentalUsageLog> =>
    (await apiClient.put<ApiResource<RentalUsageLog>>(`${endpoint}/running-chart/trips/${usageLogId}`, payload)).data.data;
export const deleteRunningChartTrip = async (usageLogId: number): Promise<void> => {
    await apiClient.delete(`${endpoint}/running-chart/trips/${usageLogId}`);
};
export const changeRunningChartTripStatus = async (
    usageLogId: number,
    status: 'submit' | 'approve' | 'reject',
    reason?: string,
): Promise<RentalUsageLog> =>
    (await apiClient.patch<ApiResource<RentalUsageLog>>(`${endpoint}/running-chart/trips/${usageLogId}/${status}`, { reason })).data.data;
export const submitRunningChartDaily = async (payload: {
    mode: string;
    lessee_agreement_id?: number;
    lessee_agreement_vehicle_id?: number;
    lessor_agreement_id?: number;
    lessor_agreement_vehicle_id?: number;
    usage_date: string;
    trips: RunningChartTripPayload[];
}): Promise<RentalUsageLog[]> =>
    (await apiClient.post<ApiResource<RentalUsageLog[]>>(`${endpoint}/running-chart/daily-submit`, payload)).data.data;
export const previewRunningChart = async (payload: {
    mode: string;
    lessee_agreement_id?: number;
    lessee_agreement_vehicle_id?: number;
    lessor_agreement_id?: number;
    lessor_agreement_vehicle_id?: number;
    usage_date: string;
    trips: RunningChartTripPayload[];
}): Promise<RunningChartPreview> =>
    (await apiClient.post<ApiResource<RunningChartPreview>>(`${endpoint}/running-chart/preview`, payload)).data.data;
export const createRentalAgreementVehicleLink = async (payload: Record<string, unknown>): Promise<RentalAgreementVehicleLink> =>
    (await apiClient.post<ApiResource<RentalAgreementVehicleLink>>(`${endpoint}/agreement-vehicle-links`, payload)).data.data;
export const changeRentalAgreementVehicleLinkStatus = async (
    linkId: number,
    status: 'submit' | 'approve' | 'cancel',
    reason?: string,
): Promise<RentalAgreementVehicleLink> =>
    (await apiClient.patch<ApiResource<RentalAgreementVehicleLink>>(
        `${endpoint}/agreement-vehicle-links/${linkId}/${status}`,
        { reason },
    )).data.data;

export const listRentalExpenses = async (agreementId: number, signal?: AbortSignal): Promise<RentalExpense[]> =>
    (await apiClient.get<ApiResource<RentalExpense[]>>(`${endpoint}/agreements/${agreementId}/expenses`, { signal })).data.data;
export const createRentalExpense = async (agreementId: number, payload: Record<string, unknown>): Promise<RentalExpense> =>
    (await apiClient.post<ApiResource<RentalExpense>>(`${endpoint}/agreements/${agreementId}/expenses`, payload)).data.data;
export const updateRentalExpense = async (agreementId: number, expenseId: number, payload: Record<string, unknown>): Promise<RentalExpense> =>
    (await apiClient.put<ApiResource<RentalExpense>>(`${endpoint}/agreements/${agreementId}/expenses/${expenseId}`, payload)).data.data;
export const deleteRentalExpense = async (agreementId: number, expenseId: number): Promise<void> => {
    await apiClient.delete(`${endpoint}/agreements/${agreementId}/expenses/${expenseId}`);
};
export const changeRentalExpenseStatus = async (agreementId: number, expenseId: number, status: 'submit' | 'approve' | 'reject'): Promise<RentalExpense> =>
    (await apiClient.patch<ApiResource<RentalExpense>>(`${endpoint}/agreements/${agreementId}/expenses/${expenseId}/${status}`)).data.data;

export const listRentalCharges = async (agreementId: number, signal?: AbortSignal): Promise<RentalCharge[]> =>
    (await apiClient.get<ApiResource<RentalCharge[]>>(`${endpoint}/agreements/${agreementId}/charges`, { signal })).data.data;
export const previewRentalCharges = async (agreementId: number): Promise<RentalCharge[]> =>
    (await apiClient.post<ApiResource<RentalCharge[]>>(`${endpoint}/agreements/${agreementId}/charges/preview`)).data.data;
export const generateRentalCharges = async (agreementId: number, replace = false): Promise<RentalCharge[]> =>
    (await apiClient.post<ApiResource<RentalCharge[]>>(`${endpoint}/agreements/${agreementId}/charges/generate`, { replace })).data.data;
export const approveRentalCharges = async (agreementId: number): Promise<RentalCharge[]> =>
    (await apiClient.patch<ApiResource<RentalCharge[]>>(`${endpoint}/agreements/${agreementId}/charges/approve`)).data.data;
export const listRentalInvoiceCharges = async (agreementId: number, signal?: AbortSignal): Promise<RentalCharge[]> =>
    (await apiClient.get<ApiResource<RentalCharge[]>>(`${endpoint}/agreements/${agreementId}/invoice-charges`, { signal })).data.data;
export const previewRentalInvoice = async (agreementId: number, payload: Record<string, unknown>): Promise<RentalInvoicePreview> =>
    (await apiClient.post<ApiResource<RentalInvoicePreview>>(`${endpoint}/agreements/${agreementId}/invoices/preview`, payload)).data.data;
export const createRentalInvoice = async (agreementId: number, payload: Record<string, unknown>): Promise<{ id: number; invoice_number: string; grand_total: string }> =>
    (await apiClient.post<ApiResource<{ id: number; invoice_number: string; grand_total: string }>>(`${endpoint}/agreements/${agreementId}/invoices`, payload)).data.data;
export const prepareRentalPayment = async (agreementId: number, payload: Record<string, unknown>): Promise<Record<string, unknown>> =>
    (await apiClient.post<ApiResource<Record<string, unknown>>>(`${endpoint}/agreements/${agreementId}/payments/prepare`, payload)).data.data;
export const createRentalPayment = async (agreementId: number, payload: Record<string, unknown>): Promise<{ id: number; payment_number: string; total_amount: string }> =>
    (await apiClient.post<ApiResource<{ id: number; payment_number: string; total_amount: string }>>(`${endpoint}/agreements/${agreementId}/payments`, payload)).data.data;
