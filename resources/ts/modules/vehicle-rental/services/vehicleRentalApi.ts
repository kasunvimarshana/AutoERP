import { mockCollectionResponse, mockPreviewResponse, mockResponse } from '../../../services/mock/mockResponse';
import { rentalRecords } from '../mock/vehicleRentalMock';

export const vehicleRentalApi = {
    agreements: {
        create: (input: unknown) => mockResponse(input),
        get: (id: string) => mockResponse(rentalRecords.find((record) => record.id === id) ?? rentalRecords[0]),
        list: () => mockCollectionResponse(rentalRecords),
        preview: (input: unknown) => mockPreviewResponse(input, { note: 'Backend agreement validation preview placeholder' }),
        update: (id: string, input: unknown) => mockResponse({ id, input }),
    },
    availability: {
        preview: (input: unknown) => mockPreviewResponse(input, { note: 'Backend vehicle availability preview placeholder' }),
    },
    breakdowns: {
        list: () => mockCollectionResponse(rentalRecords),
    },
    invoices: {
        createFromRunningChart: (runningChartId: string, input: unknown) => mockResponse({ input, runningChartId }),
        list: () => mockCollectionResponse(rentalRecords),
        post: (invoiceId: string) => mockResponse({ action: 'post-requested', invoiceId }),
        preview: (input: unknown) => mockPreviewResponse(input, { note: 'Backend rental invoice calculation placeholder' }),
    },
    payments: {
        allocate: (paymentId: string, input: unknown) => mockResponse({ input, paymentId }),
        create: (input: unknown) => mockResponse(input),
        list: () => mockCollectionResponse(rentalRecords),
        previewAllocation: (input: unknown) => mockPreviewResponse(input, { note: 'Backend rental payment allocation placeholder' }),
    },
    providerPayables: {
        list: () => mockCollectionResponse(rentalRecords),
        preview: (input: unknown) => mockPreviewResponse(input, { note: 'Backend provider payable preview placeholder' }),
    },
    replacements: {
        list: () => mockCollectionResponse(rentalRecords),
    },
    runningCharts: {
        create: (input: unknown) => mockResponse(input),
        list: () => mockCollectionResponse(rentalRecords),
        previewBilling: (input: unknown) => mockPreviewResponse(input, { note: 'Backend rental billing preview placeholder' }),
    },
};
