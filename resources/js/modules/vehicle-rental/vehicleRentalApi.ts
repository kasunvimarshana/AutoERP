import { apiClient } from '@/shared/api/apiClient';
import type { ApiCollection, ListParams } from '@/shared/types/api';
import type {
    RentalAgreement,
    RentalAssignment,
    RentalCalculation,
    RentalRunningChart,
} from './vehicleRentalTypes';

const endpoint = '/api/v1/vehicle-rental';

export const listRentalAgreements = (params: ListParams, signal?: AbortSignal) =>
    apiClient.get<ApiCollection<RentalAgreement>>(`${endpoint}/agreements`, { params, signal })
        .then((response) => response.data);

export const listRentalAssignments = (params: ListParams, signal?: AbortSignal) =>
    apiClient.get<ApiCollection<RentalAssignment>>(`${endpoint}/assignments`, { params, signal })
        .then((response) => response.data);

export const listRentalRunningCharts = (params: ListParams, signal?: AbortSignal) =>
    apiClient.get<ApiCollection<RentalRunningChart>>(`${endpoint}/running-charts`, { params, signal })
        .then((response) => response.data);

export const listRentalCalculations = (params: ListParams, signal?: AbortSignal) =>
    apiClient.get<ApiCollection<RentalCalculation>>(`${endpoint}/calculations`, { params, signal })
        .then((response) => response.data);
