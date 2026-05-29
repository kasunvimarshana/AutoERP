import { mockCollectionResponse, mockPreviewResponse, mockResponse } from '../../../services/mock/mockResponse';
import { financeRecords } from '../mock/financeMock';

export const financeApi = {
    accounts: {
        create: (input: unknown) => mockResponse(input),
        list: () => mockCollectionResponse(financeRecords),
    },
    bankAccounts: {
        list: () => mockCollectionResponse(financeRecords),
    },
    bankReconciliations: {
        preview: (input: unknown) => mockPreviewResponse(input, { note: 'Backend reconciliation preview placeholder' }),
    },
    journalEntries: {
        create: (input: unknown) => mockResponse(input),
        list: () => mockCollectionResponse(financeRecords),
        post: (journalId: string) => mockResponse({ action: 'post-requested', journalId }),
        preview: (input: unknown) => mockPreviewResponse(input, { note: 'Backend journal posting preview placeholder' }),
        reverse: (journalId: string) => mockResponse({ action: 'reverse-requested', journalId }),
    },
    tax: {
        preview: (input: unknown) => mockPreviewResponse(input, { note: 'Backend tax calculation preview placeholder' }),
    },
};
