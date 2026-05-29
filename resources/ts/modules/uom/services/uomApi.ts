import { mockCollectionResponse, mockPreviewResponse, mockResponse } from '../../../services/mock/mockResponse';
import { units } from '../mock/uomMock';
import type { UomConversionInput } from '../types/uom.types';

export const uomApi = {
    categories: {
        list: () => mockCollectionResponse(units),
    },
    conversions: {
        create: (input: unknown) => mockResponse(input),
        list: () => mockCollectionResponse(units),
        preview: (input: UomConversionInput) => mockPreviewResponse(input, { note: 'Backend conversion result placeholder' }),
    },
    units: {
        create: (input: { code: string; name: string }) => mockResponse({ id: 'mock-unit', ...input }),
        list: () => mockCollectionResponse(units),
        update: (id: string, input: unknown) => mockResponse({ id, input }),
    },
};
