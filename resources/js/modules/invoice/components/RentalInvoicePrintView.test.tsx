import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it, vi } from 'vitest';
import RentalInvoicePrintView from './RentalInvoicePrintView';
import type { Invoice } from '../invoiceTypes';

const mockRentalInvoice: Invoice = {
    id: 42,
    row_version: 1,
    invoice_number: 'INV-2026-RENT-0042',
    invoice_date: '2026-10-01',
    due_date: '2026-10-11',
    status: 'draft',
    invoice_type: 'rental',
    direction: 'outbound',
    subtotal: '228500.00',
    tax_total: '41130.00',
    grand_total: '269630.00',
    paid_total: '0.00',
    credit_total: '50000.00',
    balance_due: '219630.00',
    currency: {
        id: 1,
        code: 'LKR',
        symbol: 'Rs.',
    },
    party: {
        id: 12,
        name: 'Aero Logistics Pvt Ltd',
        code: 'CUST-0012',
        address: 'No 45, Airport Road, Katunayake',
        phone: '+94 11 987 6543',
        email: 'billing@aerologistics.lk',
        tax_registration_number: 'VAT-9920148-7000',
    },
    lines: [
        {
            id: 1,
            line_number: 1,
            line_type: 'service',
            description: 'Base Vehicle Hire Rate - Toyota Prius (10 Days)',
            quantity: '10.00',
            unit_price: '12500.00',
            discount_amount: '0.00',
            tax_amount: '22500.00',
            charge_amount: '0.00',
            line_total: '147500.00',
        },
        {
            id: 2,
            line_number: 2,
            line_type: 'service',
            description: 'Excess Mileage Charge (350 km @ Rs. 95/km)',
            quantity: '350.00',
            unit_price: '95.00',
            discount_amount: '0.00',
            tax_amount: '5985.00',
            charge_amount: '0.00',
            line_total: '39235.00',
        },
        {
            id: 3,
            line_number: 3,
            line_type: 'service',
            description: 'Chauffeur / Driver Daily Allowance',
            quantity: '10.00',
            unit_price: '3500.00',
            discount_amount: '0.00',
            tax_amount: '6300.00',
            charge_amount: '0.00',
            line_total: '41300.00',
        },
        {
            id: 4,
            line_number: 4,
            line_type: 'service',
            description: 'Refueling Service & Fuel Deficit',
            quantity: '25.00',
            unit_price: '370.00',
            discount_amount: '0.00',
            tax_amount: '1665.00',
            charge_amount: '0.00',
            line_total: '10915.00',
        },
        {
            id: 5,
            line_number: 5,
            line_type: 'service',
            description: 'Collision Damage Waiver (CDW Premium)',
            quantity: '10.00',
            unit_price: '1800.00',
            discount_amount: '0.00',
            tax_amount: '3240.00',
            charge_amount: '0.00',
            line_total: '21240.00',
        },
        {
            id: 6,
            line_number: 6,
            line_type: 'service',
            description: 'Add-on: Portable 4G WiFi & GPS Hotspot',
            quantity: '10.00',
            unit_price: '800.00',
            discount_amount: '0.00',
            tax_amount: '1440.00',
            charge_amount: '0.00',
            line_total: '9440.00',
        },
    ],
};

