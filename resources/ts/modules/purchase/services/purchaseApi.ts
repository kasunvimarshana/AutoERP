import { mockCollectionResponse, mockPreviewResponse, mockResponse } from '../../../services/mock/mockResponse';
import { purchaseRecords } from '../mock/purchaseMock';

export const purchaseApi = {
    advances: {
        list: () => mockCollectionResponse(purchaseRecords),
    },
    grns: {
        createDirect: (input: unknown) => mockResponse({ input, mode: 'direct-grn' }),
        createFromPo: (purchaseOrderId: string, input: unknown) => mockResponse({ input, purchaseOrderId }),
        list: () => mockCollectionResponse(purchaseRecords),
    },
    invoices: {
        cancel: (invoiceId: string) => mockResponse({ invoiceId, action: 'cancel-requested' }),
        createFromGrn: (grnId: string, input: unknown) => mockResponse({ grnId, input }),
        createFromPo: (purchaseOrderId: string, input: unknown) => mockResponse({ input, purchaseOrderId }),
        list: () => mockCollectionResponse(purchaseRecords),
        post: (invoiceId: string) => mockResponse({ invoiceId, action: 'post-requested' }),
        preview: (input: unknown) => mockPreviewResponse(input, { note: 'Backend purchase invoice calculation placeholder' }),
        reverse: (invoiceId: string) => mockResponse({ invoiceId, action: 'reverse-requested' }),
    },
    orders: {
        create: (input: unknown) => mockResponse(input),
        createWithLines: (input: unknown) => mockResponse(input),
        get: (id: string) => mockResponse(purchaseRecords.find((record) => record.id === id) ?? purchaseRecords[0]),
        list: () => mockCollectionResponse(purchaseRecords),
        update: (id: string, input: unknown) => mockResponse({ id, input }),
    },
    payments: {
        allocate: (paymentId: string, input: unknown) => mockResponse({ input, paymentId }),
        create: (input: unknown) => mockResponse(input),
        list: () => mockCollectionResponse(purchaseRecords),
        previewAllocation: (input: unknown) => mockPreviewResponse(input, { note: 'Backend supplier payment allocation placeholder' }),
    },
    refunds: {
        list: () => mockCollectionResponse(purchaseRecords),
    },
    returns: {
        list: () => mockCollectionResponse(purchaseRecords),
    },
};
