import type { ApiCollectionResponse, ApiResponse } from '../../../services/api/apiResponse';
import { ApiError } from '../../../services/api/apiErrors';
import { httpClient } from '../../../services/api/httpClient';
import { mockCollectionResponse, mockPreviewResponse, mockResponse } from '../../../services/mock/mockResponse';
import { getVehicleById, vehicleOwnerships, vehicleRecords } from '../mock/vehicleMock';
import type { Vehicle, VehicleFormInput, VehicleOwnership, VehicleOwnershipRole, VehicleOwnerType, VehicleOwnershipType } from '../types/vehicle.types';

type BackendRecord = Record<string, unknown>;

const VEHICLE_API_MODE = import.meta.env.VITE_VEHICLE_API_MODE ?? 'auto';

function shouldUseMockOnly() {
    return VEHICLE_API_MODE === 'mock';
}

async function withMockFallback<T>(realCall: () => Promise<T>, mockCall: () => Promise<T>, fallbackStatuses = [401, 403, 404, 419, 422]): Promise<T> {
    if (shouldUseMockOnly()) {
        return mockCall();
    }

    try {
        return await realCall();
    } catch (error) {
        if (VEHICLE_API_MODE === 'real') {
            throw error;
        }

        if (error instanceof ApiError && !fallbackStatuses.includes(error.status)) {
            throw error;
        }

        return mockCall();
    }
}

function asString(value: unknown, fallback = '') {
    return value === null || value === undefined ? fallback : String(value);
}

function asOptionalString(value: unknown): string | undefined {
    const parsed = asString(value);
    return parsed === '' ? undefined : parsed;
}

function normalizeOwnershipType(value: unknown): VehicleOwnershipType {
    const parsed = asString(value, 'other').toLowerCase();
    const allowed: VehicleOwnershipType[] = ['customer', 'external', 'financed', 'internal', 'leased', 'other', 'own', 'partner', 'provider', 'supplier'];

    return allowed.includes(parsed as VehicleOwnershipType) ? (parsed as VehicleOwnershipType) : 'other';
}

function normalizeOwnerType(value: unknown): VehicleOwnerType {
    const parsed = asString(value, 'other').toLowerCase();
    const allowed: VehicleOwnerType[] = ['company', 'customer', 'employee', 'external_party', 'other', 'partner', 'party', 'provider', 'supplier'];

    return allowed.includes(parsed as VehicleOwnerType) ? (parsed as VehicleOwnerType) : 'other';
}

function normalizeOwnershipRole(value: unknown): VehicleOwnershipRole {
    const parsed = asString(value, 'legal_owner').toLowerCase();
    const allowed: VehicleOwnershipRole[] = ['current_holder', 'legal_owner', 'operational_owner', 'provider', 'registered_owner'];

    return allowed.includes(parsed as VehicleOwnershipRole) ? (parsed as VehicleOwnershipRole) : 'legal_owner';
}

function eligibilityLabel(value: unknown, fallback: string) {
    if (typeof value === 'boolean') {
        return value ? 'Enabled by backend' : 'Disabled by backend';
    }

    return asString(value, fallback);
}

function normalizeVehicle(raw: BackendRecord): Vehicle {
    const usageProfile = asString(raw.usage_profile ?? raw.usageProfile, 'Backend profile pending');

    return {
        brand: asString(raw.brand_name ?? raw.brand ?? raw.make, 'Not provided'),
        category: asString(raw.category_name ?? raw.category, 'Not classified'),
        code: asString(raw.vehicle_code ?? raw.code, 'VEH-MOCK'),
        color: asOptionalString(raw.color),
        currentOdometer: asString(raw.current_odometer ?? raw.currentOdometer, 'Backend-owned odometer'),
        engineNumber: asOptionalString(raw.engine_number ?? raw.engineNumber),
        fuelType: asOptionalString(raw.fuel_type ?? raw.fuelType),
        id: asString(raw.id),
        insuranceExpiry: asOptionalString(raw.insurance_expiry ?? raw.insuranceExpiry),
        model: asString(raw.model ?? raw.model_name, ''),
        notes: asOptionalString(raw.notes),
        registrationExpiry: asOptionalString(raw.registration_expiry ?? raw.registrationExpiry),
        registrationNumber: asString(raw.registration_number ?? raw.license_plate ?? raw.plate, 'Not provided'),
        rentalEligibility: eligibilityLabel(raw.rental_enabled ?? raw.rentalEligibility ?? raw.rental_profile_status, usageProfile),
        serviceEligibility: eligibilityLabel(raw.service_enabled ?? raw.serviceEligibility ?? raw.service_profile_status, usageProfile),
        status: asString(raw.status, 'active'),
        transmissionType: asOptionalString(raw.transmission_type ?? raw.transmissionType ?? raw.transmission),
        type: asString(raw.vehicle_type_name ?? raw.type, 'Vehicle'),
        usageProfile,
        vin: asOptionalString(raw.vin ?? raw.chassis_number ?? raw.chassisNumber),
        year: asOptionalString(raw.year_of_manufacture ?? raw.year),
    };
}

function toBackendVehiclePayload(input: VehicleFormInput): BackendRecord {
    return {
        category: input.category || null,
        color: input.color || null,
        current_odometer: input.currentOdometer === '' ? 0 : Number(input.currentOdometer),
        fuel_type: input.fuelType || null,
        insurance_expiry: input.insuranceExpiry || null,
        license_plate: input.registrationNumber || null,
        make: input.brand || null,
        model: input.model || null,
        registration_expiry: input.registrationExpiry || null,
        rental_enabled: input.rentalEnabled,
        service_enabled: input.serviceEnabled,
        status: input.status,
        transmission: input.transmissionType || null,
        usage_profile: input.usageProfile || null,
        vehicle_code: input.code || null,
        vin: input.vin || null,
        year: input.year === '' ? null : Number(input.year),
    };
}

