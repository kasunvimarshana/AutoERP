import { AGREEMENT_API } from './agreements';
export enum RunningChartStatus { Draft = 'draft', Finalized = 'finalized', Reversed = 'reversed' }
export enum RunningChartAction { Finalize = 'finalize', Reverse = 'reverse' }
export enum AirConditioningMode { NonAc = 'non_ac', Front = 'front', Dual = 'dual' }
export const CHART_API = `${AGREEMENT_API}/running-charts`;
export const CHART_PERMISSION = { view: 'vehicle-rental.running-charts.view', manage: 'vehicle-rental.running-charts.manage', finalize: 'vehicle-rental.running-charts.finalize', reverse: 'vehicle-rental.running-charts.reverse' } as const;
export const CHART_LABELS = { [RunningChartStatus.Draft]: 'Draft', [RunningChartStatus.Finalized]: 'Finalized', [RunningChartStatus.Reversed]: 'Reversed' };
export const DISTANCE_LABELS = { start_odometer: 'Start odometer', end_odometer: 'End odometer', garage_km: 'Garage distance (km)', commercial_km: 'Commercial distance (km)' } as const;
export const COUNT_LABELS = { normal_ot_minutes: 'Normal OT (minutes)', double_ot_minutes: 'Double OT (minutes)', triple_ot_minutes: 'Triple OT (minutes)', night_outs: 'Night-outs' } as const;
export interface ChartFacts {
    reference: string; starts_at: string; ends_at: string; start_odometer: string | null; end_odometer: string | null; garage_km: string | null; commercial_km: string | null;
    normal_ot_minutes: number | null; double_ot_minutes: number | null; triple_ot_minutes: number | null; night_outs: number | null; ac_mode: AirConditioningMode | null; driver_observation: string | null; notes: string | null;
}
export interface RunningChart extends ChartFacts { id: number; row_version: number; status: RunningChartStatus; total_km: string | null; corrects_chart: { id: number; reference: string } | null }
export interface ChartHistory { version: number; action: string; reason: string | null; actor: { name: string }; recorded_at: string; facts: ChartFacts }
