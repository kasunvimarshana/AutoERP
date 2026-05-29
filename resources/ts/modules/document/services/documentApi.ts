import { mockCollectionResponse, mockPreviewResponse, mockResponse } from '../../../services/mock/mockResponse';
import { documentRecords } from '../mock/documentMock';

export const documentApi = {
    attachments: {
        list: (documentId: string) => mockCollectionResponse(documentRecords.map((record) => ({ ...record, documentId }))),
    },
    comments: {
        create: (documentId: string, input: unknown) => mockResponse({ documentId, input }),
        list: (documentId: string) => mockCollectionResponse(documentRecords.map((record) => ({ ...record, documentId }))),
    },
    documents: {
        changeStatus: (documentId: string, action: string) => mockResponse({ action, documentId }),
        create: (input: unknown) => mockResponse(input),
        get: (id: string) => mockResponse(documentRecords.find((record) => record.id === id) ?? documentRecords[0]),
        list: () => mockCollectionResponse(documentRecords),
        preview: (input: unknown) => mockPreviewResponse(input, { note: 'Backend-rendered document preview placeholder' }),
    },
    sequences: {
        preview: (input: unknown) => mockPreviewResponse(input, { note: 'Backend document numbering preview placeholder' }),
    },
    workflows: {
        list: () => mockCollectionResponse(documentRecords),
    },
};
