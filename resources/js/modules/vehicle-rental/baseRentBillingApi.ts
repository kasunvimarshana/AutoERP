import { apiClient } from '@/shared/api/apiClient';
import type { ApiResource } from '@/shared/types/api';
import { AGREEMENT_API, AgreementKind, type Agreement } from './agreements';
import { BaseRentPolicy } from './baseRentPreviewApi';

export const BILLING_PERMISSION = { [AgreementKind.Customer]: 'vehicle-rental.customer-agreements.bill', [AgreementKind.Owner]: 'vehicle-rental.owner-agreements.bill' } as const;
export interface BaseCharge { id: number; row_version: number; voided_at: string | null; void_reason: string | null; from: string; until: string; amount: string; currency: string; invoices: { id: number; number: string; status: string }[] }
export interface BillingDocumentInput { invoice_date: string; due_date: string | null; exchange_rate: string }
export interface CreatedRentalInvoice { id: number; invoice_number: string; grand_total: string }
export interface ChargePage { data: BaseCharge[]; current_page: number; last_page: number }
const path = (kind: AgreementKind, agreement: Agreement) => `${AGREEMENT_API}/${kind}/agreements/${agreement.id}/base-charges`;
export const loadBaseCharges = (kind: AgreementKind, agreement: Agreement, page: number, signal?: AbortSignal) =>
    apiClient.get<ChargePage>(path(kind, agreement), { params: { page }, signal }).then(response => response.data);
export const billBaseRent = (kind: AgreementKind, agreement: Agreement, period: { from: string; until: string }, document: BillingDocumentInput) =>
    apiClient.post<ApiResource<CreatedRentalInvoice>>(path(kind, agreement), { ...period, ...document, expected_version: agreement.row_version, policy: BaseRentPolicy.ActualCalendarDays }).then(response => response.data.data);
export const reissueBaseRent = (kind: AgreementKind, agreement: Agreement, charge: BaseCharge, document: BillingDocumentInput) =>
    apiClient.post<ApiResource<CreatedRentalInvoice>>(`${path(kind, agreement)}/${charge.id}/reissue`, { ...document, expected_version: agreement.row_version }).then(response => response.data.data);

export const voidBaseCharge = (kind: AgreementKind, agreement: Agreement, charge: BaseCharge, reason: string) =>
    apiClient.post(`${path(kind, agreement)}/${charge.id}/void`, { expected_version: agreement.row_version, expected_charge_version: charge.row_version, reason });
