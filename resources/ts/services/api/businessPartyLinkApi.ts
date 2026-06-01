import type { ApiCollectionResponse } from './apiResponse';
import { httpClient } from './httpClient';
import { mockCollectionResponse, mockResponse } from '../mock/mockResponse';
import { businessPartyLinks } from '../mock/businessPartyLinkMock';
import type { BusinessPartyLink, BusinessPartyRelationType, BusinessPartyType } from '../../shared/types/businessParty.types';

type BackendBusinessPartyLink = Record<string, unknown>;

type BusinessPartyLinkInput = {
    endDate?: string;
    notes?: string;
    relationType: BusinessPartyRelationType;
    sourcePartyId?: string;
    sourcePartyName?: string;
    sourcePartyType: BusinessPartyType;
    startDate?: string;
    targetPartyId?: string;
    targetPartyName?: string;
    targetPartyType: BusinessPartyType;
};

const CORE_API_MODE = import.meta.env.VITE_CORE_API_MODE ?? 'real';

function shouldUseMockOnly() {
    return CORE_API_MODE === 'mock';
}

async function withExplicitMock<T>(realCall: () => Promise<T>, mockCall: () => Promise<T>): Promise<T> {
    if (shouldUseMockOnly()) {
        return mockCall();
    }

    return realCall();
}

function asString(value: unknown, fallback = '') {
    return value === null || value === undefined ? fallback : String(value);
}

function asOptionalString(value: unknown): string | undefined {
    const parsed = asString(value);
    return parsed === '' ? undefined : parsed;
}

function normalizePartyType(value: unknown): BusinessPartyType {
    const parsed = asString(value, 'other').toLowerCase();
    const allowed: BusinessPartyType[] = ['company', 'customer', 'employee', 'external_party', 'other', 'partner', 'party', 'provider', 'supplier', 'user'];

    return allowed.includes(parsed as BusinessPartyType) ? (parsed as BusinessPartyType) : 'other';
}

function normalizeRelationType(value: unknown): BusinessPartyRelationType {
    const parsed = asString(value, 'acts_as').toLowerCase();
    const allowed: BusinessPartyRelationType[] = ['acts_as', 'billing_relation', 'payee_relation', 'payer_relation', 'provider_relation', 'same_party'];

    return allowed.includes(parsed as BusinessPartyRelationType) ? (parsed as BusinessPartyRelationType) : 'acts_as';
}

function normalizeBusinessPartyLink(raw: BackendBusinessPartyLink): BusinessPartyLink {
    return {
        endDate: asOptionalString(raw.end_date ?? raw.endDate),
        id: asString(raw.id),
        isActive: Boolean(raw.is_active ?? raw.isActive ?? true),
        notes: asOptionalString(raw.notes),
        relationType: normalizeRelationType(raw.relation_type ?? raw.relationType),
        sourcePartyId: asOptionalString(raw.source_party_id ?? raw.sourcePartyId),
        sourcePartyName: asOptionalString(raw.source_party_name ?? raw.sourcePartyName),
        sourcePartyType: normalizePartyType(raw.source_party_type ?? raw.sourcePartyType),
        startDate: asOptionalString(raw.start_date ?? raw.startDate),
        targetPartyId: asOptionalString(raw.target_party_id ?? raw.targetPartyId),
        targetPartyName: asOptionalString(raw.target_party_name ?? raw.targetPartyName),
        targetPartyType: normalizePartyType(raw.target_party_type ?? raw.targetPartyType),
    };
}

function toBackendPayload(input: BusinessPartyLinkInput) {
    return {
        end_date: input.endDate || null,
        notes: input.notes || null,
        relation_type: input.relationType,
        source_party_id: input.sourcePartyId ? Number(input.sourcePartyId) : null,
        source_party_name: input.sourcePartyName || null,
        source_party_type: input.sourcePartyType,
        start_date: input.startDate || null,
        target_party_id: input.targetPartyId ? Number(input.targetPartyId) : null,
        target_party_name: input.targetPartyName || null,
        target_party_type: input.targetPartyType,
    };
}

function mockListForSource(sourcePartyType: BusinessPartyType, sourcePartyId?: string) {
    return businessPartyLinks.filter((link) => {
        if (link.sourcePartyType !== sourcePartyType) {
            return false;
        }

        return sourcePartyId ? link.sourcePartyId === sourcePartyId : true;
    });
}

function mockListForTarget(targetPartyType: BusinessPartyType, targetPartyId?: string) {
    return businessPartyLinks.filter((link) => {
        if (link.targetPartyType !== targetPartyType) {
            return false;
        }

        return targetPartyId ? link.targetPartyId === targetPartyId : true;
    });
}

export const businessPartyLinkApi = {
    create: (input: BusinessPartyLinkInput) =>
        withExplicitMock(
            async () => {
                const response = await httpClient<{ data: BackendBusinessPartyLink }>('/api/core/business-party-links', {
                    body: toBackendPayload(input),
                    method: 'POST',
                });

                return { ...response, data: normalizeBusinessPartyLink(response.data) };
            },
            () => mockResponse({ ...input, id: 'party-link-mock', isActive: true } as BusinessPartyLink),
        ),
    deactivate: (linkId: string, endDate?: string) =>
        withExplicitMock(
            () =>
                httpClient<{ data: BackendBusinessPartyLink }>(`/api/core/business-party-links/${linkId}/deactivate`, {
                    body: { end_date: endDate || null },
                    method: 'POST',
                }).then((response) => ({ ...response, data: normalizeBusinessPartyLink(response.data) })),
            () =>
                mockResponse({
                    ...(businessPartyLinks.find((link) => link.id === linkId) ?? businessPartyLinks[0]),
                    endDate,
                    id: linkId,
                    isActive: false,
                }),
        ),
    listForSource: (sourcePartyType: BusinessPartyType, sourcePartyId?: string): Promise<ApiCollectionResponse<BusinessPartyLink>> =>
        withExplicitMock(
            async () => {
                const response = await httpClient<ApiCollectionResponse<BackendBusinessPartyLink>>('/api/core/business-party-links', {
                    query: {
                        source_party_id: sourcePartyId,
                        source_party_type: sourcePartyType,
                    },
                });

                return { ...response, data: response.data.map(normalizeBusinessPartyLink) };
            },
            () => mockCollectionResponse(mockListForSource(sourcePartyType, sourcePartyId)),
        ),
    listForTarget: (targetPartyType: BusinessPartyType, targetPartyId?: string): Promise<ApiCollectionResponse<BusinessPartyLink>> =>
        withExplicitMock(
            async () => {
                const response = await httpClient<ApiCollectionResponse<BackendBusinessPartyLink>>('/api/core/business-party-links', {
                    query: {
                        target_party_id: targetPartyId,
                        target_party_type: targetPartyType,
                    },
                });

                return { ...response, data: response.data.map(normalizeBusinessPartyLink) };
            },
            () => mockCollectionResponse(mockListForTarget(targetPartyType, targetPartyId)),
        ),
};
