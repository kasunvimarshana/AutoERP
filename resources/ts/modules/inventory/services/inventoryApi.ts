import { mockCollectionResponse, mockPreviewResponse, mockResponse } from '../../../services/mock/mockResponse';
import { inventoryRecords } from '../mock/inventoryMock';

export const inventoryApi = {
    adjustments: {
        create: (input: unknown) => mockResponse(input),
        preview: (input: unknown) => mockPreviewResponse(input, { note: 'Backend stock adjustment preview placeholder' }),
    },
    availability: {
        preview: (input: unknown) => mockPreviewResponse(input, { note: 'Backend stock availability preview placeholder' }),
    },
    movements: {
        list: () => mockCollectionResponse(inventoryRecords),
        reverse: (movementId: string) => mockResponse({ action: 'reverse-requested', movementId }),
    },
    reservations: {
        create: (input: unknown) => mockResponse(input),
        list: () => mockCollectionResponse(inventoryRecords),
        release: (reservationId: string) => mockResponse({ action: 'release-requested', reservationId }),
    },
    stockLevels: {
        list: () => mockCollectionResponse(inventoryRecords),
    },
    transfers: {
        create: (input: unknown) => mockResponse(input),
        list: () => mockCollectionResponse(inventoryRecords),
        preview: (input: unknown) => mockPreviewResponse(input, { note: 'Backend stock transfer preview placeholder' }),
    },
};
