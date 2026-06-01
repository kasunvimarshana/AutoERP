import type { ApiCollectionResponse, ApiResponse } from '../../../services/api/apiResponse';
import { httpClient } from '../../../services/api/httpClient';
import type {
    Vehicle,
    VehicleDocument,
    VehicleFormInput,
    VehicleListQuery,
    VehicleMasterSummary,
    VehicleOwnership,
    VehicleOwnershipFormInput,
    VehicleOwnershipRole,
    VehicleOwnerType,
    VehicleOwnershipType,
    VehicleStatus,
    VehicleUsageProfile,
    VehicleValidationResult,
} from '../types/vehicle.types';

type BackendRecord = Record<string, unknown>;

function asRecord(value: unknown): BackendRecord {
    return value && typeof value === 'object' && !Array.isArray(value) ? (value as BackendRecord) : {};
}

function asString(value: unknown, fallback = ''): string {
    if (value === null || value === undefined) {
        return fallback;
    }

    return String(value);
}

function asOptionalString(value: unknown): string | undefined {
    const normalized = asString(value).trim();

    return normalized === '' ? undefined : normalized;
}

function asBoolean(value: unknown, fallback = false): boolean {
    if (typeof value === 'boolean') {
        return value;
    }

    if (typeof value === 'number') {
        return value === 1;
    }

    if (typeof value === 'string') {
        return ['1', 'true', 'yes'].includes(value.toLowerCase());
    }

    return fallback;
}

function normalizeStatus(value: unknown): VehicleStatus {
    const status = asString(value, 'draft').toLowerCase();
    const allowed: VehicleStatus[] = ['active', 'archived', 'draft', 'in_rental', 'in_service', 'inactive', 'sold', 'under_maintenance', 'unavailable'];

    return allowed.includes(status as VehicleStatus) ? (status as VehicleStatus) : 'draft';
}

function normalizeUsageProfile(value: unknown): VehicleUsageProfile {
    const profile = asString(value, 'dual').toLowerCase();
    const allowed: VehicleUsageProfile[] = ['dual', 'internal', 'rent_only', 'service_only'];

    return allowed.includes(profile as VehicleUsageProfile) ? (profile as VehicleUsageProfile) : 'dual';
}

function normalizeOwnershipType(value: unknown): VehicleOwnershipType {
    const type = asString(value, 'other').toLowerCase();
    const allowed: VehicleOwnershipType[] = ['customer', 'external', 'financed', 'internal', 'leased', 'other', 'own', 'partner', 'provider', 'supplier'];

    return allowed.includes(type as VehicleOwnershipType) ? (type as VehicleOwnershipType) : 'other';
}

function normalizeOwnerType(value: unknown): VehicleOwnerType {
    const type = asString(value, 'other').toLowerCase();
    const allowed: VehicleOwnerType[] = ['company', 'customer', 'employee', 'external_party', 'other', 'partner', 'party', 'provider', 'supplier'];

    return allowed.includes(type as VehicleOwnerType) ? (type as VehicleOwnerType) : 'other';
}

function normalizeOwnershipRole(value: unknown): VehicleOwnershipRole {
    const role = asString(value, 'legal_owner').toLowerCase();
    const allowed: VehicleOwnershipRole[] = ['current_holder', 'legal_owner', 'operational_owner', 'provider', 'registered_owner'];

    return allowed.includes(role as VehicleOwnershipRole) ? (role as VehicleOwnershipRole) : 'legal_owner';
}

function toNullableNumber(value: string): number | null {
    const trimmed = value.trim();
    if (trimmed === '') {
        return null;
    }

    const number = Number(trimmed);

    return Number.isFinite(number) ? number : null;
}

function normalizeVehicle(raw: BackendRecord): Vehicle {
    return {
        category: asOptionalString(raw.category),
        code: asString(raw.vehicle_code ?? raw.code),
        color: asOptionalString(raw.color),
        createdAt: asOptionalString(raw.created_at),
        currentOdometer: asString(raw.current_odometer, '0'),
        fuelType: asOptionalString(raw.fuel_type),
        id: asString(raw.id),
        insuranceExpiry: asOptionalString(raw.insurance_expiry),
        lastServiceDate: asOptionalString(raw.last_service_date),
        lastServiceOdometer: asOptionalString(raw.last_service_odometer),
        make: asOptionalString(raw.make),
        model: asOptionalString(raw.model),
        nextServiceDueDate: asOptionalString(raw.next_service_due_date),
        nextServiceDueOdometer: asOptionalString(raw.next_service_due_odometer),
        organizationUnitId: asOptionalString(raw.organization_unit_id),
        registrationExpiry: asOptionalString(raw.registration_expiry),
        registrationNumber: asOptionalString(raw.license_plate ?? raw.registration_number),
        rentalEnabled: asBoolean(raw.rental_enabled),
        seatingCapacity: asOptionalString(raw.seating_capacity),
        serviceEnabled: asBoolean(raw.service_enabled, true),
        status: normalizeStatus(raw.status),
        tenantId: asOptionalString(raw.tenant_id),
        transmission: asOptionalString(raw.transmission),
        updatedAt: asOptionalString(raw.updated_at),
        usageProfile: normalizeUsageProfile(raw.usage_profile),
        vin: asOptionalString(raw.vin),
        year: asOptionalString(raw.year),
    };
}

