import { apiClient } from "@/shared/api/apiClient";
import { endpoints } from "@/shared/api/endpoints";
import type {
    ApiCollection,
    ApiResource,
    ListParams,
} from "@/shared/types/api";
import type {
    RentalAgreement,
    RentalAllocation,
    RentalCalculationRun,
    RentalCustodyEvent,
    RentalDeposit,
    RentalExpense,
    RentalMetadata,
    RentalPayload,
    RentalReservation,
    RentalUsageFact,
    RentalUsageLog,
    RentalVehicle,
    VehicleFinanceAgreement,
} from "./vehicleRentalTypes";

const base = endpoints.vehicleRental;
const collection = <T>(
    path: string,
    params: ListParams = {},
    signal?: AbortSignal,
) =>
    apiClient
        .get<ApiCollection<T>>(`${base}/${path}`, { params, signal })
        .then((response) => response.data);
const resource = <T>(path: string, signal?: AbortSignal) =>
    apiClient
        .get<ApiResource<T>>(`${base}/${path}`, { signal })
        .then((response) => response.data.data);
const post = <T>(path: string, payload: RentalPayload = {}) =>
    apiClient
        .post<ApiResource<T>>(`${base}/${path}`, payload)
        .then((response) => response.data.data);
const put = <T>(path: string, payload: RentalPayload) =>
    apiClient
        .put<ApiResource<T>>(`${base}/${path}`, payload)
        .then((response) => response.data.data);
const patch = <T>(path: string, payload: RentalPayload = {}) =>
    apiClient
        .patch<ApiResource<T>>(`${base}/${path}`, payload)
        .then((response) => response.data.data);

export const getRentalMetadata = (signal?: AbortSignal) =>
    resource<RentalMetadata>("metadata", signal);
export const getRentalDashboard = (signal?: AbortSignal) =>
    resource<Record<string, number>>("dashboard", signal);
export const listAvailableRentalVehicles = (
    params: ListParams,
    signal?: AbortSignal,
) => collection<RentalVehicle>("vehicles/available", params, signal);

export const listRentalReservations = (
    params: ListParams,
    signal?: AbortSignal,
) => collection<RentalReservation>("reservations", params, signal);
export const getRentalReservation = (id: number, signal?: AbortSignal) =>
    resource<RentalReservation>(`reservations/${id}`, signal);
export const createRentalReservation = (payload: RentalPayload) =>
    post<RentalReservation>("reservations", payload);
export const updateRentalReservation = (
    id: number,
    expectedVersion: number,
    payload: RentalPayload,
) =>
    put<RentalReservation>(`reservations/${id}`, {
        ...payload,
        expected_version: expectedVersion,
    });
export const transitionRentalReservation = (
    id: number,
    expectedVersion: number,
    status: string,
    reason?: string,
) =>
    patch<RentalReservation>(`reservations/${id}/transition`, {
        expected_version: expectedVersion,
        status,
        reason,
    });

export const listRentalAgreements = (
    params: ListParams,
    signal?: AbortSignal,
) => collection<RentalAgreement>("agreements", params, signal);
export const getRentalAgreement = (id: number, signal?: AbortSignal) =>
    resource<RentalAgreement>(`agreements/${id}`, signal);
export const createRentalAgreement = (payload: RentalPayload) =>
    post<RentalAgreement>("agreements", payload);
export const updateRentalAgreement = (
    id: number,
    expectedVersion: number,
    payload: RentalPayload,
) =>
    put<RentalAgreement>(`agreements/${id}`, {
        ...payload,
        expected_version: expectedVersion,
    });
export const transitionRentalAgreement = (
    id: number,
    expectedVersion: number,
    status: string,
    reason?: string,
) =>
    patch<RentalAgreement>(`agreements/${id}/transition`, {
        expected_version: expectedVersion,
        status,
        reason,
    });
export const createRentalRateVersion = (
    agreementId: number,
    expectedAgreementVersion: number,
    payload: RentalPayload,
) => post(`agreements/${agreementId}/rate-versions`, {
    ...payload,
    expected_agreement_version: expectedAgreementVersion,
});
export const activateRentalRateVersion = (
    versionId: number,
    expectedVersion: number,
) =>
    patch(`rate-versions/${versionId}/activate`, {
        expected_version: expectedVersion,
    });

export const listRentalAllocations = (
    params: ListParams,
    signal?: AbortSignal,
) => collection<RentalAllocation>("allocations", params, signal);
export const getRentalAllocation = (id: number, signal?: AbortSignal) =>
    resource<RentalAllocation>(`allocations/${id}`, signal);
export const createRentalAllocation = (
    agreementId: number,
    expectedAgreementVersion: number,
    payload: RentalPayload,
) =>
    post<RentalAllocation>(`agreements/${agreementId}/allocations`, {
        ...payload,
        expected_agreement_version: expectedAgreementVersion,
    });
export const assignRentalDriver = (
    id: number,
    expectedVersion: number,
    payload: RentalPayload,
) =>
    post<RentalAllocation>(`allocations/${id}/drivers`, {
        ...payload,
        expected_version: expectedVersion,
    });
export const cancelRentalAllocation = (
    id: number,
    expectedVersion: number,
    reason?: string,
) =>
    patch<RentalAllocation>(`allocations/${id}/cancel`, {
        expected_version: expectedVersion,
        reason,
    });

export const listRentalCustodyEvents = (
    params: ListParams,
    signal?: AbortSignal,
) => collection<RentalCustodyEvent>("custody-events", params, signal);
export const createRentalCustodyEvent = (
    allocationId: number,
    expectedAllocationVersion: number,
    payload: RentalPayload,
) =>
    post<RentalCustodyEvent>(
        `allocations/${allocationId}/custody-events`,
        { ...payload, expected_allocation_version: expectedAllocationVersion },
    );
