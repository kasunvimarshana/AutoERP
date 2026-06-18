import { cleanup, render, screen } from '@testing-library/react';
import { MemoryRouter, Route, Routes } from 'react-router-dom';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import type { ApiCollection } from '@/shared/types/api';
import type { Payment } from '@/modules/payment/paymentApi';
import GoodsReceiptDetailPage from './pages/GoodsReceiptDetailPage';
import PurchaseDebitNoteListPage from './pages/PurchaseDebitNoteListPage';
import PurchasePaymentWorkspacePage from './pages/PurchasePaymentWorkspacePage';

const purchaseApiMocks = vi.hoisted(() => ({
    getGoodsReceipt: vi.fn(),
    listPurchaseDebitNotes: vi.fn(),
    postGoodsReceipt: vi.fn(),
    reverseGoodsReceipt: vi.fn(),
}));

const paymentApiMocks = vi.hoisted(() => ({
    listPayments: vi.fn(),
}));

vi.mock('./purchaseApi', () => purchaseApiMocks);
vi.mock('@/modules/payment/paymentApi', () => paymentApiMocks);
vi.mock('@/modules/auth/AuthProvider', () => ({
    useAuth: () => ({
        permissions: [
            'purchase.supplier_invoices.create',
            'purchase.returns.create',
            'purchase.debit_notes.create',
        ],
    }),
}));

describe('Purchase navigation flows', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        purchaseApiMocks.getGoodsReceipt.mockResolvedValue(goodsReceipt());
        purchaseApiMocks.listPurchaseDebitNotes.mockResolvedValue(collection([]));
        paymentApiMocks.listPayments.mockResolvedValue(collection<Payment>([payment()]));
    });

    it('passes the GRN id when creating invoices and returns from a goods receipt', async () => {
        render(
            <MemoryRouter initialEntries={['/purchase/goods-receipts/77']}>
                <Routes>
                    <Route path="/purchase/goods-receipts/:id" element={<GoodsReceiptDetailPage />} />
                </Routes>
            </MemoryRouter>,
        );

        expect(await screen.findByRole('heading', { name: 'GRN-77' })).toBeInTheDocument();
        expect(screen.getByRole('link', { name: 'Create invoice' })).toHaveAttribute('href', '/purchase/invoices/create?goods_receipt_id=77');
        expect(screen.getByRole('link', { name: 'Create return' })).toHaveAttribute('href', '/purchase/returns/create?goods_receipt_id=77');
    });

    it('renders supplier payments inside the Purchase workspace', async () => {
        cleanup();
        render(
            <MemoryRouter initialEntries={['/purchase/payments']}>
                <PurchasePaymentWorkspacePage />
            </MemoryRouter>,
        );

        expect(await screen.findByRole('heading', { name: 'Supplier Payments' })).toBeInTheDocument();
        expect(screen.getByRole('link', { name: 'Create Supplier Payment' })).toHaveAttribute('href', '/purchase/payments/create');
        expect(screen.queryByText('/payments?view=supplier')).not.toBeInTheDocument();
    });

    it('opens the dedicated Debit Note create workflow instead of the return form', async () => {
        cleanup();
        render(
            <MemoryRouter initialEntries={['/purchase/debit-notes']}>
                <PurchaseDebitNoteListPage />
            </MemoryRouter>,
        );

        expect(await screen.findByRole('heading', { name: 'Purchase debit notes' })).toBeInTheDocument();
        expect(screen.getByRole('link', { name: 'New debit note' })).toHaveAttribute('href', '/purchase/debit-notes/create');
    });
});

function goodsReceipt() {
    return {
        id: 77,
        grn_number: 'GRN-77',
        received_date: '2026-06-18',
        status: 'posted',
        capabilities: { can_invoice: true, can_return: true, can_post: false, can_reverse: true },
        supplier: { id: 11, name: 'Supplier A' },
        warehouse: { id: 21, name: 'Main Warehouse' },
        purchase_order: { id: 31, purchase_order_number: 'PO-31', name: 'PO-31' },
        subtotal: '100.000000',
        grand_total: '100.000000',
        posted_at: '2026-06-18T08:00:00Z',
        lines: [],
        adjustments: [],
    };
}

function payment(): Payment {
    return {
        id: 5,
        payment_number: 'PAY-5',
        payment_date: '2026-06-18',
        payment_type: 'supplier_payment',
        direction: 'outbound',
        status: 'draft',
        party: { id: 11, name: 'Supplier A' },
        total_amount: '100.000000',
        allocated_amount: '0.000000',
    };
}

function collection<T>(data: T[]): ApiCollection<T> {
    return {
        data,
        links: {},
        meta: { current_page: 1, from: data.length ? 1 : null, last_page: 1, path: '/', per_page: 25, to: data.length || null, total: data.length },
    };
}
