import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { useState } from 'react';
import { describe, expect, it, vi } from 'vitest';
import {
    InvoiceSettlementSelector,
    type InvoiceAllocationDraft,
} from './InvoiceSettlementSelector';

const listInvoicesMock = vi.fn();

vi.mock('@/modules/invoice/invoiceApi', () => ({
    listInvoices: (params: unknown, signal?: AbortSignal) => listInvoicesMock(params, signal),
}));

describe('InvoiceSettlementSelector', () => {
    it('shows prompt when no party is selected', () => {
        render(
            <Harness
                partyId={null}
                partyType="customer"
                direction="inbound"
                targetAmount="150.000000"
            />,
        );

        expect(screen.getByText(/Select a customer above to view and settle outstanding invoices/i)).toBeInTheDocument();
    });

    it('loads and displays open invoices and handles selection and FIFO auto-allocation', async () => {
        const user = userEvent.setup();
        listInvoicesMock.mockResolvedValueOnce({
            data: [
                {
                    id: 101,
                    invoice_number: 'INV-101',
                    invoice_date: '2026-06-01',
                    invoice_type: 'rental',
                    grand_total: '100.000000',
                    balance_due: '100.000000',
                },
                {
                    id: 102,
                    invoice_number: 'INV-102',
                    invoice_date: '2026-06-02',
                    invoice_type: 'rental',
                    grand_total: '100.000000',
                    balance_due: '80.000000',
                },
            ],
        });

        render(
            <Harness
                partyId={1}
                partyType="customer"
                direction="inbound"
                targetAmount="150.000000"
            />,
        );

        await waitFor(() => {
            expect(screen.getByText('INV-101')).toBeInTheDocument();
            expect(screen.getByText('INV-102')).toBeInTheDocument();
        });

        // Click Auto-allocate FIFO with targetAmount = 150.000000
        await user.click(screen.getByRole('button', { name: /Auto-allocate FIFO/i }));

        // INV-101 (older) gets full 100, INV-102 gets remaining 50
        expect(screen.getByTestId('allocated-101')).toHaveTextContent('100.000000');
        expect(screen.getByTestId('allocated-102')).toHaveTextContent('50.000000');
        expect(screen.getByTestId('selected-count')).toHaveTextContent('2');

        // Click Deselect all
        await user.click(screen.getByRole('button', { name: /Deselect all/i }));
        expect(screen.getByTestId('selected-count')).toHaveTextContent('0');

        // Click Select all
        await user.click(screen.getByRole('button', { name: 'Select all' }));
        expect(screen.getByTestId('selected-count')).toHaveTextContent('2');
        expect(screen.getByTestId('allocated-101')).toHaveTextContent('100.000000');
        expect(screen.getByTestId('allocated-102')).toHaveTextContent('80.000000');
    });
});

function Harness({
    partyId,
    partyType,
    direction,
    targetAmount,
}: {
    partyId: number | null;
    partyType: 'customer' | 'supplier';
    direction: string;
    targetAmount: string;
}) {
    const [allocations, setAllocations] = useState<InvoiceAllocationDraft[]>([]);

    return (
        <>
            <InvoiceSettlementSelector
                partyId={partyId}
                partyType={partyType}
                direction={direction}
                targetAmount={targetAmount}
                allocations={allocations}
                onChange={setAllocations}
            />
            <div data-testid="selected-count">{allocations.filter((a) => a.selected).length}</div>
            {allocations.map((a) => (
                <div key={a.invoiceId} data-testid={`allocated-${a.invoiceId}`}>
                    {a.allocatedAmount}
                </div>
            ))}
        </>
    );
}
