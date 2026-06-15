import type { NamedResource } from '@/shared/types/common';

export type VehicleStatus = 'active' | 'inactive' | 'under_service' | 'rented' | 'reserved' | 'sold' | 'blocked' | 'scrapped';
export type VehicleDocumentStatus = 'active' | 'expired' | 'revoked' | 'pending';
export type VehicleDocumentType = 'registration' | 'insurance' | 'emission_test' | 'revenue_license' | 'fitness_certificate' | 'lease_document' | 'ownership_document' | 'warranty' | 'other';
export type VehicleOwnershipType = 'owned' | 'customer_owned' | 'leased' | 'rented' | 'company_owned' | 'third_party';
export type VehicleOwnerType = 'company' | 'customer' | 'supplier' | 'third_party';
export type VehicleScope = 'all' | 'fleet' | 'customer' | 'supplier_owner';
export type VehicleAttributeDataType = 'text' | 'number' | 'date' | 'boolean' | 'decimal';

export interface VehicleMake extends NamedResource {
    description?: string | null;
    is_active: boolean;
}

export interface VehicleModel extends NamedResource {
    make?: NamedResource | null;
    year_from?: number | null;
    year_to?: number | null;
    description?: string | null;
    is_active: boolean;
}

export interface VehicleType extends NamedResource {
    description?: string | null;
    is_active: boolean;
    sort_order: number;
}

export interface VehicleCategory extends NamedResource {
    parent?: NamedResource | null;
    description?: string | null;
    is_active: boolean;
    sort_order: number;
}

export interface VehicleSummary {
    id: number;
    vehicle_number: string;
    code?: string | null;
    registration_number?: string | null;
    chassis_number?: string | null;
    engine_number?: string | null;
    vin_number?: string | null;
    make?: NamedResource | null;
    model?: NamedResource | null;
    type?: NamedResource | null;
    category?: NamedResource | null;
    customer?: NamedResource | null;
    current_ownership?: VehicleOwnership | null;
    status: VehicleStatus;
    odometer_reading: string;
    odometer_unit?: string | null;
    created_at?: string | null;
    updated_at?: string | null;
}

export interface Vehicle extends VehicleSummary {
    current_owner_type?: VehicleOwnerType | null;
    current_owner_id?: number | null;
    manufacture_year?: number | null;
    registration_date?: string | null;
    color?: string | null;
    fuel_type?: string | null;
    transmission_type?: string | null;
    fuel_level?: string | null;
    notes?: string | null;
    metadata?: Record<string, unknown> | null;
    approved_at?: string | null;
    current_ownership?: VehicleOwnership | null;
    documents?: VehicleDocument[];
    ownerships?: VehicleOwnership[];
    attributes?: VehicleAttribute[];
    status_history?: VehicleStatusHistory[];
}

export interface VehiclePayload {
    vehicle_number?: string | null;
    code?: string | null;
    vehicle_make_id?: number | null;
    vehicle_model_id?: number | null;
    vehicle_type_id?: number | null;
    vehicle_category_id?: number | null;
    customer_id?: number | null;
    current_owner_type?: string | null;
    current_owner_id?: number | null;
    registration_number?: string | null;
    chassis_number?: string | null;
    engine_number?: string | null;
    vin_number?: string | null;
    manufacture_year?: number | null;
    registration_date?: string | null;
    color?: string | null;
    fuel_type?: string | null;
    transmission_type?: string | null;
    odometer_reading?: string | null;
    odometer_unit?: string | null;
    fuel_level?: string | null;
    status?: VehicleStatus;
    notes?: string | null;
    metadata?: Record<string, unknown> | null;
}

export interface VehicleWithRelationsPayload {
    vehicle: VehiclePayload;
    documents: VehicleDocumentPayload[];
    ownerships: VehicleOwnershipPayload[];
    attributes: VehicleAttributePayload[];
}

export interface VehicleDocument {
    id: number;
    document_type: VehicleDocumentType;
    document_number?: string | null;
    issued_date?: string | null;
    expiry_date?: string | null;
    file_path?: string | null;
    status: VehicleDocumentStatus;
    notes?: string | null;
}

export type VehicleDocumentPayload = Omit<VehicleDocument, 'id'>;

export interface VehicleOwnership {
    id: number;
    owner_type?: VehicleOwnerType | null;
    owner_id?: number | null;
    owner?: NamedResource | null;
    customer?: NamedResource | null;
    ownership_type: VehicleOwnershipType;
    started_at: string;
    ended_at?: string | null;
    is_current: boolean;
    notes?: string | null;
}

export interface VehicleOwnershipPayload {
    owner_type?: VehicleOwnerType | null;
    owner_id?: number | null;
    customer_id?: number | null;
    ownership_type: VehicleOwnershipType;
    started_at: string;
    ended_at?: string | null;
    is_current?: boolean;
    notes?: string | null;
}

export interface VehicleAttribute {
    id: number;
    attribute_key: string;
    attribute_value?: string | null;
    data_type: VehicleAttributeDataType;
    sort_order: number;
}

export type VehicleAttributePayload = Omit<VehicleAttribute, 'id'>;

export interface VehicleStatusHistory {
    id: number;
    old_status?: VehicleStatus | null;
    new_status: VehicleStatus;
    reason?: string | null;
    changed_by?: number | null;
    changed_at?: string | null;
}
