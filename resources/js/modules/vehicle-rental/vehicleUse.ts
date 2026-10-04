import {
    businessDateTimeInputValue,
    businessDateTimeToOffsetTimestamp,
    businessTimeZone,
    formatBusinessDateTime,
} from '@/shared/utils/businessDate';
import { AGREEMENT_API } from './agreements';

export enum VehicleUseStatus { Planned = 'planned', InCustody = 'in_custody', Returned = 'returned', Cancelled = 'cancelled' }
export enum VehicleUseAction { Handover = 'handover', Return = 'return', Cancel = 'cancel' }
export const USE_PERMISSION = { view: 'vehicle-rental.vehicle-use.view', manage: 'vehicle-rental.vehicle-use.manage' } as const;
export const USE_API = `${AGREEMENT_API}/vehicle-uses`;
export const USE_LABELS = { [VehicleUseStatus.Planned]: 'Planned', [VehicleUseStatus.InCustody]: 'With customer', [VehicleUseStatus.Returned]: 'Returned', [VehicleUseStatus.Cancelled]: 'Cancelled' };
export const USE_ACTION_LABELS = { [VehicleUseAction.Handover]: 'Hand over vehicle', [VehicleUseAction.Return]: 'Record return', [VehicleUseAction.Cancel]: 'Cancel plan' };
interface AgreementReference { id: number; reference: string; party_name: string; version: number }
export interface VehicleUse {
    id: number; row_version: number; status: VehicleUseStatus;
    replaces_use?: { id: number; vehicle_label: string } | null;
    customer_agreement: AgreementReference; owner_agreement: AgreementReference | null;
    vehicle: { id: number; label: string }; starts_at: string; ends_at: string | null;
    handed_over_at: string | null; returned_at: string | null; handover_odometer: string | null; return_odometer: string | null; notes: string | null;
}
export interface VehicleUseHistory {
    version: number; action: string; reason: string | null; recorded_at: string; actor: { name: string };
    vehicle_label: string; status: VehicleUseStatus; starts_at: string; ends_at: string | null;
    handed_over_at: string | null; returned_at: string | null; handover_odometer: string | null; return_odometer: string | null;
}

export const OPERATIONAL_TIME_STEP_SECONDS = 1;

export function operationalTimeZone(): string {
    return businessTimeZone();
}

export function timestampWithOffset(value: string): string {
    return businessDateTimeToOffsetTimestamp(value);
}

export function localTimestampValue(value: string): string {
    const date = new Date(value);
    return Number.isNaN(date.getTime()) ? '' : businessDateTimeInputValue(date, 0, undefined, true);
}

export function formatOperationalDateTime(value: string | null | undefined, fallback = 'Not recorded'): string {
    return formatBusinessDateTime(value, fallback);
}

export const USE_REGISTER_PATH = '/vehicle-rental/vehicle-uses';
export interface VehicleUseRegisterFilters { search?: string; use_status?: VehicleUseStatus; from?: string; until?: string }
