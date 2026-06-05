export type VehicleStatus = 'active' | 'inactive';

export type VehicleListItem = {
    chassisNumber?: string;
    color?: string;
    createdAt: string;
    engineNumber?: string;
    fuelType?: string;
    id: number;
    make?: string;
    model?: string;
    organizationUnitId?: number;
    ownershipType?: string;
    registrationNumber: string;
    status: VehicleStatus;
    transmissionType?: string;
    updatedAt: string;
    vehicleCode: string;
    vehicleType?: string;
    year?: number;
};

export type Vehicle = VehicleListItem & {
    notes?: string;
    rowVersion: number;
    tenantId: number;
};

export type VehicleInput = {
    chassisNumber?: string;
    color?: string;
    engineNumber?: string;
    fuelType?: string;
    make?: string;
    model?: string;
    notes?: string;
    organizationUnitId?: number;
    ownershipType?: string;
    registrationNumber: string;
    status: VehicleStatus;
    transmissionType?: string;
    vehicleCode: string;
    vehicleType?: string;
    year?: number;
};

export type VehicleListQuery = {
    page: number;
    perPage: number;
    search?: string;
    status?: VehicleStatus;
};

export type VehiclePage = {
    items: VehicleListItem[];
    meta: {
        currentPage: number;
        lastPage: number;
        perPage: number;
        total: number;
    };
};
