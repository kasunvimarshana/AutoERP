import { mockCollectionResponse, mockPreviewResponse, mockResponse } from '../../../services/mock/mockResponse';
import { items } from '../mock/itemMock';
import type { ItemFormInput } from '../types/item.types';

export const itemApi = {
    attributes: {
        create: (itemId: string, input: unknown) => mockResponse({ input, itemId }),
        list: (itemId: string) => mockCollectionResponse(items.map((item) => ({ ...item, itemId }))),
    },
    combos: {
        preview: (itemId: string) => mockPreviewResponse({ itemId }, { note: 'Backend combo validation and expansion preview placeholder' }),
    },
    items: {
        create: (input: ItemFormInput) => mockResponse({ id: 'mock-item', ...input }),
        get: (id: string) => mockResponse(items.find((item) => item.id === id) ?? items[0]),
        list: () => mockCollectionResponse(items),
        update: (id: string, input: ItemFormInput) => mockResponse({ id, ...input }),
    },
    pricingReferences: {
        list: (itemId: string) => mockCollectionResponse(items.map((item) => ({ ...item, itemId }))),
    },
    units: {
        preview: (itemId: string) => mockPreviewResponse({ itemId }, { note: 'Backend UOM compatibility placeholder' }),
    },
    variants: {
        create: (itemId: string, input: unknown) => mockResponse({ input, itemId }),
        list: (itemId: string) => mockCollectionResponse(items.map((item) => ({ ...item, itemId }))),
    },
};
