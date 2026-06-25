import { render, screen } from '@testing-library/react';
import { Link } from 'react-router-dom';
import { TestRouter } from '@/test/TestRouter';
import { describe, expect, it, vi } from 'vitest';
import PurchaseInvoiceListPage from './PurchaseInvoiceListPage';
const authMock = vi.hoisted(() => ({
    permissions: ['purchase.supplier_invoices.create'],
}));
vi.mock('@/modules/auth/AuthProvider', () => ({
    useAuth: () => ({ roles: [], permissions: authMock.permissions, permissionsLoaded: true }),
}));
vi.mock('@/modules/invoice/pages/InvoiceListPage', () => ({
    InvoiceListWorkspace: ({ viewKey, rowHref, renderHeader }: {
        viewKey?: string;
        rowHref?: (invoice: { id: number }) => string;
        renderHeader?: (view: { title: string; description: string }) => React.ReactNode;
    }) => (
        <section>
            {renderHeader?.({ title: 'Supplier Invoices', description: 'Purchase invoices payable to suppliers.' })}
            <p data-testid="invoice-view">{viewKey}</p>
            <Link to={rowHref?.({ id: 42 }) ?? '#'}>Open supplier invoice</Link>
        </section>
    ),
}));
describe('PurchaseInvoiceListPage', () => {
    it('renders the canonical supplier invoice list inside Purchase context without redirecting', () => {
        authMock.permissions = ['purchase.supplier_invoices.create'];
        render(
            <TestRouter initialEntries={['/purchase/invoices']}>
                <PurchaseInvoiceListPage />
            </TestRouter>,
        );
        expect(screen.getByRole('heading', { name: 'Supplier Invoices' })).toBeInTheDocument();
        expect(screen.getByTestId('invoice-view')).toHaveTextContent('supplier');
        expect(screen.getByRole('link', { name: 'Create supplier invoice' })).toHaveAttribute('href', '/purchase/invoices/create');
        expect(screen.getByRole('link', { name: 'Open supplier invoice' })).toHaveAttribute('href', '/invoices/42?from=purchase');
        expect(screen.queryByText('/invoices?view=supplier')).not.toBeInTheDocument();
    });
    it('hides supplier invoice creation without permission', () => {
        authMock.permissions = [];
        render(
            <TestRouter initialEntries={['/purchase/invoices']}>
                <PurchaseInvoiceListPage />
            </TestRouter>,
        );
        expect(screen.getByRole('heading', { name: 'Supplier Invoices' })).toBeInTheDocument();
        expect(screen.queryByRole('link', { name: 'Create supplier invoice' })).not.toBeInTheDocument();
    });
});
