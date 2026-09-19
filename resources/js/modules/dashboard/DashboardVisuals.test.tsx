import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import { TestRouter } from '@/test/TestRouter';
import { EmployeePerformance, ProfitabilityOverview } from './DashboardVisuals';

describe('Dashboard Phase 2 visuals', () => {
    it('shows ledger profitability and human-readable employee rankings with drill-down context', () => {
        render(
            <TestRouter>
                <ProfitabilityOverview
                    currency="LKR"
                    profitability={{
                        total_income: '1000.000000',
                        cost_of_sales: '400.000000',
                        gross_profit: '600.000000',
                        other_expenses: '150.000000',
                        total_expenses: '550.000000',
                        net_profit: '450.000000',
                    }}
                />
                <EmployeePerformance
                    currency="LKR"
                    dateFrom="2026-09-01"
                    dateTo="2026-09-30"
                    rows={[{
                        employee: { id: 7, code: 'EMP-007', name: 'Nimal Perera' },
                        completed_jobs: 3,
                        total_jobs: 4,
                        total_hours: '8.000000',
                        labour_value: '700.000000',
                        earned_commission: '70.000000',
                        pending_commission: '20.000000',
                        cancelled_commission: '0.000000',
                        total_commission: '90.000000',
                    }]}
                />
            </TestRouter>,
        );

        expect(screen.getByRole('heading', { name: 'Profitability overview' })).toBeInTheDocument();
        expect(screen.getByText('Gross profit')).toBeInTheDocument();
        expect(screen.getByText('Nimal Perera')).toBeInTheDocument();
        expect(screen.getByText(/3 completed \/ 4 assigned jobs/)).toBeInTheDocument();
        expect(screen.getByRole('link', { name: /Nimal Perera/ })).toHaveAttribute(
            'href',
            expect.stringContaining('search=EMP-007'),
        );
    });
});
