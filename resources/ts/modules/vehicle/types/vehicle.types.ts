export type VehicleStatus =
    | 'active'
    | 'archived'
    | 'draft'
    | 'in_rental'
    | 'in_service'
    | 'inactive'
    | 'sold'
    | 'under_maintenance'
    | 'unavailable';

export type VehicleUsageProfile = 'dual' | 'internal' | 'rent_only' | 'service_only';

export type VehicleOwnershipType =
    | 'customer'
    | 'external'
    | 'financed'
    | 'internal'
    | 'leased'
    | 'other'
    | 'own'
    | 'partner'
    | 'provider'
    | 'supplier';

export type VehicleOwnerType =
    | 'company'
    | 'customer'
    | 'employee'
    | 'external_party'
    | 'other'
    | 'partner'
    | 'party'
    | 'provider'
    | 'supplier';

export type VehicleOwnershipRole = 'current_holder' | 'legal_owner' | 'operational_owner' | 'provider' | 'registered_owner';

export type Vehicle = {
    category?: string;
    code: string;
    color?: string;
    createdAt?: string;
    currentOdometer: string;
    fuelType?: string;
    id: string;
    insuranceExpiry?: string;
    lastServiceDate?: string;
    lastServiceOdometer?: string;
    make?: string;
    model?: string;
    nextServiceDueDate?: string;
    nextServiceDueOdometer?: string;
    organizationUnitId?: string;
    registrationExpiry?: string;
    registrationNumber?: string;
    rentalEnabled: boolean;
    seatingCapacity?: string;
    serviceEnabled: boolean;
    status: VehicleStatus | string;
    tenantId?: string;
    transmission?: string;
    updatedAt?: string;
    usageProfile: VehicleUsageProfile | string;
    vin?: string;
    year?: string;
};

export type VehicleFormInput = {
    category: string;
    code: string;
    color: string;
    currentOdometer: string;
    fuelType: string;
    insuranceExpiry: string;
    lastServiceDate: string;
    lastServiceOdometer: string;
    make: string;
    model: string;
    nextServiceDueDate: string;
    nextServiceDueOdometer: string;
    registrationExpiry: string;
    registrationNumber: string;
    rentalEnabled: boolean;
    seatingCapacity: string;
    serviceEnabled: boolean;
    status: VehicleStatus;
    transmission: string;
    usageProfile: VehicleUsageProfile;
    vin: string;
    year: string;
};

export type VehicleOwnership = {
    endDate?: string;
    id: string;
    isCurrent: boolean;
    notes?: string;
    ownerDisplayName: string;
    ownerId?: string;
    ownerName?: string;
    ownerType: VehicleOwnerType;
    ownershipRole: VehicleOwnershipRole;
    ownershipType: VehicleOwnershipType;
    partyId?: string;
    startDate: string;
    vehicleId: string;
};

export type VehicleOwnershipFormInput = {
    endDate: string;
    isCurrent: boolean;
    notes: string;
    ownerId: string;
    ownerName: string;
    ownerType: VehicleOwnerType;
    ownershipRole: VehicleOwnershipRole;
    ownershipType: VehicleOwnershipType;
    partyId: string;
    startDate: string;
};

export type VehicleDocument = {
    documentNumber?: string;
    documentType: string;
    expiryDate?: string;
    id: string;
    status: string;
    title: string;
};

export type VehicleValidationResult = {
    isValid: boolean;
    reason: string;
    usage: 'rental' | 'service';
    vehicle: Vehicle;
};

export type VehicleMasterSummary = {
    id: string;
    kind: 'brand' | 'category' | 'model' | 'type';
    latestStatus: string;
    name: string;
    recordCount: number;
};

export type VehicleListQuery = {
    page?: number;
    perPage?: number;
    rentalEnabled?: boolean;
    search?: string;
    serviceEnabled?: boolean;
    status?: VehicleStatus | string;
};

export type VehicleFieldErrors = Record<string, string[]>;