function normalizeOwnership(raw: BackendRecord): VehicleOwnership {
    const ownerType = normalizeOwnerType(raw.owner_type ?? raw.ownerType);
    const ownerId = asOptionalString(raw.owner_id ?? raw.ownerId);
    const ownerName = asOptionalString(raw.owner_name ?? raw.ownerName ?? raw.owner_display_name ?? raw.ownerDisplayName);

    return {
        endDate: asOptionalString(raw.end_date ?? raw.endDate),
        id: asString(raw.id),
        isCurrent: Boolean(raw.is_current ?? raw.isCurrent ?? true),
        notes: asOptionalString(raw.notes),
        ownerDisplayName: ownerName ?? (ownerId ? `${ownerType} #${ownerId}` : ownerType),
        ownerId,
        ownerName,
        ownerType,
        ownershipRole: normalizeOwnershipRole(raw.ownership_role ?? raw.ownershipRole),
        ownershipType: normalizeOwnershipType(raw.ownership_type ?? raw.ownershipType),
        partyId: asOptionalString(raw.party_id ?? raw.partyId),
        startDate: asString(raw.start_date ?? raw.startDate, 'Not provided'),
        vehicleId: asString(raw.vehicle_id ?? raw.vehicleId),
    };
}

export const vehicleApi = {
    create: (input: VehicleFormInput): Promise<ApiResponse<Vehicle>> =>
        withMockFallback(
            async () => {
                const response = await httpClient<ApiResponse<BackendRecord>, BackendRecord>('/api/vehicle/vehicles', {
                    body: toBackendVehiclePayload(input),
                    method: 'POST',
                });

                return { ...response, data: normalizeVehicle(response.data) };
            },
            () =>
                mockResponse({
                    brand: input.brand,
                    category: input.category,
                    code: input.code || 'VEH-DRAFT',
                    currentOdometer: input.currentOdometer || '0',
                    id: `veh-${Date.now()}`,
                    model: input.model,
                    registrationNumber: input.registrationNumber,
                    rentalEligibility: input.rentalEnabled ? 'Enabled by backend' : 'Disabled by backend',
                    serviceEligibility: input.serviceEnabled ? 'Enabled by backend' : 'Disabled by backend',
                    status: input.status,
                    type: input.category || 'Vehicle',
                    usageProfile: input.usageProfile,
                }),
        ),
    get: (vehicleId: string): Promise<ApiResponse<Vehicle>> =>
        withMockFallback(
            async () => {
                const response = await httpClient<ApiResponse<BackendRecord>>(`/api/vehicle/vehicles/${vehicleId}`);

                return { ...response, data: normalizeVehicle(response.data) };
            },
            () => mockResponse(getVehicleById(vehicleId)),
        ),
    getCurrentOwnership: (vehicleId: string, role: VehicleOwnershipRole = 'legal_owner'): Promise<ApiResponse<VehicleOwnership | null>> =>
        withMockFallback(
            async () => {
                const response = await httpClient<ApiResponse<BackendRecord | null>>(`/api/vehicle/vehicles/${vehicleId}/ownerships/current`, {
                    query: { ownership_role: role },
                });

                return { ...response, data: response.data ? normalizeOwnership(response.data) : null };
            },
            () =>
                mockResponse(
                    vehicleOwnerships.find((ownership) => ownership.vehicleId === vehicleId && ownership.ownershipRole === role && ownership.isCurrent) ?? null,
                ),
        ),
    list: (): Promise<ApiCollectionResponse<Vehicle>> =>
        withMockFallback(
            async () => {
                const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/vehicle/vehicles');

                return { ...response, data: response.data.map(normalizeVehicle) };
            },
            () => mockCollectionResponse(vehicleRecords),
        ),
    listOwnerships: (vehicleId: string): Promise<ApiCollectionResponse<VehicleOwnership>> =>
        withMockFallback(
            async () => {
                const response = await httpClient<ApiResponse<BackendRecord[]>>(`/api/vehicle/vehicles/${vehicleId}/ownerships`);
                const rows = Array.isArray(response.data) ? response.data : [];

                return { data: rows.map(normalizeOwnership) };
            },
            () => mockCollectionResponse(vehicleOwnerships.filter((ownership) => ownership.vehicleId === vehicleId)),
        ),
    previewAvailability: (vehicleId: string) =>
        mockPreviewResponse({ vehicleId }, { note: 'Backend vehicle availability preview placeholder' }),
    update: (vehicleId: string, input: VehicleFormInput): Promise<ApiResponse<Vehicle>> =>
        withMockFallback(
            async () => {
                const response = await httpClient<ApiResponse<BackendRecord>, BackendRecord>(`/api/vehicle/vehicles/${vehicleId}`, {
                    body: toBackendVehiclePayload(input),
                    method: 'PUT',
                });

                return { ...response, data: normalizeVehicle(response.data) };
            },
            () =>
                mockResponse({
                    ...getVehicleById(vehicleId),
                    brand: input.brand,
                    category: input.category,
                    code: input.code,
                    currentOdometer: input.currentOdometer,
                    model: input.model,
                    registrationNumber: input.registrationNumber,
                    rentalEligibility: input.rentalEnabled ? 'Enabled by backend' : 'Disabled by backend',
                    serviceEligibility: input.serviceEnabled ? 'Enabled by backend' : 'Disabled by backend',
                    status: input.status,
                    usageProfile: input.usageProfile,
                }),
        ),
};
