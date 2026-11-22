/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { TestRouter } from '@/test/TestRouter';
import InvoiceLayoutsPage from './InvoiceLayoutsPage';
import type { InvoiceLayout } from '../invoiceLayoutTypes';

const layoutApi = vi.hoisted(() => ({
    listInvoiceLayouts: vi.fn(),
    getInvoiceLayout: vi.fn(),
    createInvoiceLayout: vi.fn(),
    updateInvoiceLayout: vi.fn(),
    deleteInvoiceLayout: vi.fn(),
    previewInvoiceLayout: vi.fn(),
    setDefaultInvoiceLayout: vi.fn(),
}));

const authState = vi.hoisted(() => ({
    permissions: ['invoices.layouts.view', 'invoices.layouts.manage'],
    organizationUnit: { id: 10, name: 'Main Branch' },
}));

vi.mock('../invoiceLayoutApi', () => layoutApi);
vi.mock('@/modules/auth/AuthProvider', () => ({
    useAuth: () => ({
        roles: [],
        permissions: authState.permissions,
        permissionsLoaded: true,
        organizationUnit: authState.organizationUnit,
    }),
}));

const mockLayouts: InvoiceLayout[] = [
    {
        id: 1,
        code: 'standard_a4',
        name: 'Classic Standard A4',
        description: 'Standard classic bordered layout on A4 portrait paper.',
        paper_size: 'a4',
        paper_size_label: 'A4 (210 × 297 mm)',
        orientation: 'portrait',
        view_template: 'invoice.layouts.standard',
        is_system: true,
        is_active: true,
        custom_css: null,
        header_content: null,
        footer_content: null,
        settings: { show_logo: true, show_tax_breakdown: true },
        row_version: 1,
        created_at: '2026-11-22T00:00:00Z',
        updated_at: '2026-11-22T00:00:00Z',
    },
    {
        id: 2,
        code: 'modern_a4',
        name: 'Modern Clean A4',
        description: 'Contemporary card-style accent headers layout.',
        paper_size: 'a4',
        paper_size_label: 'A4 (210 × 297 mm)',
        orientation: 'portrait',
        view_template: 'invoice.layouts.modern',
        is_system: true,
        is_active: true,
        custom_css: null,
        header_content: null,
        footer_content: null,
        settings: { show_logo: true, show_signatures: true },
        row_version: 1,
        created_at: '2026-11-22T00:00:00Z',
        updated_at: '2026-11-22T00:00:00Z',
    },
    {
        id: 10,
        code: 'custom_wholesale_a4',
        name: 'Wholesale Invoice Custom',
        description: 'Customized wholesale format for bulk clients.',
        paper_size: 'a4',
        paper_size_label: 'A4 (210 × 297 mm)',
        orientation: 'portrait',
        view_template: 'invoice.layouts.modern',
        is_system: false,
        is_active: true,
        custom_css: '.title { color: navy; }',
        header_content: null,
        footer_content: null,
        settings: { show_logo: true },
        row_version: 3,
        created_at: '2026-11-22T10:00:00Z',
        updated_at: '2026-11-22T12:00:00Z',
    },
];

describe('InvoiceLayoutsPage', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        layoutApi.listInvoiceLayouts.mockResolvedValue(mockLayouts);
    });

    it('renders the layout list with badges and action buttons', async () => {
        render(
            <TestRouter initialEntries={['/settings/invoice-layouts']}>
                <InvoiceLayoutsPage />
            </TestRouter>,
        );

        expect(await screen.findByText('Invoice Layout Templates')).toBeInTheDocument();
        expect(screen.getByText('Classic Standard A4')).toBeInTheDocument();
        expect(screen.getByText('Modern Clean A4')).toBeInTheDocument();
        expect(screen.getByText('Wholesale Invoice Custom')).toBeInTheDocument();

        // System badges
        const systemBadges = screen.getAllByText('System');
        expect(systemBadges.length).toBe(2);

        // Custom layout delete button exists
        expect(screen.getByRole('button', { name: 'Delete' })).toBeInTheDocument();
    });

    it('sets layout as tenant and branch default', async () => {
        layoutApi.setDefaultInvoiceLayout.mockResolvedValue({
            message: 'Invoice layout default set.',
            scope: 'tenant',
            code: 'modern_a4',
            organization_unit_id: null,
        });

        render(
            <TestRouter initialEntries={['/settings/invoice-layouts']}>
                <InvoiceLayoutsPage />
            </TestRouter>,
        );

        expect(await screen.findByText('Modern Clean A4')).toBeInTheDocument();

        // Find "Tenant Default" button for modern_a4
        const tenantDefaultButtons = screen.getAllByRole('button', { name: 'Tenant Default' });
        fireEvent.click(tenantDefaultButtons[1]); // second card is modern_a4

        await waitFor(() => {
            expect(layoutApi.setDefaultInvoiceLayout).toHaveBeenCalledWith({
                code: 'modern_a4',
                organization_unit_id: null,
                scope: 'tenant',
            });
        });

        expect(await screen.findByText(/set as default for organization/i)).toBeInTheDocument();
    });

    it('opens preview modal when clicking preview button', async () => {
        layoutApi.previewInvoiceLayout.mockResolvedValue('<html><body><h1>Invoice Preview</h1></body></html>');

        render(
            <TestRouter initialEntries={['/settings/invoice-layouts']}>
                <InvoiceLayoutsPage />
            </TestRouter>,
        );

        expect(await screen.findByText('Classic Standard A4')).toBeInTheDocument();

        const previewButtons = screen.getAllByRole('button', { name: 'Preview' });
        fireEvent.click(previewButtons[0]);

        await waitFor(() => {
            expect(layoutApi.previewInvoiceLayout).toHaveBeenCalledWith('standard_a4', undefined, expect.any(AbortSignal));
        });

        expect(await screen.findByText(/Preview: Classic Standard A4/i)).toBeInTheDocument();
    });

    it('opens editor drawer for creating new layout', async () => {
        render(
            <TestRouter initialEntries={['/settings/invoice-layouts']}>
                <InvoiceLayoutsPage />
            </TestRouter>,
        );

        expect(await screen.findByText('Invoice Layout Templates')).toBeInTheDocument();

        const newButton = screen.getByRole('button', { name: '+ New Custom Layout' });
        fireEvent.click(newButton);

        expect(await screen.findByText('Create New Invoice Layout')).toBeInTheDocument();
        expect(screen.getByLabelText(/Layout Name/i)).toBeInTheDocument();
    });
});
