import { useState } from 'react';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { MemoryRouter } from 'react-router-dom';
import { describe, expect, it, vi } from 'vitest';
import type { NavigationSection } from '@/app/navigation/navigationTypes';
import { Sidebar } from './Sidebar';

const sections: NavigationSection[] = [
    {
        id: 'operations',
        label: 'Operations',
        items: [
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
                id: 'sales',
                type: 'module',
                label: 'Sales',
                icon: 'sales',
                children: [
                    { id: 'sales-orders', type: 'link', label: 'Sales Orders', to: '/sales/orders' },
                ],
            },
        ],
    },
];

describe('Sidebar', () => {
    it('keeps only one module expanded and highlights the active child', async () => {
        const user = userEvent.setup();
        render(<SidebarHarness />);

        expect(screen.getByRole('link', { name: 'Purchase Orders' })).toHaveAttribute('aria-current', 'page');
        expect(screen.queryByRole('link', { name: 'Sales Orders' })).not.toBeInTheDocument();

        await user.click(screen.getByRole('button', { name: 'Sales' }));

        expect(screen.queryByRole('link', { name: 'Purchase Orders' })).not.toBeInTheDocument();
        expect(screen.getByRole('link', { name: 'Sales Orders' })).toBeInTheDocument();
        expect(screen.getByRole('button', { name: 'Purchase' })).toHaveAttribute('aria-expanded', 'false');
        expect(screen.getByRole('button', { name: 'Sales' })).toHaveAttribute('aria-expanded', 'true');
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
        <MemoryRouter>
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
                onCloseMobile={() => undefined}
                onExpandDesktop={() => undefined}
                onToggleModule={(moduleId) => setExpanded((current) => current === moduleId ? null : moduleId)}
            />
        </MemoryRouter>
    );
}

function renderSidebar(mobileOpen: boolean, onClose: () => void) {
    return render(sidebarElement(mobileOpen, onClose));
}

function sidebarElement(mobileOpen: boolean, onClose: () => void) {
    return (
        <MemoryRouter>
            <Sidebar
                sections={sections}
                activeItemId={null}
                activeParentId={null}
                expandedModuleId={null}
                mobileOpen={mobileOpen}
                collapsed={false}
                user={null}
                tenant={null}
                organizationUnit={null}
                onCloseMobile={onClose}
                onExpandDesktop={() => undefined}
                onToggleModule={() => undefined}
            />
        </MemoryRouter>
    );
}
