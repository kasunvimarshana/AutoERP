import { apiClient } from '@/shared/api/apiClient';
import type { ApiCollection, ApiResource } from '@/shared/types/api';
import { AGREEMENT_API, PAGE_SIZE, AgreementKind, AgreementAction, type Agreement, type AgreementPayload } from './agreements';

const url = (kind: AgreementKind) => `${AGREEMENT_API}/${kind}/agreements`;
export const listAgreements = (kind: AgreementKind, page: number, signal?: AbortSignal) =>
    apiClient.get<ApiCollection<Agreement>>(url(kind), { params: { page, per_page: PAGE_SIZE }, signal }).then(r => r.data);
export const saveAgreement = (kind: AgreementKind, payload: AgreementPayload, id?: number) =>
    (id ? apiClient.put<ApiResource<Agreement>>(`${url(kind)}/${id}`, payload) : apiClient.post<ApiResource<Agreement>>(url(kind), payload)).then(r => r.data.data);
export const transitionAgreement = (kind: AgreementKind, record: Agreement, action: AgreementAction, reason?: string) =>
    apiClient.post<ApiResource<Agreement>>(`${url(kind)}/${record.id}/${action}`, { expected_version: record.row_version, reason }).then(r => r.data.data);
