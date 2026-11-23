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
    uploadInvoiceBladeTemplate: vi.fn(),
    getInvoiceBladeExportUrl: vi.fn((idOrCode: number | string) => `/api/v1/invoice-layouts/${idOrCode}/export`),
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
        template_type: 'preset',
        view_template: 'invoice.layouts.standard',
        blade_content: null,
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
        template_type: 'preset',
        view_template: 'invoice.layouts.modern',
        blade_content: null,
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
        template_type: 'preset',
        view_template: 'invoice.layouts.modern',
        blade_content: null,
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
    {
        id: 11,
        code: 'custom_blade_v1',
        name: 'Custom Blade Format',
        description: 'Bespoke custom blade layout.',
        paper_size: 'a4',
        paper_size_label: 'A4 (210 × 297 mm)',
        orientation: 'portrait',
        template_type: 'blade',
        view_template: null,
        blade_content: '<div>{{ $document["title"] }}</div>',
        is_system: false,
        is_active: true,
        custom_css: null,
        header_content: null,
        footer_content: null,
        settings: {},
        row_version: 1,
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
        expect(screen.getByText('Custom Blade Format')).toBeInTheDocument();

        // Badges
        const systemBadges = screen.getAllByText('System');
        expect(systemBadges.length).toBe(2);
        expect(screen.getByText('Custom Blade')).toBeInTheDocument();

        // Custom layout delete button exists
        const deleteButtons = screen.getAllByRole('button', { name: 'Delete' });
        expect(deleteButtons.length).toBeGreaterThan(0);
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

    it('switches between visual preset and custom blade editor mode', async () => {
        render(
            <TestRouter initialEntries={['/settings/invoice-layouts']}>
                <InvoiceLayoutsPage />
            </TestRouter>,
        );

        const newButton = await screen.findByRole('button', { name: '+ New Custom Layout' });
        fireEvent.click(newButton);

        expect(await screen.findByText('Template Architecture')).toBeInTheDocument();
        const bladeTabButton = screen.getByText('Custom Blade Template');
        fireEvent.click(bladeTabButton);

        expect(await screen.findByText(/Blade Template \(\.blade\.php\)/i)).toBeInTheDocument();
        expect(screen.getByRole('button', { name: 'Upload .blade.php' })).toBeInTheDocument();
        expect(screen.getByRole('button', { name: 'Reset Boilerplate' })).toBeInTheDocument();
        expect(screen.getByText(/Available Blade Template Variables/i)).toBeInTheDocument();
    });
});
