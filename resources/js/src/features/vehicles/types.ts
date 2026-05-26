export type VehicleOwnershipType = 'company_owned' | 'third_party_owned' | 'customer_owned' | 'leased';
export type VehicleFuelType = 'petrol' | 'diesel' | 'hybrid' | 'electric' | 'cng' | 'lpg' | 'other';
export type VehicleTransmission = 'manual' | 'automatic' | 'cvt' | 'semi_automatic' | 'other';
export type VehicleRentalStatus = 'available' | 'reserved' | 'rented' | 'blocked';
export type VehicleServiceStatus =
    | 'none'
    | 'in_maintenance'
    | 'under_repair'
    | 'awaiting_parts'
    | 'quality_check'
    | 'ready_for_pickup'
    | 'returned_to_fleet';

export type VehicleMetadata = {
    color?: string | null;
    engine_number?: string | null;
    notes?: string | null;
    [key: string]: unknown;
};

export type VehicleRecord = {
    id: number;
    tenant_id: number;
    org_unit_id?: number | null;
    customer_id?: number | null;
    supplier_id?: number | null;
    ownership_type: VehicleOwnershipType;
    asset_code?: string | null;
    make: string;
    model: string;
    year?: number | null;
    vin: string | null;
    registration_number: string | null;
    chassis_number: string | null;
    fuel_type?: VehicleFuelType | null;
    transmission?: VehicleTransmission | null;
    odometer?: string | number | null;
    rental_status: VehicleRentalStatus;
    service_status: VehicleServiceStatus;
    next_maintenance_due_at: string | null;
    primary_image_path?: string | null;
    metadata?: VehicleMetadata | null;
    is_active?: boolean;
    created_at?: string;
    updated_at?: string;
};

export type VehicleListFilters = {
    tenant_id: number;
    ownership_type?: VehicleOwnershipType;
    rental_status?: VehicleRentalStatus;
    service_status?: VehicleServiceStatus;
    is_active?: boolean;
    make?: string;
    model?: string;
    per_page?: number;
    page?: number;
    sort?: string;
};

export type VehiclePayload = {
    tenant_id: number;
    org_unit_id?: number | null;
    customer_id?: number | null;
    supplier_id?: number | null;
    ownership_type: VehicleOwnershipType;
    asset_code?: string | null;
    make: string;
    model: string;
    year?: number | null;
    vin?: string | null;
    registration_number?: string | null;
    chassis_number?: string | null;
    fuel_type?: VehicleFuelType | null;
    transmission?: VehicleTransmission | null;
    odometer?: number | null;
    rental_status?: VehicleRentalStatus | null;
    service_status?: VehicleServiceStatus | null;
    next_maintenance_due_at?: string | null;
    primary_image_path?: string | null;
    metadata?: VehicleMetadata | null;
    is_active?: boolean;
};

export type VehicleStatusPayload = {
    tenant_id: number;
    rental_status?: VehicleRentalStatus | null;
    service_status?: VehicleServiceStatus | null;
    next_maintenance_due_at?: string | null;
};

export type VehicleDashboardTotals = {
    all?: number;
    rental_available?: number;
    rented?: number;
    in_service?: number;
    awaiting_parts?: number;
    quality_control?: number;
    due_for_maintenance?: number;
};

export type VehicleDocumentAlert = {
    id?: number;
    vehicle_id?: number;
    document_type?: string;
    document_number?: string | null;
    expires_at?: string | null;
    [key: string]: unknown;
};

export type VehicleDashboard = {
    totals?: VehicleDashboardTotals;
    expiring_documents?: VehicleDocumentAlert[];
};
