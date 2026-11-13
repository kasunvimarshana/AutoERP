import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { OwnerSettlementModal } from './OwnerSettlementModal';
import * as agreementApi from './agreementApi';

vi.mock('./agreementApi', () => ({
    generateOwnerSettlementBill: vi.fn(),
}));

describe('OwnerSettlementModal', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('renders with default 10% WHT rate and generates an inbound AP supplier bill', async () => {
        const viGenerate = vi.mocked(agreementApi.generateOwnerSettlementBill).mockResolvedValue({
            id: 88,
            invoice_number: 'BILL-2026-0088',
        });

        const onClose = vi.fn();
        const onBillGenerated = vi.fn();

        render(
            <OwnerSettlementModal
                open={true}
                onClose={onClose}
                ownerAgreementId={4}
                ownerAgreementReference="AG-OWN-004"
                defaultStartDate="2026-09-01"
                defaultEndDate="2026-09-30"
                onBillGenerated={onBillGenerated}
            />,
        );

        expect(screen.getByText(/Generate Inbound AP Supplier Bill · AG-OWN-004/i)).toBeInTheDocument();
        expect(screen.getByDisplayValue('10')).toBeInTheDocument();

        const submitBtn = screen.getByRole('button', { name: /Generate Inbound AP Bill/i });
        fireEvent.click(submitBtn);

        await waitFor(() => {
            expect(viGenerate).toHaveBeenCalledWith(
                expect.objectContaining({
                    owner_agreement_id: 4,
                    start_date: '2026-09-01',
                    end_date: '2026-09-30',
                    wht_rate: 10,
                }),
            );
            expect(onBillGenerated).toHaveBeenCalledWith({
                id: 88,
                invoice_number: 'BILL-2026-0088',
            });
            expect(screen.getByText(/Supplier Purchase Invoice Created/i)).toBeInTheDocument();
            expect(screen.getByText(/BILL-2026-0088/i)).toBeInTheDocument();
        });
    });
});
