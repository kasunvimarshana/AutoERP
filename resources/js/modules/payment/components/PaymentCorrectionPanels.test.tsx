import { MemoryRouter } from 'react-router-dom';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import type { Payment, PaymentAllocation } from '../paymentApi';
import { PaymentAllocationReversalPanel } from './PaymentAllocationReversalPanel';
import { PaymentRefundPanel } from './PaymentRefundPanel';

const api = vi.hoisted(() => ({
    listUsableRefundPaymentMethods: vi.fn(),
    refundPayment: vi.fn(),
    reversePaymentAllocation: vi.fn(),
}));

vi.mock('../paymentApi', async (importOriginal) => {
    const actual = await importOriginal<typeof import('../paymentApi')>();
    return { ...actual, ...api };
});

function payment(): Payment {
    return {
        id: 41,
        row_version: 7,
        payment_number: 'PAY-0041',
        payment_date: '2026-10-01',
        payment_type: 'customer_receipt',
        direction: 'inbound',
        document_status: 'approved',
        posting_status: 'posted',
        allocation_status: 'partially_allocated',
        instrument_status: 'cleared',
        currency: { id: 2, code: 'USD' },
        exchange_rate: '2.000000',
        total_amount: '1000.000000',
        allocated_amount: '300.000000',
        unapplied_amount: '700.000000',
        refunded_amount: '0.000000',
    };
}

describe('Payment correction panels', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        api.listUsableRefundPaymentMethods.mockResolvedValue({
            data: [{
                id: 9,
                name: 'Cash',
                method_type: 'cash',
                direction_allowed: 'both',
                requires_reference: false,
                requires_instrument_details: false,
                is_active: true,
            }],
        });
        api.refundPayment.mockResolvedValue({ refund_payment: { id: 55 } });
        api.reversePaymentAllocation.mockResolvedValue(payment());
    });

    it('creates a governed refund draft with explicit method and exchange rate', async () => {
        const onChanged = vi.fn().mockResolvedValue(undefined);
        render(
            <MemoryRouter>
                <PaymentRefundPanel payment={payment()} enabled onChanged={onChanged} />
            </MemoryRouter>,
        );

        const method = await screen.findByLabelText('Refund method');
        await userEvent.selectOptions(method, '9');
        await userEvent.clear(screen.getByLabelText('Refund date'));
        await userEvent.type(screen.getByLabelText('Refund date'), '2026-10-03');
        await userEvent.clear(screen.getByLabelText('Amount'));
        await userEvent.type(screen.getByLabelText('Amount'), '125');
        await userEvent.type(screen.getByLabelText('Exchange rate to base currency'), '3');
        await userEvent.type(screen.getByLabelText('Reason *'), 'Return surplus');

        await userEvent.click(screen.getByRole('button', { name: 'Create refund draft' }));

        await waitFor(() => expect(api.refundPayment).toHaveBeenCalledWith(41, expect.objectContaining({
            expected_version: 7,
            refund_date: '2026-10-03',
            amount: '125',
            payment_method_id: 9,
            exchange_rate: '3',
            reason: 'Return surplus',
        })));
        expect(onChanged).toHaveBeenCalledTimes(1);
        expect(await screen.findByRole('link', { name: 'Review refund payment' })).toHaveAttribute('href', '/payments/55');
    });

    it('reverses one active allocation using both optimistic versions', async () => {
        const onChanged = vi.fn().mockResolvedValue(undefined);
        const allocations: PaymentAllocation[] = [{
            id: 81,
            row_version: 4,
            invoice_id: 91,
            invoice: { id: 91, invoice_number: 'INV-0091' },
            allocated_amount: '300.000000',
            allocation_date: '2026-10-02T00:00:00.000000Z',
            allocation_method: 'specific_invoice',
            status: 'active',
        }];

        render(
            <PaymentAllocationReversalPanel
                payment={payment()}
                allocations={allocations}
                enabled
                onChanged={onChanged}
            />,
        );

        await userEvent.selectOptions(screen.getByLabelText('Allocation'), '81');
        expect(screen.getByLabelText('Reversal date')).toHaveAttribute('min', '2026-10-02');
        await userEvent.clear(screen.getByLabelText('Reversal date'));
        await userEvent.type(screen.getByLabelText('Reversal date'), '2026-10-04');
        await userEvent.type(screen.getByLabelText('Reason *'), 'Wrong invoice');
        await userEvent.click(screen.getByRole('button', { name: 'Reverse allocation' }));

        await waitFor(() => expect(api.reversePaymentAllocation).toHaveBeenCalledWith(41, 81, {
            expected_payment_version: 7,
            expected_allocation_version: 4,
            reversal_date: '2026-10-04',
            reason: 'Wrong invoice',
        }));
        expect(onChanged).toHaveBeenCalledTimes(1);
    });
});
