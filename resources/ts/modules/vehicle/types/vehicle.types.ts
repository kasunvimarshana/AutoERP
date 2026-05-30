export type VehicleStatus = 'active' | 'archived' | 'draft' | 'in_rental' | 'in_service' | 'inactive' | 'sold' | 'under_maintenance' | 'unavailable';

export type VehicleOwnershipType = 'customer' | 'external' | 'financed' | 'internal' | 'leased' | 'other' | 'own' | 'partner' | 'provider' | 'supplier';

export type VehicleOwnerType = 'company' | 'customer' | 'employee' | 'external_party' | 'other' | 'partner' | 'party' | 'provider' | 'supplier';

export type VehicleOwnershipRole = 'current_holder' | 'legal_owner' | 'operational_owner' | 'provider' | 'registered_owner';

export type Vehicle = {
    brand: string;
    category: string;
    code: string;
    color?: string;
    currentOdometer: string;
    engineNumber?: string;
    fuelType?: string;
    id: string;
    insuranceExpiry?: string;
    model: string;
    notes?: string;
    registrationExpiry?: string;
    registrationNumber: string;
    rentalEligibility: string;
    serviceEligibility: string;
    status: VehicleStatus | string;
    transmissionType?: string;
    type: string;
    usageProfile: string;
    vin?: string;
    year?: string;
};

export type VehicleFormInput = {
    brand: string;
    category: string;
    code: string;
    color: string;
    currentOdometer: string;
    fuelType: string;
    insuranceExpiry: string;
    model: string;
    registrationExpiry: string;
    registrationNumber: string;
    rentalEnabled: boolean;
    serviceEnabled: boolean;
    status: VehicleStatus;
    transmissionType: string;
    usageProfile: string;
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

export type VehicleAvailabilityPreview = {
    calculated: Record<string, string>;
    input: Record<string, string>;
    warnings: string[];
};
