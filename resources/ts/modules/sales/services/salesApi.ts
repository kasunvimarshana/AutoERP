import { mockCollectionResponse, mockPreviewResponse, mockResponse } from '../../../services/mock/mockResponse';
import { salesRecords } from '../mock/salesMock';

export const salesApi = {
    advances: {
        list: () => mockCollectionResponse(salesRecords),
    },
    deliveries: {
        createFromOrder: (salesOrderId: string, input: unknown) => mockResponse({ input, salesOrderId }),
        list: () => mockCollectionResponse(salesRecords),
        previewStockIssue: (input: unknown) => mockPreviewResponse(input, { note: 'Backend stock issue preview placeholder' }),
    },
    invoices: {
        cancel: (invoiceId: string) => mockResponse({ invoiceId, action: 'cancel-requested' }),
        createFromDelivery: (deliveryId: string, input: unknown) => mockResponse({ deliveryId, input }),
        createFromOrder: (salesOrderId: string, input: unknown) => mockResponse({ input, salesOrderId }),
        list: () => mockCollectionResponse(salesRecords),
        post: (invoiceId: string) => mockResponse({ invoiceId, action: 'post-requested' }),
        preview: (input: unknown) => mockPreviewResponse(input, { note: 'Backend sales invoice calculation placeholder' }),
        reverse: (invoiceId: string) => mockResponse({ invoiceId, action: 'reverse-requested' }),
    },
    orders: {
        create: (input: unknown) => mockResponse(input),
        createWithLines: (input: unknown) => mockResponse(input),
        get: (id: string) => mockResponse(salesRecords.find((record) => record.id === id) ?? salesRecords[0]),
        list: () => mockCollectionResponse(salesRecords),
        update: (id: string, input: unknown) => mockResponse({ id, input }),
    },
    payments: {
        allocate: (paymentId: string, input: unknown) => mockResponse({ input, paymentId }),
        create: (input: unknown) => mockResponse(input),
        list: () => mockCollectionResponse(salesRecords),
        previewAllocation: (input: unknown) => mockPreviewResponse(input, { note: 'Backend customer payment allocation placeholder' }),
    },
    refunds: {
        list: () => mockCollectionResponse(salesRecords),
    },
    returns: {
        list: () => mockCollectionResponse(salesRecords),
    },
};
