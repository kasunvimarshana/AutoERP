import { mockCollectionResponse, mockPreviewResponse, mockResponse } from '../../../services/mock/mockResponse';
import { priceRules } from '../mock/pricingMock';
import type { PricePreviewInput } from '../types/pricing.types';

export const pricingApi = {
    discounts: {
        preview: (input: unknown) => mockPreviewResponse(input, { note: 'Backend discount calculation placeholder' }),
    },
    priceLists: {
        create: (input: unknown) => mockResponse(input),
        list: () => mockCollectionResponse(priceRules),
        update: (id: string, input: unknown) => mockResponse({ id, input }),
    },
    resolver: {
        preview: (input: PricePreviewInput) => mockPreviewResponse(input, { note: 'Backend price resolving placeholder' }),
    },
    rules: {
        create: (input: { name: string; scope: string }) => mockResponse({ id: 'mock-price-rule', ...input }),
        list: () => mockCollectionResponse(priceRules),
        update: (id: string, input: unknown) => mockResponse({ id, input }),
    },
};
