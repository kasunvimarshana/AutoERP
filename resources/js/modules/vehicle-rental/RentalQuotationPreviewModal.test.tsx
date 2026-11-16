import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import React from 'react';
import { RentalQuotationPreviewModal } from './RentalQuotationPreviewModal';
import * as rentalBillingApi from './rentalBillingApi';
import * as agreementApi from './agreementApi';

vi.mock('./rentalBillingApi', () => ({
    previewRentalQuotation: vi.fn(),
}));

vi.mock('./agreementApi', () => ({
    searchCustomerAgreements: vi.fn(),
}));

const mockQuotationPreview: rentalBillingApi.RentalQuotationPreview = {
    quotation_number: 'QUOT-AGR-CUST-100-C1',
    quotation_date: '2026-09-30',
    direction: 'outbound',
    customer: {
        id: 10,
        name: 'Alpha Freight Systems',
        email: 'billing@alphafreight.lk',
        phone: '0112345678',
    },
    agreement: {
        id: 100,
        reference: 'AGR-CUST-100',
        billing_basis: 'monthly',
        starts_on: '2026-09-01',
        ends_on: '2026-12-31',
    },
    billing_cycle: {
        id: 1,
        cycle_number: 1,
        period_from: '2026-09-01',
        period_until: '2026-09-30',
        status: 'provisional',
    },
    period: {
        from: '2026-09-01',
        until: '2026-09-30',
    },
    lines: [
        {
            line_number: 1,
            description: 'Base Rental Fee · WP-CAS-1234 · 30 Days (2026-09-01 To 2026-09-30)',
            quantity: '1.000000',
            unit_price: '60000.000000',
            discount_amount: '0.000000',
            line_total: '60000.000000',
        },
        {
            line_number: 2,
            description: 'Excess Mileage Charge · 120 km @ 50.00/km (Included: 1000 km)',
            quantity: '1.000000',
            unit_price: '6000.000000',
            discount_amount: '0.000000',
            line_total: '6000.000000',
        },
        {
            line_number: 3,
            description: 'Driver Overtime · Normal (1.5x) · 8.0 hrs @ 400.00/hr',
            quantity: '1.000000',
            unit_price: '3200.000000',
            discount_amount: '0.000000',
            line_total: '3200.000000',
        },
    ],
    telematics: {
        trips_count: 5,
        commercial_km: '1120.000000',
        normal_ot_hours: '8.000000',
        double_ot_hours: '0.000000',
        triple_ot_hours: '0.000000',
        night_outs: 2,
        assigned_vehicles: ['WP-CAS-1234'],
        assigned_drivers: ['Sunil Perera'],
    },
    vehicle_attributions: [],
    driver_attributions: [],
    line_items: [],
    financial_breakdown: {
        base_rent: '60000.000000',
        excess_km: '6000.000000',
        overtime: '3200.000000',
        night_out: '3000.000000',
        tolls: '0.000000',
        additions: '0.000000',
        deductions: '0.000000',
        gross_subtotal: '72200.000000',
        discount_total: '0.000000',
        net_subtotal: '72200.000000',
        tax_amount: '0.000000',
        grand_total: '72200.000000',
        net_payable: '72200.000000',
    },
};

describe('RentalQuotationPreviewModal', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('renders form and calculates preview when cycle id is supplied', async () => {
        vi.mocked(rentalBillingApi.previewRentalQuotation).mockResolvedValue(mockQuotationPreview);

        render(
            <RentalQuotationPreviewModal
                open={true}
                onClose={vi.fn()}
                initialCycleId={1}
            />
        );

        const submitBtn = screen.getByRole('button', { name: 'Generate Quotation Preview' });
        fireEvent.click(submitBtn);

        await waitFor(() => {
            expect(rentalBillingApi.previewRentalQuotation).toHaveBeenCalledWith(
                expect.objectContaining({ billing_cycle_id: 1 })
            );
            expect(screen.getByText('QUOT-AGR-CUST-100-C1')).toBeInTheDocument();
            expect(screen.getByText('Alpha Freight Systems')).toBeInTheDocument();
            expect(screen.getByText('1120.000000 km')).toBeInTheDocument();
            expect(screen.getByText(/Base Rental Fee · WP-CAS-1234/i)).toBeInTheDocument();
            expect(screen.getByText(/Excess Mileage Charge · 120 km/i)).toBeInTheDocument();
        });
    });

    it('triggers onProceedToCreateInvoice callback when proceed button is clicked', async () => {
        vi.mocked(rentalBillingApi.previewRentalQuotation).mockResolvedValue(mockQuotationPreview);
        const onProceed = vi.fn();
        const onClose = vi.fn();

        render(
            <RentalQuotationPreviewModal
                open={true}
                onClose={onClose}
                initialAgreementId={100}
                onProceedToCreateInvoice={onProceed}
            />
        );

        const submitBtn = screen.getByRole('button', { name: 'Generate Quotation Preview' });
        fireEvent.click(submitBtn);

        await waitFor(() => {
            expect(screen.getByRole('button', { name: 'Proceed to Issue Invoice' })).toBeInTheDocument();
        });

        fireEvent.click(screen.getByRole('button', { name: 'Proceed to Issue Invoice' }));

        expect(onProceed).toHaveBeenCalledWith(100);
        expect(onClose).toHaveBeenCalled();
    });
});