function normalizeOwnership(raw: BackendRecord): VehicleOwnership {
    const ownerType = normalizeOwnerType(raw.owner_type);
    const ownerId = asOptionalString(raw.owner_id);
    const partyId = asOptionalString(raw.party_id);
    const ownerName = asOptionalString(raw.owner_name);

    return {
        endDate: asOptionalString(raw.end_date),
        id: asString(raw.id),
        isCurrent: asBoolean(raw.is_current, true),
        notes: asOptionalString(raw.notes),
        ownerDisplayName: ownerName ?? (ownerId ? `${ownerType} #${ownerId}` : partyId ? `party #${partyId}` : ownerType),
        ownerId,
        ownerName,
        ownerType,
        ownershipRole: normalizeOwnershipRole(raw.ownership_role),
        ownershipType: normalizeOwnershipType(raw.ownership_type),
        partyId,
        startDate: asString(raw.start_date),
        vehicleId: asString(raw.vehicle_id),
    };
}

function normalizeDocument(raw: BackendRecord): VehicleDocument {
    return {
        documentNumber: asOptionalString(raw.document_number ?? raw.reference_number),
        documentType: asString(raw.document_type ?? raw.type, 'Document'),
        expiryDate: asOptionalString(raw.expiry_date),
        id: asString(raw.id),
        status: asString(raw.status, 'active'),
        title: asString(raw.title ?? raw.document_name ?? raw.file_name, 'Vehicle document'),
    };
}

function toBackendVehiclePayload(input: VehicleFormInput): BackendRecord {
    return {
        category: input.category || null,
        color: input.color || null,
        current_odometer: toNullableNumber(input.currentOdometer) ?? 0,
        fuel_type: input.fuelType || null,
        insurance_expiry: input.insuranceExpiry || null,
        last_service_date: input.lastServiceDate || null,
        last_service_odometer: toNullableNumber(input.lastServiceOdometer),
        license_plate: input.registrationNumber || null,
        make: input.make || null,
        model: input.model || null,
        next_service_due_date: input.nextServiceDueDate || null,
        next_service_due_odometer: toNullableNumber(input.nextServiceDueOdometer),
        registration_expiry: input.registrationExpiry || null,
        rental_enabled: input.rentalEnabled,
        seating_capacity: toNullableNumber(input.seatingCapacity),
        service_enabled: input.serviceEnabled,
        status: input.status,
        transmission: input.transmission || null,
        usage_profile: input.usageProfile,
        vehicle_code: input.code || null,
        vin: input.vin || null,
        year: toNullableNumber(input.year),
    };
}

function toBackendOwnershipPayload(input: VehicleOwnershipFormInput): BackendRecord {
    return {
        end_date: input.endDate || null,
        is_current: input.isCurrent,
        notes: input.notes || null,
        owner_id: toNullableNumber(input.ownerId),
        owner_name: input.ownerName || null,
        owner_type: input.ownerType,
        ownership_role: input.ownershipRole,
        ownership_type: input.ownershipType,
        party_id: toNullableNumber(input.partyId),
        start_date: input.startDate,
    };
}

function normalizeValidation(raw: BackendRecord): VehicleValidationResult {
    return {
        isValid: asBoolean(raw.is_valid),
        reason: asString(raw.reason, 'Backend validation completed.'),
        usage: asString(raw.usage, 'service') === 'rental' ? 'rental' : 'service',
        vehicle: normalizeVehicle(asRecord(raw.vehicle)),
    };
}

function summarizeVehicles(kind: VehicleMasterSummary['kind'], vehicles: Vehicle[]): VehicleMasterSummary[] {
    const valueFor = (vehicle: Vehicle) => {
        if (kind === 'brand') {
            return vehicle.make;
        }

        if (kind === 'model') {
            return vehicle.model;
        }

        if (kind === 'category') {
            return vehicle.category;
        }

        return vehicle.usageProfile;
    };

    const buckets = new Map<string, VehicleMasterSummary>();
    vehicles.forEach((vehicle) => {
        const name = valueFor(vehicle)?.trim() || 'Unspecified';
        const existing = buckets.get(name);

        if (existing) {
            existing.recordCount += 1;
            existing.latestStatus = vehicle.status;

            return;
        }

        buckets.set(name, {
            id: `${kind}-${name.toLowerCase().replace(/[^a-z0-9]+/g, '-')}`,
            kind,
            latestStatus: vehicle.status,
            name,
            recordCount: 1,
        });
    });

    return Array.from(buckets.values()).sort((left, right) => left.name.localeCompare(right.name));
}

