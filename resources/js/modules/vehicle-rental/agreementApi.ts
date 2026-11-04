import { apiClient } from '@/shared/api/apiClient';
import { requestLookup, mapLookupResult } from '@/shared/api/lookupRequest';
import type { ApiCollection, ApiResource } from '@/shared/types/api';
import type { NamedResource } from '@/shared/types/common';
import type { LookupLoadParams, LookupResult } from '@/shared/types/lookup';
import { AGREEMENT_API, PAGE_SIZE, AgreementKind, AgreementAction, type Agreement, type AgreementHistory, type AgreementPayload, type AgreementSuccessorPayload } from './agreements';

const url = (kind: AgreementKind) => `${AGREEMENT_API}/${kind}/agreements`;
export const listAgreements = (kind: AgreementKind, page: number, signal?: AbortSignal, search?: string) =>
    apiClient.get<ApiCollection<Agreement>>(url(kind), { params: { page, per_page: PAGE_SIZE, search: search || undefined }, signal }).then(r => r.data);
export const getAgreement = (kind: AgreementKind, id: number, signal?: AbortSignal) =>
    apiClient.get<ApiResource<Agreement>>(`${url(kind)}/${id}`, { signal }).then(r => r.data.data);
export const searchCustomerAgreements = async (params: LookupLoadParams): Promise<LookupResult<NamedResource>> => {
    const result = await requestLookup<Agreement>(url(AgreementKind.Customer), params);
    return mapLookupResult(result, ag => ({
        id: ag.id,
        name: `${ag.reference} — ${ag.party.name}`,
    }));
};
export const searchOwnerAgreements = async (params: LookupLoadParams): Promise<LookupResult<NamedResource>> => {
    const result = await requestLookup<Agreement>(url(AgreementKind.Owner), params);
    return mapLookupResult(result, ag => ({
        id: ag.id,
        name: `${ag.reference} — ${ag.party.name}`,
    }));
};
export const saveAgreement = (kind: AgreementKind, payload: AgreementPayload, id?: number) =>
    (id ? apiClient.put<ApiResource<Agreement>>(`${url(kind)}/${id}`, payload) : apiClient.post<ApiResource<Agreement>>(url(kind), payload)).then(r => r.data.data);
export const createAgreementSuccessor = (kind: AgreementKind, record: Agreement, payload: AgreementSuccessorPayload) =>
    apiClient.post<ApiResource<Agreement>>(`${url(kind)}/${record.id}/successor`, { ...payload, expected_version: record.row_version }).then(r => r.data.data);
export const transitionAgreement = (kind: AgreementKind, record: Agreement, action: AgreementAction, reason?: string) =>
    apiClient.post<ApiResource<Agreement>>(`${url(kind)}/${record.id}/${action}`, { expected_version: record.row_version, reason }).then(r => r.data.data);
export const agreementHistory = (kind: AgreementKind, id: number, page: number, signal?: AbortSignal) =>
    apiClient.get<ApiCollection<AgreementHistory>>(`${url(kind)}/${id}/history`, { params: { page, per_page: PAGE_SIZE }, signal }).then(r => r.data);
