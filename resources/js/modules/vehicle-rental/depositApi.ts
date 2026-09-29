import { apiClient } from '@/shared/api/apiClient';
import type { ApiResource } from '@/shared/types/api';
import { PAYMENT_IDEMPOTENCY_HEADER, type Payment, type PaymentLinePayload } from '@/modules/payment/paymentApi';
import { AGREEMENT_API } from './agreements';
export const DEPOSIT_PERMISSION = { view: 'payments.view', create: 'payments.create' } as const;
export interface DepositSummary { requirement: string | null; net_receipts: string; remaining_to_receive: string | null; payments: Payment[] }
export interface DepositReceiptInput { expected_version: number; payment_date: string; exchange_rate: string; reference_number?: string; lines: PaymentLinePayload[] }
const path = (agreement: number) => `${AGREEMENT_API}/customer/agreements/${agreement}/deposits`;
export const getDepositSummary = (agreement: number, signal?: AbortSignal) => apiClient.get<ApiResource<DepositSummary>>(path(agreement), { signal }).then(r => r.data.data);
export const receiveDeposit = (agreement: number, input: DepositReceiptInput, key: string) => apiClient.post<ApiResource<Payment>>(path(agreement), input, { headers: { [PAYMENT_IDEMPOTENCY_HEADER]: key } }).then(r => r.data.data);
