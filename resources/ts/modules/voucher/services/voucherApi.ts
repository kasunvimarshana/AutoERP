import { mockCollectionResponse, mockPreviewResponse, mockResponse } from '../../../services/mock/mockResponse';
import { voucherRecords } from '../mock/voucherMock';

export const voucherApi = {
    approvals: {
        approve: (voucherId: string) => mockResponse({ action: 'approve-requested', voucherId }),
        reject: (voucherId: string) => mockResponse({ action: 'reject-requested', voucherId }),
    },
    allocations: {
        create: (voucherId: string, input: unknown) => mockResponse({ input, voucherId }),
        list: (voucherId: string) => mockCollectionResponse(voucherRecords.map((record) => ({ ...record, voucherId }))),
    },
    types: {
        list: () => mockCollectionResponse(voucherRecords),
    },
    vouchers: {
        create: (input: unknown) => mockResponse(input),
        get: (id: string) => mockResponse(voucherRecords.find((record) => record.id === id) ?? voucherRecords[0]),
        list: () => mockCollectionResponse(voucherRecords),
        post: (voucherId: string) => mockResponse({ action: 'post-requested', voucherId }),
        previewPosting: (input: unknown) => mockPreviewResponse(input, { note: 'Backend voucher posting preview placeholder' }),
        reverse: (voucherId: string) => mockResponse({ action: 'reverse-requested', voucherId }),
    },
};
