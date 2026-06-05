import type { ApiCollectionResponse, ApiResponse } from '../../../services/api/apiResponse';
import { httpClient } from '../../../services/api/httpClient';
import type { Vehicle, VehicleInput, VehicleListItem, VehicleListQuery, VehiclePage } from '../types/vehicle.types';

type VehicleRecord = {
    chassis_number?: string | null;
    color?: string | null;
    created_at: string;
    engine_number?: string | null;
    fuel_type?: string | null;
    id: number;
    make?: string | null;
    model?: string | null;
    notes?: string | null;
    organization_unit_id?: number | null;
    ownership_type?: string | null;
    registration_number: string;
    row_version?: number;
    status: 'active' | 'inactive';
    tenant_id?: number;
    transmission_type?: string | null;
    updated_at: string;
    vehicle_code: string;
    vehicle_type?: string | null;
    year?: number | null;
};

function listItem(record: VehicleRecord): VehicleListItem {
    return {
        chassisNumber: record.chassis_number ?? undefined,
        color: record.color ?? undefined,
        createdAt: record.created_at,
        engineNumber: record.engine_number ?? undefined,
        fuelType: record.fuel_type ?? undefined,
        id: record.id,
        make: record.make ?? undefined,
        model: record.model ?? undefined,
        organizationUnitId: record.organization_unit_id ?? undefined,
        ownershipType: record.ownership_type ?? undefined,
        registrationNumber: record.registration_number,
        status: record.status,
        transmissionType: record.transmission_type ?? undefined,
        updatedAt: record.updated_at,
        vehicleCode: record.vehicle_code,
        vehicleType: record.vehicle_type ?? undefined,
        year: record.year ?? undefined,
    };
}

function detail(record: VehicleRecord): Vehicle {
    return {
        ...listItem(record),
        notes: record.notes ?? undefined,
        rowVersion: record.row_version ?? 1,
        tenantId: record.tenant_id ?? 0,
    };
}

function optional(value?: string) {
    return value?.trim() || null;
}

function payload(input: VehicleInput) {
    return {
        chassis_number: optional(input.chassisNumber),
        color: optional(input.color),
        engine_number: optional(input.engineNumber),
        fuel_type: optional(input.fuelType),
        make: optional(input.make),
        model: optional(input.model),
        notes: optional(input.notes),
        organization_unit_id: input.organizationUnitId,
        ownership_type: optional(input.ownershipType),
        registration_number: input.registrationNumber.trim(),
        status: input.status,
        transmission_type: optional(input.transmissionType),
        vehicle_code: input.vehicleCode.trim(),
        vehicle_type: optional(input.vehicleType),
        year: input.year,
    };
}

export const vehicleApi = {
    async create(input: VehicleInput): Promise<Vehicle> {
        const response = await httpClient<ApiResponse<VehicleRecord>>('/api/vehicle/vehicles', {
            body: payload(input),
            method: 'POST',
        });

        return detail(response.data);
    },
    async get(id: number): Promise<Vehicle> {
        const response = await httpClient<ApiResponse<VehicleRecord>>(`/api/vehicle/vehicles/${id}`);

        return detail(response.data);
    },
    async list(query: VehicleListQuery): Promise<VehiclePage> {
        const response = await httpClient<ApiCollectionResponse<VehicleRecord>>('/api/vehicle/vehicles', {
            query: {
                page: query.page,
                per_page: query.perPage,
                search: query.search,
                status: query.status,
            },
        });

        return {
            items: response.data.map(listItem),
            meta: {
                currentPage: response.meta?.current_page ?? query.page,
                lastPage: response.meta?.last_page ?? 1,
                perPage: response.meta?.per_page ?? query.perPage,
                total: response.meta?.total ?? response.data.length,
            },
        };
    },
    async remove(id: number): Promise<void> {
        await httpClient<void>(`/api/vehicle/vehicles/${id}`, { method: 'DELETE' });
    },
    async update(id: number, input: VehicleInput): Promise<Vehicle> {
        const response = await httpClient<ApiResponse<VehicleRecord>>(`/api/vehicle/vehicles/${id}`, {
            body: payload(input),
            method: 'PUT',
        });

        return detail(response.data);
    },
};
