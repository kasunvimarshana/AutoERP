import { mockCollectionResponse, mockPreviewResponse, mockResponse } from '../../../services/mock/mockResponse';
import { paymentRecords } from '../mock/paymentMock';

export const paymentApi = {
    advances: {
        allocate: (advanceId: string, input: unknown) => mockResponse({ advanceId, input }),
        list: () => mockCollectionResponse(paymentRecords),
        previewAllocation: (input: unknown) => mockPreviewResponse(input, { note: 'Backend advance allocation placeholder' }),
    },
    allocations: {
        create: (input: unknown) => mockResponse(input),
        preview: (input: unknown) => mockPreviewResponse(input, { note: 'Backend payment allocation preview placeholder' }),
    },
    methods: {
        list: () => mockCollectionResponse(paymentRecords),
    },
    payments: {
        create: (input: unknown) => mockResponse(input),
        get: (id: string) => mockResponse(paymentRecords.find((record) => record.id === id) ?? paymentRecords[0]),
        list: () => mockCollectionResponse(paymentRecords),
        post: (paymentId: string) => mockResponse({ action: 'post-requested', paymentId }),
        reverse: (paymentId: string) => mockResponse({ action: 'reverse-requested', paymentId }),
    },
    refunds: {
        create: (input: unknown) => mockResponse(input),
        list: () => mockCollectionResponse(paymentRecords),
        preview: (input: unknown) => mockPreviewResponse(input, { note: 'Backend refund preview placeholder' }),
    },
    writeOffs: {
        create: (input: unknown) => mockResponse(input),
        preview: (input: unknown) => mockPreviewResponse(input, { note: 'Backend write-off preview placeholder' }),
    },
};
