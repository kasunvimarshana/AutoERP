/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

import type { ReactNode } from 'react';
import { MemoryRouter, Route, Routes } from 'react-router-dom';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import PaymentDetailPage from './PaymentDetailPage';

const paymentApiMocks = vi.hoisted(() => ({
    allocatePayment: vi.fn(),
    approvePayment: vi.fn(),
    deletePayment: vi.fn(),
    getPayment: vi.fn(),
    getPaymentAllocations: vi.fn(),
    getPaymentUnappliedBalance: vi.fn(),
    listUsableRefundPaymentMethods: vi.fn(),
    postPayment: vi.fn(),
    refundPayment: vi.fn(),
    reversePayment: vi.fn(),
    reversePaymentAllocation: vi.fn(),
    settlePaymentLine: vi.fn(),
    submitPayment: vi.fn(),
    voidPayment: vi.fn(),
}));

vi.mock('../paymentApi', () => paymentApiMocks);
vi.mock('@/modules/auth/AuthProvider', () => ({
    useAuth: () => ({}),
}));
vi.mock('../paymentPermissions', () => ({
    hasPaymentPermission: () => true,
    paymentPermissions: {
        submit: 'payments.submit',
        approve: 'payments.approve',
        post: 'payments.post',
        void: 'payments.void',
        allocate: 'payments.allocate',
        settle: 'payments.settle',
        refund: 'payments.refund',
        reverse: 'payments.reverse',
        chequesPrint: 'cheques.print',
    },
}));
vi.mock('@/shared/components/ContentHeader', () => ({
    ContentHeader: ({ title, actions }: { title: ReactNode; actions?: ReactNode }) => (
        <header>
            <h1>{title}</h1>
            {actions}
        </header>
    ),
}));

function payment(rowVersion: number, capability: 'can_submit' | 'can_approve' | 'can_post') {
    return {
        id: 15,
        row_version: rowVersion,
        payment_number: 'PAY-0015',
        payment_date: '2026-07-21',
        payment_type: 'supplier_payment',
        direction: 'outbound',
        document_status: capability === 'can_submit' ? 'draft' : capability === 'can_approve' ? 'submitted' : 'approved',
        posting_status: 'not_posted',
        allocation_status: 'allocated',
        instrument_status: 'pending',
        total_amount: '1000.000000',
        allocated_amount: '1000.000000',
        unapplied_amount: '0.000000',
        refunded_amount: '0.000000',
        capabilities: { [capability]: true },
        lines: [],
        allocations: [],
        refunds: [],
        reversals: [],
        lifecycle_events: [],
    };
}

describe('Payment detail optimistic version flow', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        paymentApiMocks.getPayment.mockResolvedValue(payment(1, 'can_submit'));
        paymentApiMocks.getPaymentAllocations.mockResolvedValue([]);
        paymentApiMocks.getPaymentUnappliedBalance.mockResolvedValue(null);
        paymentApiMocks.listUsableRefundPaymentMethods.mockResolvedValue({ data: [] });
        paymentApiMocks.submitPayment.mockResolvedValue(payment(2, 'can_approve'));
        paymentApiMocks.approvePayment.mockResolvedValue(payment(3, 'can_post'));
    });

    it('keeps the version returned by each action for the next action', async () => {
        render(
            <MemoryRouter initialEntries={['/payments/15']}>
                <Routes>
                    <Route path="/payments/:id" element={<PaymentDetailPage />} />
                </Routes>
            </MemoryRouter>,
        );

        await userEvent.click(await screen.findByRole('button', { name: 'Submit' }));
        await waitFor(() => expect(paymentApiMocks.submitPayment).toHaveBeenCalledWith(15, 1));

        await userEvent.click(await screen.findByRole('button', { name: 'Approve' }));
        await waitFor(() => expect(paymentApiMocks.approvePayment).toHaveBeenCalledWith(15, 2));
    });

    it('confirms draft deletion using ConfirmDialog before deleting', async () => {
        paymentApiMocks.getPayment.mockResolvedValue({
            ...payment(1, 'can_submit'),
            capabilities: { can_delete_draft: true },
        });
        paymentApiMocks.deletePayment.mockResolvedValue({});

        render(
            <MemoryRouter initialEntries={['/payments/15']}>
                <Routes>
                    <Route path="/payments/:id" element={<PaymentDetailPage />} />
                </Routes>
            </MemoryRouter>,
        );

        await userEvent.click(await screen.findByRole('button', { name: 'Delete draft' }));

        expect(await screen.findByText('Delete draft payment?')).toBeInTheDocument();
        expect(screen.getByText(/Are you sure you want to delete this draft payment\?/)).toBeInTheDocument();

        // Cancel
        await userEvent.click(screen.getByRole('button', { name: 'Cancel' }));
        expect(paymentApiMocks.deletePayment).not.toHaveBeenCalled();

        // Open again and confirm inside dialog
        await userEvent.click(screen.getByRole('button', { name: 'Delete draft' }));
        const dialog = screen.getByRole('dialog');
        const confirmBtn = dialog.querySelector('button.bg-rose-600');
        expect(confirmBtn).not.toBeNull();
        await userEvent.click(confirmBtn!);
        await waitFor(() => expect(paymentApiMocks.deletePayment).toHaveBeenCalledWith(15, 1));
    });
});
