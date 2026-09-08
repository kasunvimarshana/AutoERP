import { apiClient } from '@/shared/api/apiClient';
import type { ApiCollection, ApiResource } from '@/shared/types/api';
import { AGREEMENT_API, PAGE_SIZE, type Agreement } from './agreements';
import { USE_API, VehicleUseAction, type VehicleUse, type VehicleUseHistory } from './vehicleUse';
const agreementUses = (id: number) => `${AGREEMENT_API}/customer/agreements/${id}/vehicles`;
export const listVehicleUses = (agreement: number, page: number, signal?: AbortSignal) => apiClient.get<ApiCollection<VehicleUse>>(agreementUses(agreement), { params: { page, per_page: PAGE_SIZE }, signal }).then(r => r.data);
export const planVehicleUse = (agreement: Agreement, input: { vehicle_id: number; owner_agreement_id: number | null; starts_at: string; ends_at: string | null; notes: string | null }) => apiClient.post<ApiResource<VehicleUse>>(agreementUses(agreement.id), { ...input, expected_version: agreement.row_version }).then(r => r.data.data);
export const transitionVehicleUse = (use: VehicleUse, action: VehicleUseAction, input: { occurred_at?: string; odometer?: string | null; reason: string }) => apiClient.post<ApiResource<VehicleUse>>(`${USE_API}/${use.id}/${action}`, { ...input, expected_version: use.row_version }).then(r => r.data.data);
export const vehicleUseHistory = (id: number, page: number, signal?: AbortSignal) => apiClient.get<ApiCollection<VehicleUseHistory>>(`${USE_API}/${id}/history`, { params: { page, per_page: PAGE_SIZE }, signal }).then(r => r.data);

export const replaceVehicleUse = (use: VehicleUse, input: { vehicle_id: number; owner_agreement_id: number | null; starts_at: string; ends_at: string | null; notes: string | null; reason: string; return_odometer: string | null; handover_odometer: string | null }) => apiClient.post<ApiResource<VehicleUse>>(`${USE_API}/${use.id}/replace`, { ...input, expected_version: use.row_version }).then(r => r.data.data);
