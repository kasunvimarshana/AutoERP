import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import { MemoryRouter } from 'react-router-dom';
import RentalReportsPage from './RentalReportsPage';

describe('RentalReportsPage', () => {
    it('renders report categories and quick links to matrix sheet and availability', () => {
        render(
            <MemoryRouter>
                <RentalReportsPage />
            </MemoryRouter>
        );

        expect(screen.getByText('Vehicle Rental reports & registers')).toBeInTheDocument();
        expect(screen.getByText('Running Chart Landscape A4 Matrix Sheet')).toBeInTheDocument();
        expect(screen.getByText('Open Running Chart Sheet')).toBeInTheDocument();
        expect(screen.getByText('Operational & Dispatch Reports')).toBeInTheDocument();
        expect(screen.getByText('Financial & Contract Reports')).toBeInTheDocument();
        expect(screen.getByText('Vehicle Availability Query')).toBeInTheDocument();
        expect(screen.getByText('Customer Agreements & Base Rent Billing')).toBeInTheDocument();
    });
});
