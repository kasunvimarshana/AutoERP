import { AGREEMENT_API } from './agreements';
import type { VehicleUse } from './vehicleUse';
export enum RunningChartStatus { Draft = 'draft', Finalized = 'finalized', Reversed = 'reversed' }
export enum RunningChartAction { Finalize = 'finalize', Reverse = 'reverse' }
export enum AirConditioningMode { NonAc = 'non_ac', Front = 'front', Dual = 'dual' }
export enum DriverIdentitySource { Employee = 'employee', External = 'external' }
export const CHART_API = `${AGREEMENT_API}/running-charts`;
export const DRIVER_EMPLOYEE_API = `${AGREEMENT_API}/driver-employees`;
export const CHART_PERMISSION = { view: 'vehicle-rental.running-charts.view', manage: 'vehicle-rental.running-charts.manage', finalize: 'vehicle-rental.running-charts.finalize', reverse: 'vehicle-rental.running-charts.reverse' } as const;
export const CHART_LABELS = { [RunningChartStatus.Draft]: 'Draft', [RunningChartStatus.Finalized]: 'Finalized', [RunningChartStatus.Reversed]: 'Reversed' };
export const CHART_ACTION_CONFIRM_LABELS = { [RunningChartAction.Finalize]: 'Confirm finalization', [RunningChartAction.Reverse]: 'Confirm reversal' };
export const DISTANCE_LABELS = { start_odometer: 'Start odometer', end_odometer: 'End odometer', garage_km: 'Garage distance (km)', commercial_km: 'Commercial distance (km)' } as const;
export const COUNT_LABELS = { normal_ot_minutes: 'Normal OT (minutes)', double_ot_minutes: 'Double OT (minutes)', triple_ot_minutes: 'Triple OT (minutes)', night_outs: 'Night-outs' } as const;
export interface ChartFacts {
    reference?: string; starts_at: string; ends_at: string; start_odometer: string | null; end_odometer: string | null; garage_km: string | null; commercial_km: string | null;
    normal_ot_minutes: number | null; double_ot_minutes: number | null; triple_ot_minutes: number | null; night_outs: number | null; ac_mode: AirConditioningMode | null;
    driver_identity_source?: DriverIdentitySource | null; driver_employee_id?: number | null; driver_name_snapshot?: string | null; driver_reference_snapshot?: string | null;
    driver_observation: string | null; notes: string | null;
}
export interface RunningChart extends ChartFacts {
    id: number; reference: string; row_version: number; status: RunningChartStatus; total_km: string | null; corrects_chart: { id: number; reference: string } | null;
    starts_at_input?: string;
    ends_at_input?: string;
    calculated_ot_minutes?: number | null;
    agreed_baseline_hours?: string | number | null;
    corrected_by_chart?: { id: number; reference: string } | null;
    driver?: { source: DriverIdentitySource; employee_id: number | null; name: string; reference: string } | null;
}
export interface ChartHistory { version: number; action: string; reason: string | null; actor: { name: string }; recorded_at: string; facts: ChartFacts }

export const CHART_REGISTER_PATH = '/vehicle-rental/running-charts';
export interface ChartRegisterRow extends RunningChart {
    vehicle_use: { id: number; vehicle_label: string; version?: number; row_version?: number } & Partial<VehicleUse>;
    customer_agreement: {
        id?: number;
        reference: string;
        party_name: string;
        driver_mode?: string;
        working_hours?: string | null;
        terms?: Record<string, string | null>;
    } | null;
    owner_agreement: {
        id?: number;
        reference: string;
        party_name: string;
        driver_mode?: string;
        working_hours?: string | null;
        terms?: Record<string, string | null>;
    } | null;
    replaces_vehicle: string | null;
}
export interface ChartRegisterFilters {
    search?: string;
    chart_status?: RunningChartStatus;
    from?: string;
    until?: string;
    kind?: 'customer' | 'owner' | 'internal';
    customer_agreement_id?: number;
    owner_agreement_id?: number;
    vehicle_use_id?: number;
}

export const AC_LABELS = { [AirConditioningMode.NonAc]: 'Non-AC', [AirConditioningMode.Front]: 'Front AC', [AirConditioningMode.Dual]: 'Dual AC' };

export const DEFAULT_WORKING_HOURS_BASELINE = 8.0;

export function calculateOtMinutes(
    startsAt: string,
    endsAt: string,
    baselineHours: number | string | null = DEFAULT_WORKING_HOURS_BASELINE,
): number {
    const baseline = typeof baselineHours === 'string' ? parseFloat(baselineHours) : (baselineHours ?? 0);
    if (!startsAt || !endsAt || isNaN(baseline) || baseline <= 0) return 0;
    try {
        const startMs = new Date(startsAt).getTime();
        const endMs = new Date(endsAt).getTime();
        if (isNaN(startMs) || isNaN(endMs) || endMs <= startMs) return 0;
        const tripMinutes = Math.floor((endMs - startMs) / 60000);
        const baselineMinutes = Math.round(baseline * 60);
        return Math.max(0, tripMinutes - baselineMinutes);
    } catch {
        return 0;
    }
}

export function formatMinutes(totalMinutes: number | null | undefined): string {
    if (totalMinutes === null || totalMinutes === undefined || isNaN(totalMinutes) || totalMinutes <= 0) {
        return '0 mins';
    }
    const total = Math.round(totalMinutes);
    const hours = Math.floor(total / 60);
    const mins = total % 60;
    if (hours === 0) return `${mins} min${mins !== 1 ? 's' : ''}`;
    if (mins === 0) return `${hours} hr${hours !== 1 ? 's' : ''}`;
    return `${hours} hr${hours !== 1 ? 's' : ''} ${mins} min${mins !== 1 ? 's' : ''}`;
}

export function formatMinutesDecimalHours(totalMinutes: number | null | undefined): string {
    if (totalMinutes === null || totalMinutes === undefined || isNaN(totalMinutes) || totalMinutes <= 0) {
        return '0.00 hrs';
    }
    return `${(totalMinutes / 60).toFixed(2)} hrs`;
}
