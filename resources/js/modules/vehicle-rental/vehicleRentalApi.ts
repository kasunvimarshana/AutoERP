import { apiClient } from '@/shared/api/apiClient';
import type { ApiCollection, ApiResource, ListParams } from '@/shared/types/api';
import type {
    RentalAgreement,
    RentalAgreementCollection,
    RentalAgreementVehicle,
    RentalAvailabilityRow,
    RentalCharge,
    RentalExpense,
    RentalInspection,
    RentalInvoicePreview,
    RentalReservation,
    RentalReservationCollection,
    RentalUsageEvent,
    RentalUsageLog,
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
export const createRentalUsageEvent = async (agreementId: number, usageLogId: number, payload: Record<string, unknown>): Promise<RentalUsageEvent> =>
    (await apiClient.post<ApiResource<RentalUsageEvent>>(`${endpoint}/agreements/${agreementId}/usage-logs/${usageLogId}/events`, payload)).data.data;

export const listRentalExpenses = async (agreementId: number, signal?: AbortSignal): Promise<RentalExpense[]> =>
    (await apiClient.get<ApiResource<RentalExpense[]>>(`${endpoint}/agreements/${agreementId}/expenses`, { signal })).data.data;
export const createRentalExpense = async (agreementId: number, payload: Record<string, unknown>): Promise<RentalExpense> =>
    (await apiClient.post<ApiResource<RentalExpense>>(`${endpoint}/agreements/${agreementId}/expenses`, payload)).data.data;
export const changeRentalExpenseStatus = async (agreementId: number, expenseId: number, status: 'approve' | 'reject'): Promise<RentalExpense> =>
    (await apiClient.patch<ApiResource<RentalExpense>>(`${endpoint}/agreements/${agreementId}/expenses/${expenseId}/${status}`)).data.data;

export const listRentalCharges = async (agreementId: number, signal?: AbortSignal): Promise<RentalCharge[]> =>
    (await apiClient.get<ApiResource<RentalCharge[]>>(`${endpoint}/agreements/${agreementId}/charges`, { signal })).data.data;
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
