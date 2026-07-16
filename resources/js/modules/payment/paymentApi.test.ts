import { beforeEach, describe, expect, it, vi } from 'vitest';
import { endpoints } from '@/shared/api/endpoints';
import {
    createPayment,
    PAYMENT_IDEMPOTENCY_HEADER,
    type PaymentPayload,
} from './paymentApi';

const apiClientMocks = vi.hoisted(() => ({
    post: vi.fn(),
}));

vi.mock('@/shared/api/apiClient', () => ({ apiClient: apiClientMocks }));

const payload: PaymentPayload = {
    payment_type: 'customer_receipt',
    direction: 'inbound',
    payment_date: '2026-07-16',
    party_type: 'customer',
    party_id: 7,
    lines: [{
        payment_method_id: 3,
        amount: '100.000000',
    }],
};

describe('payment create API idempotency', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('reuses one idempotency key when an exact create request is retried after an uncertain failure', async () => {
        const randomUuid = vi.spyOn(globalThis.crypto, 'randomUUID')
            .mockReturnValue('00000000-0000-4000-8000-000000000001');
        apiClientMocks.post
            .mockRejectedValueOnce(new Error('Network response was lost.'))
            .mockResolvedValueOnce({ data: { data: { id: 41 } } });

        await expect(createPayment(payload)).rejects.toThrow('Network response was lost.');
        await expect(createPayment(payload)).resolves.toEqual({ id: 41 });

        expect(randomUuid).toHaveBeenCalledTimes(1);
        expect(apiClientMocks.post).toHaveBeenNthCalledWith(1, endpoints.payments, payload, {
            headers: { [PAYMENT_IDEMPOTENCY_HEADER]: '00000000-0000-4000-8000-000000000001' },
        });
        expect(apiClientMocks.post).toHaveBeenNthCalledWith(2, endpoints.payments, payload, {
            headers: { [PAYMENT_IDEMPOTENCY_HEADER]: '00000000-0000-4000-8000-000000000001' },
        });
    });

    it('releases the retained key after a successful response so a later identical payment is a new command', async () => {
        const randomUuid = vi.spyOn(globalThis.crypto, 'randomUUID')
            .mockReturnValueOnce('00000000-0000-4000-8000-000000000002')
            .mockReturnValueOnce('00000000-0000-4000-8000-000000000003');
        apiClientMocks.post
            .mockResolvedValueOnce({ data: { data: { id: 42 } } })
            .mockResolvedValueOnce({ data: { data: { id: 43 } } });

        await createPayment(payload);
        await createPayment(payload);

        expect(randomUuid).toHaveBeenCalledTimes(2);
        expect(apiClientMocks.post.mock.calls[0][2].headers[PAYMENT_IDEMPOTENCY_HEADER])
            .toBe('00000000-0000-4000-8000-000000000002');
        expect(apiClientMocks.post.mock.calls[1][2].headers[PAYMENT_IDEMPOTENCY_HEADER])
            .toBe('00000000-0000-4000-8000-000000000003');
    });
});
