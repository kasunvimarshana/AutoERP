import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import React from 'react';
import { RentalOwnerBillPreviewModal } from './RentalOwnerBillPreviewModal';
import * as rentalBillingApi from './rentalBillingApi';
import * as agreementApi from './agreementApi';

vi.mock('./rentalBillingApi', () => ({
    previewRentalOwnerBill: vi.fn(),
}));

vi.mock('./agreementApi', () => ({
    searchOwnerAgreements: vi.fn(),
}));

const mockBillPreview: rentalBillingApi.RentalOwnerBillPreview = {
    bill_number: 'PROFORMA-SETTLE-AGR-OWN-200',
    direction: 'inbound',
    document_date: '2026-09-30',
    supplier: {
        id: 15,
        name: 'Sunil Weerasinghe',
    },
    agreement: {
        id: 200,
        reference: 'AGR-OWN-200',
        starts_on: '2026-09-01',
        ends_on: '2026-12-31',
        vehicle: {
            id: 8,
            registration_number: 'WP-CAR-7777',
            make: 'Toyota',
            model: 'HiAce',
        },
    },
    period: {
        from: '2026-09-01',
        until: '2026-09-30',
    },
    wht_rate: '10.0',
    lines: [
        {
            line_number: 1,
            description: 'Gross Vehicle Lease Rent · AGR-OWN-200 · WP-CAR-7777 (01/09/2026 To 30/09/2026)',
            quantity: '1.000000',
            unit_price: '50000.000000',
            discount_amount: '0.000000',
            line_total: '50000.000000',
        },
    ],
    adjustments: [
        {
            name: 'Statutory Withholding Tax Deduction (10%)',
            type: 'withholding',
            effect: 'decrease',
            amount: '5000.000000',
        },
        {
            name: 'Fleet Maintenance Recovery',
            type: 'discount',
            effect: 'decrease',
            amount: '3000.000000',
        },
    ],
    financial_breakdown: {
        gross_payable: '50000.000000',
        base_payable: '50000.000000',
        usage_payable: '0.000000',
        adjustment_additions: '0.000000',
        wht_amount: '5000.000000',
        maintenance_deductions: '3000.000000',
        adjustment_deductions: '0.000000',
        total_deductions: '8000.000000',
        net_payable: '42000.000000',
    },
    notes: 'Pro-forma settlement preview. Gross: 50000, WHT: 5000, Net: 42000',
};

describe('RentalOwnerBillPreviewModal', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('renders form and calculates settlement preview with 10% WHT and maintenance deductions', async () => {
        vi.mocked(rentalBillingApi.previewRentalOwnerBill).mockResolvedValue(mockBillPreview);

        render(
            <RentalOwnerBillPreviewModal
                open={true}
                onClose={vi.fn()}
                initialAgreementId={200}
            />
        );

        const submitBtn = screen.getByRole('button', { name: 'Generate Settlement Preview' });
        fireEvent.click(submitBtn);

        await waitFor(() => {
            expect(rentalBillingApi.previewRentalOwnerBill).toHaveBeenCalledWith(
                expect.objectContaining({
                    owner_agreement_id: 200,
                    wht_rate: '10.0',
                })
            );
            expect(screen.getByText('PROFORMA-SETTLE-AGR-OWN-200')).toBeInTheDocument();
            expect(screen.getByText('Sunil Weerasinghe')).toBeInTheDocument();
            expect(screen.getByText('WP-CAR-7777')).toBeInTheDocument();
            expect(screen.getByText('Statutory Withholding Tax Deduction (10%)')).toBeInTheDocument();
            expect(screen.getByText('Fleet Maintenance Recovery')).toBeInTheDocument();
        });
    });

    it('triggers onProceedToGenerateBill callback when proceed button is clicked', async () => {
        vi.mocked(rentalBillingApi.previewRentalOwnerBill).mockResolvedValue(mockBillPreview);
        const onProceed = vi.fn();
        const onClose = vi.fn();

        render(
            <RentalOwnerBillPreviewModal
                open={true}
                onClose={onClose}
                initialAgreementId={200}
                onProceedToGenerateBill={onProceed}
            />
        );

        const submitBtn = screen.getByRole('button', { name: 'Generate Settlement Preview' });
        fireEvent.click(submitBtn);

        await waitFor(() => {
            expect(screen.getByRole('button', { name: 'Proceed to Generate Bill' })).toBeInTheDocument();
        });

        fireEvent.click(screen.getByRole('button', { name: 'Proceed to Generate Bill' }));

        expect(onProceed).toHaveBeenCalledWith(200, expect.any(String), expect.any(String), '10.0');
        expect(onClose).toHaveBeenCalled();
    });
});