export const confirmRentalCustodyEvent = (id: number, expectedVersion: number) =>
    patch<RentalCustodyEvent>(`custody-events/${id}/confirm`, {
        expected_version: expectedVersion,
    });
export const reverseRentalCustodyEvent = (
    id: number,
    expectedVersion: number,
    reason: string,
) =>
    patch<RentalCustodyEvent>(`custody-events/${id}/reverse`, {
        expected_version: expectedVersion,
        status: "reversed",
        reason,
    });
export const replaceRentalVehicle = (
    allocationId: number,
    expectedAllocationVersion: number,
    expectedAgreementVersion: number,
    payload: RentalPayload,
) =>
    post(`allocations/${allocationId}/replacement`, {
        ...payload,
        expected_allocation_version: expectedAllocationVersion,
        expected_agreement_version: expectedAgreementVersion,
    });

export const listRentalUsageLogs = (params: ListParams, signal?: AbortSignal) =>
    collection<RentalUsageLog>("usage-logs", params, signal);
export const getRentalUsageLog = (id: number, signal?: AbortSignal) =>
    resource<RentalUsageLog>(`usage-logs/${id}`, signal);
export const createRentalUsageLog = (
    allocationId: number,
    expectedAllocationVersion: number,
    payload: RentalPayload,
) => post<RentalUsageLog>(`allocations/${allocationId}/usage-logs`, {
    ...payload,
    expected_allocation_version: expectedAllocationVersion,
});
export const transitionRentalUsageLog = (
    id: number,
    expectedVersion: number,
    status: string,
    reason?: string,
) =>
    patch<RentalUsageLog>(`usage-logs/${id}/transition`, {
        expected_version: expectedVersion,
        status,
        reason,
    });
export const updateRentalUsageFact = (id: number, payload: RentalPayload) =>
    patch<RentalUsageFact>(`usage-facts/${id}`, payload);
export const transitionRentalUsageFact = (
    id: number,
    expectedVersion: number,
    status: string,
    reason?: string,
) =>
    patch<RentalUsageFact>(`usage-facts/${id}/transition`, {
        expected_version: expectedVersion,
        status,
        reason,
    });

export const listRentalExpenses = (params: ListParams, signal?: AbortSignal) =>
    collection<RentalExpense>("expenses", params, signal);
export const createRentalExpense = (payload: RentalPayload) =>
    post<RentalExpense>("expenses", payload);
export const transitionRentalExpense = (
    id: number,
    expectedVersion: number,
    status: string,
    reason?: string,
) =>
    patch<RentalExpense>(`expenses/${id}/transition`, {
        expected_version: expectedVersion,
        status,
        reason,
    });

export const listRentalCalculationRuns = (
    params: ListParams,
    signal?: AbortSignal,
) => collection<RentalCalculationRun>("calculation-runs", params, signal);
export const calculateRentalAgreement = (
    agreementId: number,
    expectedAgreementVersion: number,
    payload: RentalPayload,
) => post<RentalCalculationRun>(`agreements/${agreementId}/calculate`, {
    ...payload,
    expected_agreement_version: expectedAgreementVersion,
});
export const transitionRentalCalculationRun = (
    id: number,
    expectedVersion: number,
    status: string,
    reason?: string,
) =>
    patch<RentalCalculationRun>(`calculation-runs/${id}/transition`, {
        expected_version: expectedVersion,
        status,
        reason,
    });
export const createRentalInvoice = (
    id: number,
    expectedVersion: number,
    payload: RentalPayload,
) =>
    post<{ id: number; invoice_number: string; status: string }>(
        `calculation-runs/${id}/invoice`,
        { ...payload, expected_version: expectedVersion },
    );

export const listRentalDeposits = (params: ListParams, signal?: AbortSignal) =>
    collection<RentalDeposit>("deposits", params, signal);
export const receiveRentalDeposit = (id: number, payload: RentalPayload) =>
    post<RentalDeposit>(`deposits/${id}/receive`, payload);
export const applyRentalDeposit = (id: number, payload: RentalPayload) =>
    post<RentalDeposit>(`deposits/${id}/apply`, payload);
export const refundRentalDeposit = (id: number, payload: RentalPayload) =>
    post<RentalDeposit>(`deposits/${id}/refund`, payload);
export const forfeitRentalDeposit = (id: number, payload: RentalPayload) =>
    post<RentalDeposit>(`deposits/${id}/forfeit`, payload);
export const reverseRentalDepositLink = (
    linkId: number,
    expectedRequirementVersion: number,
) =>
    patch<RentalDeposit>(`deposit-links/${linkId}/reverse`, {
        expected_requirement_version: expectedRequirementVersion,
    });

export const listVehicleFinanceAgreements = (
    params: ListParams,
    signal?: AbortSignal,
) => collection<VehicleFinanceAgreement>("finance-agreements", params, signal);
export const createVehicleFinanceAgreement = (payload: RentalPayload) =>
    post<VehicleFinanceAgreement>("finance-agreements", payload);
export const activateVehicleFinanceAgreement = (
    id: number,
    expectedVersion: number,
) =>
    patch<VehicleFinanceAgreement>(`finance-agreements/${id}/activate`, {
        expected_version: expectedVersion,
    });
export const createVehicleFinancePayable = (
    installmentId: number,
    expectedVersion: number,
    payload: RentalPayload,
) =>
    post(`finance-installments/${installmentId}/payable`, {
        ...payload,
        expected_version: expectedVersion,
    });
