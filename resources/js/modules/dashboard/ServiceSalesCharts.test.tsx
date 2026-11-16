import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import { TestRouter } from '@/test/TestRouter';
import { ServiceSalesCharts } from './ServiceSalesCharts';
import type { DashboardSummary } from './dashboardTypes';

describe('ServiceSalesCharts', () => {
    it('shows invoice-week activity, job type profit, and current reorder risk', () => {
        const sales: DashboardSummary['service_sales'] = {
            by_week: [
                { week_start: '2026-11-02', job_count: 2, revenue: '700' },
                { week_start: '2026-11-09', job_count: 0, revenue: '0' },
            ],
            by_job_type: [
                { type: 'body_wash', label: 'Body Wash', job_count: 2, revenue: '700', profit: '-50' },
            ],
            top_stock_items: [
                { item: { id: 4, code: 'OIL', name: 'Engine oil' }, quantity_sold: '3', revenue: '600', quantity_available: '2', reorder_level: '5', needs_reorder: true },
            ],
        };

        render(<TestRouter><ServiceSalesCharts sales={sales} currency="LKR" /></TestRouter>);

        expect(screen.getByRole('img', { name: '2 jobs' })).toBeInTheDocument();
        expect(screen.getByRole('img', { name: /Body Wash profit LKR -50/ })).toBeInTheDocument();
        expect(screen.getByText('Reorder')).toBeInTheDocument();
        expect(screen.getByText(/2 available/)).toBeInTheDocument();
        expect(screen.getByRole('link', { name: 'View sales report' })).toHaveAttribute('href', '/reports/vehicle-service/sales-summary');
        expect(screen.getByRole('link', { name: 'View low stock report' })).toHaveAttribute('href', '/reports/inventory.low-stock');
    });

    it('explains empty service sales', () => {
        render(<TestRouter><ServiceSalesCharts sales={{
            by_week: [{ week_start: '2026-11-09', job_count: 0, revenue: '0' }],
            by_job_type: [],
            top_stock_items: [],
        }} currency="LKR" /></TestRouter>);

        expect(screen.getByText('No service jobs were invoiced in this period.')).toBeInTheDocument();
        expect(screen.getByText('No invoiced job profit is available in this period.')).toBeInTheDocument();
        expect(screen.getByText('No stock items were invoiced in this period.')).toBeInTheDocument();
    });
});