export const vehicleApi = {
    create: async (input: VehicleFormInput): Promise<ApiResponse<Vehicle>> => {
        const response = await httpClient<ApiResponse<BackendRecord>, BackendRecord>('/api/vehicle/vehicles', {
            body: toBackendVehiclePayload(input),
            method: 'POST',
        });

        return { ...response, data: normalizeVehicle(response.data) };
    },
    createOwnership: async (vehicleId: string, input: VehicleOwnershipFormInput): Promise<ApiResponse<VehicleOwnership>> => {
        const response = await httpClient<ApiResponse<BackendRecord>, BackendRecord>(`/api/vehicle/vehicles/${vehicleId}/ownerships`, {
            body: toBackendOwnershipPayload(input),
            method: 'POST',
        });

        return { ...response, data: normalizeOwnership(response.data) };
    },
    endOwnership: async (vehicleId: string, ownershipId: string, endDate: string): Promise<ApiResponse<VehicleOwnership>> => {
        const response = await httpClient<ApiResponse<BackendRecord>, BackendRecord>(`/api/vehicle/vehicles/${vehicleId}/ownerships/${ownershipId}/end`, {
            body: { end_date: endDate },
            method: 'POST',
        });

        return { ...response, data: normalizeOwnership(response.data) };
    },
    get: async (vehicleId: string): Promise<ApiResponse<Vehicle>> => {
        const response = await httpClient<ApiResponse<BackendRecord>>(`/api/vehicle/vehicles/${vehicleId}`);

        return { ...response, data: normalizeVehicle(response.data) };
    },
    getCurrentOwnership: async (vehicleId: string, role: VehicleOwnershipRole = 'legal_owner'): Promise<ApiResponse<VehicleOwnership | null>> => {
        const response = await httpClient<ApiResponse<BackendRecord | null>>(`/api/vehicle/vehicles/${vehicleId}/ownerships/current`, {
            query: { ownership_role: role },
        });

        return { ...response, data: response.data ? normalizeOwnership(response.data) : null };
    },
    list: async (query: VehicleListQuery = {}): Promise<ApiCollectionResponse<Vehicle>> => {
        const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/vehicle/vehicles', {
            query: {
                page: query.page,
                per_page: query.perPage ?? 50,
                rental_enabled: query.rentalEnabled,
                search: query.search,
                service_enabled: query.serviceEnabled,
                status: query.status,
            },
        });

        return { ...response, data: response.data.map(normalizeVehicle) };
    },
    listDocuments: async (vehicleId: string): Promise<ApiCollectionResponse<VehicleDocument>> => {
        const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/vehicle/vehicle-documents', {
            query: { vehicle_id: vehicleId },
        });

        return { ...response, data: response.data.map(normalizeDocument) };
    },
    listMasterSummaries: async (kind: VehicleMasterSummary['kind']): Promise<ApiCollectionResponse<VehicleMasterSummary>> => {
        const response = await vehicleApi.list({ perPage: 50 });

        return { data: summarizeVehicles(kind, response.data) };
    },
    listOwnerships: async (vehicleId: string): Promise<ApiCollectionResponse<VehicleOwnership>> => {
        const response = await httpClient<ApiResponse<BackendRecord[]>>(`/api/vehicle/vehicles/${vehicleId}/ownerships`);
        const rows = Array.isArray(response.data) ? response.data : [];

        return { data: rows.map(normalizeOwnership) };
    },
    lookup: async (query: VehicleListQuery = {}): Promise<ApiCollectionResponse<Vehicle>> => {
        const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/vehicle/vehicles/lookup', {
            query: {
                page: query.page,
                per_page: query.perPage ?? 50,
                rental_enabled: query.rentalEnabled,
                search: query.search,
                service_enabled: query.serviceEnabled,
                status: query.status,
            },
        });

        return { ...response, data: response.data.map(normalizeVehicle) };
    },
    setCurrentOwnership: async (vehicleId: string, ownershipId: string): Promise<ApiResponse<VehicleOwnership>> => {
        const response = await httpClient<ApiResponse<BackendRecord>>(`/api/vehicle/vehicles/${vehicleId}/ownerships/${ownershipId}/set-current`, {
            method: 'POST',
        });

        return { ...response, data: normalizeOwnership(response.data) };
    },
    update: async (vehicleId: string, input: VehicleFormInput): Promise<ApiResponse<Vehicle>> => {
        const response = await httpClient<ApiResponse<BackendRecord>, BackendRecord>(`/api/vehicle/vehicles/${vehicleId}`, {
            body: toBackendVehiclePayload(input),
            method: 'PUT',
        });

        return { ...response, data: normalizeVehicle(response.data) };
    },
    updateOwnership: async (vehicleId: string, ownershipId: string, input: VehicleOwnershipFormInput): Promise<ApiResponse<VehicleOwnership>> => {
        const response = await httpClient<ApiResponse<BackendRecord>, BackendRecord>(`/api/vehicle/vehicles/${vehicleId}/ownerships/${ownershipId}`, {
            body: toBackendOwnershipPayload(input),
            method: 'PUT',
        });

        return { ...response, data: normalizeOwnership(response.data) };
    },
    validateUsage: async (vehicleId: string, usage: VehicleValidationResult['usage']): Promise<ApiResponse<VehicleValidationResult>> => {
        const response = await httpClient<ApiResponse<BackendRecord>>(`/api/vehicle/vehicles/${vehicleId}/validate/${usage}`);

        return { ...response, data: normalizeValidation(response.data) };
    },
};
