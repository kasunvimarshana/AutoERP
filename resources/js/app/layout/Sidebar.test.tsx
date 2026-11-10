import { useState } from 'react';
import { TestRouter } from '@/test/TestRouter';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it, vi } from 'vitest';
import type { NavigationSection } from '@/app/navigation/navigationTypes';
import { Sidebar } from './Sidebar';
const sections: NavigationSection[] = [
    {
        id: 'operations',
        label: 'Operations',
        items: [
            {
                id: 'vehicle-service',
                type: 'module',
                label: 'Vehicle Service',
                icon: 'service',
                children: [
                    { id: 'create-service-job', type: 'link', label: 'Create Service Job', to: '/vehicle-service/jobs/create' },
                    { id: 'service-jobs', type: 'link', label: 'Service Job List', to: '/vehicle-service/jobs' },
                ],
            },
            {
                id: 'selling',
                type: 'module',
                label: 'Selling',
                icon: 'purchase',
                children: [
                    { id: 'selling-create', type: 'link', label: 'Create Selling', to: '/selling/create' },
                    { id: 'selling-list', type: 'link', label: 'Selling List', to: '/selling' },
                ],
            },
            {
                id: 'purchase',
                type: 'module',
                label: 'Purchase',
                icon: 'purchase',
                children: [
                    { id: 'purchase-orders', type: 'link', label: 'Purchase Orders', to: '/purchase/orders' },
                ],
            },
            {
                id: 'payment',
                type: 'module',
                label: 'Payments',
                icon: 'payment',
                children: [
                    { id: 'payments-list', type: 'link', label: 'Payments', to: '/payments' },
                ],
            },
        ],
    },
];
describe('Sidebar', () => {
    it('keeps visible service and selling shortcuts outside the scrolling navigation', () => {
        render(<SidebarHarness />);
        const quickAccess = screen.getByRole('navigation', { name: 'Quick access' });
        expect(quickAccess).toHaveClass('shrink-0');
        expect(screen.getByRole('navigation', { name: 'Main navigation' })).toHaveClass('overflow-y-auto');
        expect(quickAccess.querySelectorAll('a')).toHaveLength(4);
        expect(screen.getByRole('link', { name: 'Create Service Job' })).toHaveAttribute('href', '/vehicle-service/jobs/create');
        expect(screen.getByRole('link', { name: 'Service Job List' })).toHaveAttribute('href', '/vehicle-service/jobs');
        expect(screen.getByRole('link', { name: 'Create Selling' })).toHaveAttribute('href', '/selling/create');
        expect(screen.getByRole('link', { name: 'Selling List' })).toHaveAttribute('href', '/selling');
    });

    it('keeps quick access links named when the desktop sidebar is collapsed', () => {
        render(sidebarElement(true, () => undefined, true));
        expect(screen.getByRole('navigation', { name: 'Quick access' }).querySelectorAll('a')).toHaveLength(4);
        expect(screen.getByRole('link', { name: 'Create Service Job' })).toHaveAttribute('title', 'Create Service Job');
    });

    it('keeps only one module expanded and highlights the active child', async () => {
        const user = userEvent.setup();
        render(<SidebarHarness />);
        expect(screen.getByRole('link', { name: 'Purchase Orders' })).toHaveAttribute('aria-current', 'page');
        expect(screen.queryByRole('link', { name: 'Payments' })).not.toBeInTheDocument();
        await user.click(screen.getByRole('button', { name: 'Payments' }));
        expect(screen.queryByRole('link', { name: 'Purchase Orders' })).not.toBeInTheDocument();
        expect(screen.getByRole('link', { name: 'Payments' })).toBeInTheDocument();
        expect(screen.getByRole('button', { name: 'Purchase' })).toHaveAttribute('aria-expanded', 'false');
        expect(screen.getByRole('button', { name: 'Payments' })).toHaveAttribute('aria-expanded', 'true');
    });
    it('uses drawer visibility classes and a close backdrop on mobile', async () => {
        const onClose = vi.fn();
        const { rerender } = renderSidebar(false, onClose);
        expect(screen.getByLabelText('Application sidebar')).toHaveClass('-translate-x-full');
        expect(screen.queryByRole('button', { name: 'Close navigation' })).toBeInTheDocument();
        rerender(sidebarElement(true, onClose));
        expect(screen.getByLabelText('Application sidebar')).toHaveClass('translate-x-0');
        const closeButtons = screen.getAllByRole('button', { name: 'Close navigation' });
        await userEvent.click(closeButtons[0]);
        expect(onClose).toHaveBeenCalledOnce();
    });
});
function SidebarHarness() {
    const [expanded, setExpanded] = useState<string | null>('purchase');
    return (
        <TestRouter>
            <Sidebar
                sections={sections}
                activeItemId="purchase-orders"
                activeParentId="purchase"
                expandedModuleId={expanded}
                mobileOpen
                collapsed={false}
                user={{ id: 1, name: 'Admin User', email: 'admin@example.com' }}
                tenant={{ id: 1, name: 'AutoERP' }}
                organizationUnit={{ id: 1, name: 'Main Branch' }}
                homePath="/dashboard"
                workspaceLabel="Business workspace"
                onCloseMobile={() => undefined}
                onExpandDesktop={() => undefined}
                onToggleModule={(moduleId) => setExpanded((current) => current === moduleId ? null : moduleId)}
            />
        </TestRouter>
    );
}
function renderSidebar(mobileOpen: boolean, onClose: () => void) {
    return render(sidebarElement(mobileOpen, onClose));
}
function sidebarElement(mobileOpen: boolean, onClose: () => void, collapsed = false) {
    return (
        <TestRouter>
            <Sidebar
                sections={sections}
                activeItemId={null}
                activeParentId={null}
                expandedModuleId={null}
                mobileOpen={mobileOpen}
                collapsed={collapsed}
                user={null}
                tenant={null}
                organizationUnit={null}
                homePath="/dashboard"
                workspaceLabel="Business workspace"
                onCloseMobile={onClose}
                onExpandDesktop={() => undefined}
                onToggleModule={() => undefined}
            />
        </TestRouter>
    );
}
