import { apiClient } from '@/shared/api/apiClient';
import type { ApiResource } from '@/shared/types/api';
import type { AgreementKind } from './agreements';
import { CHART_API, type RunningChart } from './runningCharts';
import type { BillingDocumentInput, CreatedRentalInvoice } from './baseRentBillingApi';

export enum UsageChargeComponent { NormalOvertime = 'normal_ot', DoubleOvertime = 'double_ot', TripleOvertime = 'triple_ot', NightOut = 'night_out' }
export enum UsageChargePolicy { RecordedMinutesAndNights = 'recorded_minutes_and_nights_v1' }
export const USAGE_COMPONENT_LABELS = { [UsageChargeComponent.NormalOvertime]: 'Normal OT', [UsageChargeComponent.DoubleOvertime]: 'Double OT', [UsageChargeComponent.TripleOvertime]: 'Triple OT', [UsageChargeComponent.NightOut]: 'Night-outs' } as const;
export const MILEAGE_COMPONENT = 'excess_distance';
export interface UsageCharge { id: number; row_version: number; component: UsageChargeComponent | typeof MILEAGE_COMPONENT; amount: string; voided_at: string | null; void_reason: string | null; calculation: { description: string; currency: string }; invoices: { id: number; number: string; status: string }[] }
export interface UsageComponentQuote { component: UsageChargeComponent; label: string; quantity: number | null; rate: string | null; denominator: number; amount: string | null; error: string | null }
export interface UsageChargePage { currency: string; components: UsageComponentQuote[]; agreement: { reference: string; version: number }; charges: { data: UsageCharge[]; current_page: number; last_page: number } }
const path = (kind: AgreementKind, chart: RunningChart) => `${CHART_API}/${chart.id}/${kind}/charges`;
export const loadUsageCharges = (kind: AgreementKind, chart: RunningChart, page: number, signal?: AbortSignal) => apiClient.get<UsageChargePage>(path(kind, chart), { params: { page }, signal }).then(r => r.data);
export const billUsage = (kind: AgreementKind, chart: RunningChart, agreementVersion: number, component: UsageChargeComponent, document: BillingDocumentInput) =>
    apiClient.post<ApiResource<CreatedRentalInvoice>>(path(kind, chart), { ...document, expected_version: agreementVersion, expected_chart_version: chart.row_version, component, policy: UsageChargePolicy.RecordedMinutesAndNights }).then(r => r.data.data);
export const reissueUsage = (kind: AgreementKind, chart: RunningChart, agreementVersion: number, charge: UsageCharge, document: BillingDocumentInput) =>
    apiClient.post<ApiResource<CreatedRentalInvoice>>(`${path(kind, chart)}/${charge.id}/reissue`, { ...document, expected_version: agreementVersion, expected_chart_version: chart.row_version }).then(r => r.data.data);
export const voidUsage = (kind: AgreementKind, chart: RunningChart, agreementVersion: number, charge: UsageCharge, reason: string) =>
    apiClient.post(`${path(kind, chart)}/${charge.id}/void`, { reason, expected_version: agreementVersion, expected_chart_version: chart.row_version, expected_charge_version: charge.row_version });
