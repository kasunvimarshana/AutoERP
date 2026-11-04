import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import { MemoryRouter } from 'react-router-dom';
import RentalReportsPage from './RentalReportsPage';

describe('RentalReportsPage', () => {
    it('renders report categories and all specialized report links', () => {
        render(
            <MemoryRouter>
                <RentalReportsPage />
            </MemoryRouter>
        );

        expect(screen.getByText('Vehicle Rental Reports & Business Intelligence')).toBeInTheDocument();
        expect(screen.getByText('Running Chart Landscape A4 Matrix Sheet')).toBeInTheDocument();
        expect(screen.getByText('Open Running Chart Sheet')).toBeInTheDocument();

        // 1. Fleet Operations & Compliance Reports
        expect(screen.getByText('Fleet Operations & Compliance Reports')).toBeInTheDocument();
        expect(screen.getByText('Fleet Utilization & Status Report')).toBeInTheDocument();
        expect(screen.getByText('Vehicle Revenue Licence Expire Report')).toBeInTheDocument();
        expect(screen.getByText('Vehicle Availability Query')).toBeInTheDocument();

        // 2. Commercial Revenue & Receivables
        expect(screen.getByText('Commercial Revenue & Receivables')).toBeInTheDocument();
        expect(screen.getByText('Rental Revenue & Commercial Billing Summary')).toBeInTheDocument();
        expect(screen.getByText("Lessee's Outstanding Age Analysis")).toBeInTheDocument();
        expect(screen.getByText('Customer Agreements & Base Rent Billing')).toBeInTheDocument();

        // 3. Supplier Payables & Financial Performance
        expect(screen.getByText('Supplier Payables & Financial Performance')).toBeInTheDocument();
        expect(screen.getByText('Vehicle Owner Disbursement & Payables Ledger')).toBeInTheDocument();
        expect(screen.getByText('Fleet Vehicle Profitability & Unit Economics')).toBeInTheDocument();
        expect(screen.getByText('Rental Incident & Damage Risk Report')).toBeInTheDocument();
    });
});
