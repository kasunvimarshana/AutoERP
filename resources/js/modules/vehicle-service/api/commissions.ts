import { apiClient } from '@/shared/api/apiClient';
import { endpoints } from '@/shared/api/endpoints';
import type { ApiResource } from '@/shared/types/api';
import type {
    VehicleServiceCommissionPolicyPayload,
    VehicleServiceLaborItemCommissionRule,
    VehicleServiceSupervisorCommissionPolicy,
} from '../commissionTypes';

const policies = `${endpoints.vehicleService}/commission-policies`;

export const getSupervisorCommissionDefault = (signal?: AbortSignal) =>
    apiClient.get<ApiResource<VehicleServiceSupervisorCommissionPolicy | null>>(
        `${policies}/supervisor-default`,
        { signal },
    ).then((response) => response.data.data);

export const saveSupervisorCommissionDefault = (payload: VehicleServiceCommissionPolicyPayload) =>
    apiClient.put<ApiResource<VehicleServiceSupervisorCommissionPolicy>>(
        `${policies}/supervisor-default`,
        payload,
    ).then((response) => response.data.data);

export const getLaborItemCommissionRule = (
    itemId: number,
    signal?: AbortSignal,
) => apiClient.get<ApiResource<VehicleServiceLaborItemCommissionRule | null>>(
    `${policies}/labor-items/${itemId}`,
    { signal },
).then((response) => response.data.data);

export const saveLaborItemCommissionRule = (
    itemId: number,
    payload: VehicleServiceCommissionPolicyPayload,
) => apiClient.put<ApiResource<VehicleServiceLaborItemCommissionRule>>(
    `${policies}/labor-items/${itemId}`,
    payload,
).then((response) => response.data.data);
