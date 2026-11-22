/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

import { apiClient } from '@/shared/api/apiClient';
import type { ApiCollection, ApiResource } from '@/shared/types/api';
import { AGREEMENT_API, PAGE_SIZE, AgreementKind, type Agreement } from './agreements';
import { USE_API, VehicleUseAction, type VehicleUse, type VehicleUseHistory, type VehicleUseRegisterFilters } from './vehicleUse';
const agreementUses = (id: number, kind: AgreementKind = AgreementKind.Customer) =>
    kind === AgreementKind.Owner
        ? `${AGREEMENT_API}/owner/agreements/${id}/vehicles`
        : `${AGREEMENT_API}/customer/agreements/${id}/vehicles`;

export const listVehicleUses = (agreement: number, page: number, signal?: AbortSignal, kind: AgreementKind = AgreementKind.Customer) =>
    apiClient.get<ApiCollection<VehicleUse>>(agreementUses(agreement, kind), { params: { page, per_page: PAGE_SIZE }, signal }).then(r => r.data);
export const getVehicleUse = (id: number, signal?: AbortSignal) => apiClient.get<ApiResource<VehicleUse>>(`${USE_API}/${id}`, { signal }).then(r => r.data.data);
export const planVehicleUse = (
    agreement: Agreement,
    input: { vehicle_id: number; owner_agreement_id: number | null; customer_agreement_id?: number | null; starts_at: string; ends_at: string | null; notes: string | null },
    kind: AgreementKind = agreement.kind ?? AgreementKind.Customer,
) => apiClient.post<ApiResource<VehicleUse>>(agreementUses(agreement.id, kind), { ...input, expected_version: agreement.row_version }).then(r => r.data.data);
export const planInternalVehicleUse = (input: {
    vehicle_id: number;
    owner_agreement_id?: number | null;
    customer_agreement_id?: number | null;
    starts_at: string;
    ends_at?: string | null;
    notes?: string | null;
}) => apiClient.post<ApiResource<VehicleUse>>(`${USE_API}/internal`, input).then(r => r.data.data);
export const transitionVehicleUse = (use: VehicleUse, action: VehicleUseAction, input: { occurred_at?: string; odometer?: string | null; reason: string }) => apiClient.post<ApiResource<VehicleUse>>(`${USE_API}/${use.id}/${action}`, { ...input, expected_version: use.row_version }).then(r => r.data.data);
export const vehicleUseHistory = (id: number, page: number, signal?: AbortSignal) => apiClient.get<ApiCollection<VehicleUseHistory>>(`${USE_API}/${id}/history`, { params: { page, per_page: PAGE_SIZE }, signal }).then(r => r.data);

export const replaceVehicleUse = (use: VehicleUse, input: { vehicle_id: number; owner_agreement_id: number | null; starts_at: string; ends_at: string | null; notes: string | null; reason: string; return_odometer: string | null; handover_odometer: string | null }) => apiClient.post<ApiResource<VehicleUse>>(`${USE_API}/${use.id}/replace-vehicle`, { ...input, new_vehicle_id: input.vehicle_id, new_owner_agreement_id: input.owner_agreement_id, cutover_at: input.starts_at, new_handover_odometer: input.handover_odometer, expected_version: use.row_version }).then(r => r.data.data);

export const listVehicleUseRegister = (filters: VehicleUseRegisterFilters, page: number, signal?: AbortSignal) => apiClient.get<ApiCollection<VehicleUse>>(USE_API, { params: { ...filters, page, per_page: PAGE_SIZE }, signal }).then(r => r.data);

export interface AvailableVehicleOption {
    id: number;
    vehicle_number: string;
    registration_number: string | null;
    display_label: string;
    make: string | null;
    model: string | null;
    category: string | null;
    category_id: number | null;
    odometer_reading: string;
}

export const fetchAvailableVehicles = (startsAt: string, endsAt?: string | null, categoryId?: number | null, signal?: AbortSignal) =>
    apiClient.get<{ data: AvailableVehicleOption[] }>('/api/v1/vehicle-rental/vehicles/available', {
        params: { starts_at: startsAt, ends_at: endsAt ?? undefined, category_id: categoryId ?? undefined },
        signal,
    }).then(r => r.data.data);
