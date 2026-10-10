import { AGREEMENT_API } from './agreements';

export const INCIDENT_REGISTER_PATH = '/vehicle-rental/incidents';
export const INCIDENT_API = `${AGREEMENT_API}/incidents`;
export const INCIDENT_PERMISSION = {
    view: 'vehicle-rental.incidents.view',
    record: 'vehicle-rental.incidents.record',
    review: 'vehicle-rental.incidents.review',
} as const;

export enum IncidentType {
    Fuel = 'fuel', Toll = 'toll', Parking = 'parking', Repair = 'repair',
    Maintenance = 'maintenance', AccidentDamage = 'accident_damage',
    Penalty = 'penalty', Cleaning = 'cleaning', Other = 'other',
}
export enum IncidentStatus { Recorded = 'recorded', Confirmed = 'confirmed', Rejected = 'rejected' }
export enum IncidentReviewAction { Confirm = 'confirm', Reject = 'reject' }

export const INCIDENT_TYPE_LABELS: Record<IncidentType, string> = {
    [IncidentType.Fuel]: 'Fuel',
    [IncidentType.Toll]: 'Highway / toll',
    [IncidentType.Parking]: 'Parking',
    [IncidentType.Repair]: 'Repair',
    [IncidentType.Maintenance]: 'Maintenance',
    [IncidentType.AccidentDamage]: 'Accident / damage',
    [IncidentType.Penalty]: 'Penalty',
    [IncidentType.Cleaning]: 'Cleaning',
    [IncidentType.Other]: 'Other',
};

export interface RentalIncident {
    id: number;
    reference: string;
    row_version: number;
    incident_type: IncidentType;
    status: IncidentStatus;
    occurred_on: string;
    evidence_reference: string;
    description: string;
    vehicle_use_id: number;
    running_chart_id: number | null;
    vehicle: { label: string; customer_agreement: string | null; owner_agreement: string | null };
    running_chart: string | null;
    reviewed_at: string | null;
}

export interface IncidentFormData {
    vehicle_use_id: number;
    incident_type: IncidentType;
    occurred_on: string;
    evidence_reference: string;
    description: string;
}
