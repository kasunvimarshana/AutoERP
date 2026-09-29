import { apiClient } from '@/shared/api/apiClient';
import type { ApiResource } from '@/shared/types/api';
import { AGREEMENT_API, type Agreement, type AgreementKind } from './agreements';

export enum BaseRentPolicy { ActualCalendarDays = 'actual_calendar_days_v1' }
export interface BaseRentPreview {
    agreement: { id: number; reference: string; version: number; kind: AgreementKind };
    currency: string; policy: BaseRentPolicy; from: string; until: string; base_rent: string;
    rate: string;
    segments: { from: string; until: string; cycle_from: string | null; cycle_until: string | null; days: number; denominator_days: number; amount: string }[];
}
export const previewBaseRent = (kind: AgreementKind, agreement: Agreement, from: string, until: string, signal?: AbortSignal) =>
    apiClient.post<ApiResource<BaseRentPreview>>(`${AGREEMENT_API}/${kind}/agreements/${agreement.id}/base-rent-preview`, {
        expected_version: agreement.row_version, policy: BaseRentPolicy.ActualCalendarDays, from, until,
    }, { signal }).then(response => response.data.data);
