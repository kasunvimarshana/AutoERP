import { apiClient } from '@/shared/api/apiClient';
import type { ItemLookupResource } from '@/shared/api/lookupApi';
import type { ApiCollection } from '@/shared/types/api';
import type { ApiResource } from '@/shared/types/api';
import type { LookupLoadParams, LookupResult } from '@/shared/types/lookup';

const endpoint = '/api/v1/promotions';

export async function searchPromotionJobItems(params: LookupLoadParams): Promise<LookupResult<ItemLookupResource>> {
    const response = await apiClient.get<ApiCollection<ItemLookupResource>>(`${endpoint}/job-items/lookup`, {
        params: { search: params.search, page: params.page, per_page: params.perPage },
        signal: params.signal,
    });

    return response.data;
}

export interface PromotionOffer {
    id: number;
    code: string;
    name: string;
    qualifying_item_id: number;
    qualifying_item_name: string;
    qualifying_item_code: string;
    reward_item_id: number;
    reward_item_name: string;
    paid_quantity: number;
    reward_quantity: number;
    count_scope: 'customer' | 'vehicle';
    starts_on: string;
    ends_on: string | null;
    status: 'active' | 'paused' | 'expired' | 'draft';
    row_version: number;
}

export interface PromotionBenefit {
    id: number;
    kind: 'direct' | 'earned';
    item_id: number | null;
    item_name: string | null;
    item_code: string | null;
    offer_id: number | null;
    offer_name?: string | null;
    discount_type: 'free' | 'percentage' | null;
    discount_rate: string | null;
    use_limit: number | null;
    valid_weekdays: string | null;
    remaining_uses?: number;
    available_today?: boolean;
}

export interface PromotionTemplate {
    id: number;
    code: string;
    name: string;
    starts_on: string;
    ends_on: string;
    benefits: PromotionBenefit[];
}

export interface PromotionCard {
    id: number;
    template_id: number;
    template_name: string;
    card_number: string;
    customer_id: number;
    customer_name?: string;
    vehicle_id: number;
    vehicle_registration_number?: string;
    expires_on: string;
    status: string;
    row_version: number;
    benefits?: PromotionBenefit[];
}

export interface PromotionProgress {
    id: number;
    card_id: number | null;
    offer_name: string;
    qualifying_item_name: string;
    qualifying_item_code: string;
    reward_item_name: string;
    reward_item_code: string;
    reward_item_id: number;
    paid_quantity: number;
    reward_quantity: number;
    qualifying_quantity: string;
    progress_quantity: string;
    remaining_to_next: string;
    available_rewards: string;
}

export interface AvailablePromotions {
    cards: PromotionCard[];
    offers: PromotionProgress[];
    unassigned_offers: Array<{ id: number; name: string; count_scope: 'customer' | 'vehicle'; paid_item_code: string; paid_item_name: string }>;
}

const get = <T>(url: string, params?: Record<string, number>) =>
    apiClient.get<ApiResource<T>>(url, { params }).then((response) => response.data.data);
const post = <T>(url: string, payload: unknown) =>
    apiClient.post<ApiResource<T>>(url, payload).then((response) => response.data.data);

export const promotionApi = {
    offers: () => get<PromotionOffer[]>(`${endpoint}/offers`),
    templates: () => get<PromotionTemplate[]>(`${endpoint}/card-templates`),
    cards: (customerId?: number) => get<PromotionCard[]>(`${endpoint}/cards`, customerId ? { customer_id: customerId } : undefined),
    available: (customerId: number, vehicleId: number) =>
        get<AvailablePromotions>(`${endpoint}/available`, { customer_id: customerId, vehicle_id: vehicleId }),
    createOffer: (payload: unknown) => post<PromotionOffer>(`${endpoint}/offers`, payload),
    assignOffer: (offerId: number, payload: unknown) => post(`${endpoint}/offers/${offerId}/assignments`, payload),
    setOfferStatus: (offerId: number, expectedVersion: number, status: 'active' | 'paused') =>
        apiClient.patch<ApiResource<PromotionOffer>>(`${endpoint}/offers/${offerId}/status`, { expected_version: expectedVersion, status })
            .then((response) => response.data.data),
    deleteOffer: (offerId: number, expectedVersion: number) =>
        apiClient.delete(`${endpoint}/offers/${offerId}`, { data: { expected_version: expectedVersion } }),
    createTemplate: (payload: unknown) => post<PromotionTemplate>(`${endpoint}/card-templates`, payload),
    issueCard: (payload: unknown) => post<PromotionCard>(`${endpoint}/cards`, payload),
    setCardStatus: (cardId: number, payload: unknown) =>
        apiClient.patch<ApiResource<PromotionCard>>(`${endpoint}/cards/${cardId}/status`, payload)
            .then((response) => response.data.data),
    transferCard: (cardId: number, payload: unknown) =>
        apiClient.patch<ApiResource<PromotionCard>>(`${endpoint}/cards/${cardId}/transfer`, payload)
            .then((response) => response.data.data),
    choose: (jobId: number, lineId: number, payload: unknown) =>
        apiClient.put<ApiResource<{ job_row_version: number }>>(`${endpoint}/jobs/${jobId}/lines/${lineId}/choice`, payload)
            .then((response) => response.data.data),
    removeChoice: (jobId: number, lineId: number, expectedVersion: number) =>
        apiClient.delete<ApiResource<{ job_row_version: number }>>(`${endpoint}/jobs/${jobId}/lines/${lineId}/choice`, {
            data: { expected_version: expectedVersion },
        }).then((response) => response.data.data),
};
