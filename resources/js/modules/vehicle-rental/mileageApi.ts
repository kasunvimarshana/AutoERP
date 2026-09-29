import { apiClient } from '@/shared/api/apiClient';
import type { AgreementKind } from './agreements';
import { CHART_API, type RunningChart } from './runningCharts';
import type { BillingDocumentInput, CreatedRentalInvoice } from './baseRentBillingApi';
export enum MileagePolicy { CommercialCalendarCycles = 'commercial_calendar_cycles_v1' }
export interface MileageQuote { policy: MileagePolicy; timezone: string; cycle_from: string; cycle_until: string; allowance: string; distance: string; included_applied: string; excess_km: string; rate: string; amount: string; currency: string; pool_head: number; agreement: { reference: string; version: number } }
export interface MileageAssessment { assessment: { id: number; row_version: number }; invoice: CreatedRentalInvoice | null }
const path = (kind: AgreementKind, chart: RunningChart) => `${CHART_API}/${chart.id}/${kind}/charges/mileage`;
export const quoteMileage = (kind: AgreementKind, chart: RunningChart, signal?: AbortSignal) => apiClient.get<MileageQuote>(path(kind, chart), { signal }).then(r => r.data);
export const assessMileage = (kind: AgreementKind, chart: RunningChart, quote: MileageQuote, document: BillingDocumentInput) => apiClient.post<MileageAssessment>(path(kind, chart), {
    ...document, policy: MileagePolicy.CommercialCalendarCycles, expected_version: quote.agreement.version, expected_chart_version: chart.row_version, expected_pool_head: quote.pool_head, expected_timezone: quote.timezone,
}).then(r => r.data);
