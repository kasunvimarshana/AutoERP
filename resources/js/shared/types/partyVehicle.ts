export type VehicleOwnerType = 'customer' | 'supplier' | 'company';

export interface PartyVehicleOwner {
    id: number | null;
    code: string;
    name: string;
}

export interface PartyVehicleVehicle {
    id: number;
    number: string;
    registration_number?: string | null;
    chassis_number?: string | null;
    make?: string | null;
    model?: string | null;
}

export interface PartyVehicleRelationship {
    id: number;
    row_version: number;
    owner_type: VehicleOwnerType;
    owner_id: number | null;
    owner: PartyVehicleOwner;
    vehicle: PartyVehicleVehicle;
    ownership_type: string;
    started_at: string;
    ended_at?: string | null;
    is_current: boolean;
    supersedes_ownership_id?: number | null;
    correction_reason?: string | null;
    notes?: string | null;
    created_at?: string;
    updated_at?: string;
}

export interface CreatePartyVehiclePayload {
    vehicle_id: number;
    owner_type: VehicleOwnerType;
    owner_id: number | null;
    ownership_type: string;
    started_at: string;
    ended_at?: string | null;
    is_current: boolean;
    notes?: string | null;
}

export interface SupersedePartyVehiclePayload {
    expected_version: number;
    correction_reason: string;
    owner_type: VehicleOwnerType;
    owner_id: number | null;
    ownership_type: string;
    started_at: string;
    ended_at?: string | null;
    is_current: boolean;
    notes?: string | null;
}