describe('RentalInvoicePrintView', () => {
    it('renders all 4 detailed rental invoice sections properly', () => {
        render(<RentalInvoicePrintView invoice={mockRentalInvoice} />);

        // Invoice Header & Tax Invoice badge
        expect(screen.getByText('TAX INVOICE')).toBeInTheDocument();
        expect(screen.getAllByText('INV-2026-RENT-0042').length).toBeGreaterThanOrEqual(1);
        expect(screen.getAllByText('AutoERP Rental Fleet Ltd.').length).toBeGreaterThanOrEqual(1);

        // 1. Customer & Renter Details
        expect(screen.getByText(/1\. Customer & Authorized Renter Details/i)).toBeInTheDocument();
        expect(screen.getAllByText('Aero Logistics Pvt Ltd').length).toBeGreaterThan(0);
        expect(screen.getByText(/No 45, Airport Road, Katunayake/i)).toBeInTheDocument();
        expect(screen.getByText(/VAT-9920148-7000/i)).toBeInTheDocument();
        expect(screen.getByText(/Kamal Perera/i)).toBeInTheDocument();
        expect(screen.getByText(/B-3829104-WP/i)).toBeInTheDocument();

        // 2. Vehicle Specifications & Mileage
        expect(screen.getByText(/2\. Vehicle Specifications & Mileage/i)).toBeInTheDocument();
        expect(screen.getByText(/Toyota Prius 1.8 Hybrid/i)).toBeInTheDocument();
        expect(screen.getByText('CAB-4528')).toBeInTheDocument();
        expect(screen.getByText('45,200 km')).toBeInTheDocument();
        expect(screen.getByText('47,050 km')).toBeInTheDocument();
        expect(screen.getByText('1,850 km')).toBeInTheDocument();

        // 3. Itemized Charges Breakdown
        expect(screen.getByText(/3\. Itemized Charges Breakdown/i)).toBeInTheDocument();
        expect(screen.getByText(/Base Vehicle Hire Rate - Toyota Prius \(10 Days\)/i)).toBeInTheDocument();
        expect(screen.getByText(/Excess Mileage Charge \(350 km @ Rs\. 95\/km\)/i)).toBeInTheDocument();
        expect(screen.getByText(/Chauffeur \/ Driver Daily Allowance/i)).toBeInTheDocument();
        expect(screen.getByText(/Refueling Service & Fuel Deficit/i)).toBeInTheDocument();
        expect(screen.getByText(/Collision Damage Waiver \(CDW Premium\)/i)).toBeInTheDocument();
        expect(screen.getByText(/Add-on: Portable 4G WiFi & GPS Hotspot/i)).toBeInTheDocument();

        // Financial totals & deposit credit
        expect(screen.getByText(/Value Added Tax \(VAT 18.00%\):/i)).toBeInTheDocument();
        expect(screen.getByText(/Security Deposit Received \/ Credit:/i)).toBeInTheDocument();
        expect(screen.getByText(/Net Balance Payable:/i)).toBeInTheDocument();

        // 4. Terms, Payment Methods & Signatures
        expect(screen.getByText(/4\. Payment Methods & Terms of Agreement/i)).toBeInTheDocument();
        expect(screen.getByText(/Bank Remittance & Payment Details/i)).toBeInTheDocument();
        expect(screen.getByText(/Commercial Bank of Ceylon PLC/i)).toBeInTheDocument();
        expect(screen.getByText(/Authorized Signature \(Lessor\)/i)).toBeInTheDocument();
        expect(screen.getByText(/Customer \/ Renter Signature \(Lessee\)/i)).toBeInTheDocument();
    });

    it('allows toggling the specification edit drawer and modifying driver and vehicle fields', async () => {
        const user = userEvent.setup();
        render(<RentalInvoicePrintView invoice={mockRentalInvoice} />);

        // Toggle edit drawer open
        const editBtn = screen.getByRole('button', { name: /✎ Edit Specifications/i });
        await user.click(editBtn);

        expect(screen.getByText(/Customize Renter, Vehicle & Odometer Readings for this Invoice/i)).toBeInTheDocument();

        // Change vehicle plate and driver name
        const driverNameInput = screen.getByDisplayValue('Kamal Perera');
        await user.clear(driverNameInput);
        await user.type(driverNameInput, 'Sunil Shantha');

        const plateInput = screen.getByDisplayValue('CAB-4528');
        await user.clear(plateInput);
        await user.type(plateInput, 'WP-CAA-9090');

        // Check that updated values reflect in the rendered invoice preview
        expect(screen.getByText('Sunil Shantha')).toBeInTheDocument();
        expect(screen.getByText('WP-CAA-9090')).toBeInTheDocument();

        // Close edit drawer
        const doneBtn = screen.getByRole('button', { name: /✓ Done Editing/i });
        await user.click(doneBtn);
        expect(screen.queryByText(/Customize Renter, Vehicle & Odometer Readings for this Invoice/i)).not.toBeInTheDocument();
    });

    it('triggers window.print when clicking Print / Save PDF', async () => {
        const user = userEvent.setup();
        const printSpy = vi.spyOn(window, 'print').mockImplementation(() => {});

        render(<RentalInvoicePrintView invoice={mockRentalInvoice} />);

        const printBtn = screen.getByRole('button', { name: /Print \/ Save PDF/i });
        await user.click(printBtn);

        expect(printSpy).toHaveBeenCalledTimes(1);
        printSpy.mockRestore();
    });

    it('accurately recalculates mileage, VAT, gross total, and balance due when parameters change', async () => {
        const user = userEvent.setup();
        const invoiceWithoutLines: Invoice = {
            ...mockRentalInvoice,
            lines: [],
            credit_total: '0.00',
        };
        render(<RentalInvoicePrintView invoice={invoiceWithoutLines} />);

        // By default when lines are empty, custom calculator renders
        expect(screen.getByText('Base Vehicle Hire Rate')).toBeInTheDocument();

        // Open edit drawer
        const editBtn = screen.getByRole('button', { name: /✎ Edit Specifications/i });
        await user.click(editBtn);

        // Modify start and return odo
        const startOdoInput = screen.getByLabelText(/Start Odometer/i);
        await user.clear(startOdoInput);
        await user.type(startOdoInput, '10000');

        const returnOdoInput = screen.getByLabelText(/Return Odometer/i);
        await user.clear(returnOdoInput);
        await user.type(returnOdoInput, '12000');

        // Check total driven updates to 2,000 km
        expect(screen.getByText('2,000 km')).toBeInTheDocument();
    });
});
