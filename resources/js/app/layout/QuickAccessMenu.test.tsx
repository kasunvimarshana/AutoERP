import { beforeEach, describe, expect, it } from 'vitest';
import { act, render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { TestRouter } from '@/test/TestRouter';
import type { NavigationSection } from '@/app/navigation/navigationTypes';
import { QuickAccessMenu } from './QuickAccessMenu';
import {
    saveQuickAccessItems,
    QUICK_ACCESS_STORAGE_KEY,
    type QuickAccessItemConfig,
} from '@/modules/settings/quickAccessService';

const sections: NavigationSection[] = [{
    id: 'operations',
    items: [
        {
            id: 'vehicle-service', type: 'module', label: 'Vehicle Service', icon: 'service', children: [
                { id: 'create-service-job', type: 'link', label: 'Create Service Job', to: '/vehicle-service/jobs/create' },
                { id: 'service-jobs', type: 'link', label: 'Service Job List', to: '/vehicle-service/jobs' },
            ],
        },
        {
            id: 'selling', type: 'module', label: 'Selling', icon: 'purchase', children: [
                { id: 'selling-create', type: 'link', label: 'Create Selling', to: '/selling/create' },
                { id: 'selling-list', type: 'link', label: 'Selling List', to: '/selling' },
            ],
        },
        {
            id: 'customers-module', type: 'module', label: 'Customers', icon: 'customer', children: [
                { id: 'customer-list', type: 'link', label: 'Customer List', to: '/customers' },
            ],
        },
    ],
}];

describe('QuickAccessMenu', () => {
    beforeEach(() => {
        window.localStorage.clear();
    });

    it('opens on hover and navigates with the existing links', async () => {
        const user = userEvent.setup();
        render(<TestRouter><QuickAccessMenu sections={sections} activeItemId="service-jobs" /></TestRouter>);

        const trigger = screen.getByRole('button', { name: 'Quick Access' });
        expect(trigger).toHaveAttribute('aria-expanded', 'false');
        await user.hover(trigger);

        const menu = screen.getByRole('navigation', { name: 'Quick access' });
        expect(trigger).toHaveAttribute('aria-expanded', 'true');
        expect(within(menu).getByRole('link', { name: 'Create Service Job' })).toHaveAttribute('href', '/vehicle-service/jobs/create');
        expect(within(menu).getByRole('link', { name: 'Service Job List' })).toHaveAttribute('aria-current', 'page');
        expect(within(menu).getByRole('link', { name: 'Create Selling' })).toHaveAttribute('href', '/selling/create');
        expect(within(menu).getByRole('link', { name: 'Selling List' })).toHaveAttribute('href', '/selling');

        // Verify Customize link
        expect(within(menu).getByRole('link', { name: 'Customize' })).toHaveAttribute('href', '/settings/quick-access');

        await user.click(within(menu).getByRole('link', { name: 'Create Service Job' }));
        expect(screen.queryByRole('navigation', { name: 'Quick access' })).not.toBeInTheDocument();
    });

    it('opens by keyboard and closes with Escape or outside click', async () => {
        const user = userEvent.setup();
        render(<TestRouter><QuickAccessMenu sections={sections} activeItemId={null} /></TestRouter>);

        const trigger = screen.getByRole('button', { name: 'Quick Access' });
        trigger.focus();
        await user.keyboard('{Enter}');
        expect(screen.getByRole('navigation', { name: 'Quick access' })).toBeInTheDocument();
        await user.keyboard('{Escape}');
        expect(screen.queryByRole('navigation', { name: 'Quick access' })).not.toBeInTheDocument();
        expect(trigger).toHaveFocus();

        await user.click(trigger);
        await user.click(document.body);
        expect(screen.queryByRole('navigation', { name: 'Quick access' })).not.toBeInTheDocument();
    });

    it('shows only links passed through permission-filtered navigation', async () => {
        const user = userEvent.setup();
        render(<TestRouter><QuickAccessMenu sections={[{ ...sections[0], items: [sections[0].items[0]] }]} activeItemId={null} /></TestRouter>);

        await user.click(screen.getByRole('button', { name: 'Quick Access' }));
        expect(screen.getByRole('link', { name: 'Create Service Job' })).toBeInTheDocument();
        expect(screen.queryByText('Selling')).not.toBeInTheDocument();
        expect(screen.queryByRole('link', { name: 'Create Selling' })).not.toBeInTheDocument();
    });

    it('dynamically loads custom configured items from localStorage', async () => {
        const customItems: QuickAccessItemConfig[] = [
            { id: 'customer-list', label: 'All Customers', to: '/customers', group: 'Clients', icon: 'customer', enabled: true, order: 1 },
        ];
        window.localStorage.setItem(QUICK_ACCESS_STORAGE_KEY, JSON.stringify(customItems));

        const user = userEvent.setup();
        render(<TestRouter><QuickAccessMenu sections={sections} activeItemId={null} /></TestRouter>);

        await user.click(screen.getByRole('button', { name: 'Quick Access' }));
        const menu = screen.getByRole('navigation', { name: 'Quick access' });

        expect(within(menu).getByText('Clients')).toBeInTheDocument();
        expect(within(menu).getByRole('link', { name: 'All Customers' })).toHaveAttribute('href', '/customers');
        expect(within(menu).queryByText('Vehicle Service')).not.toBeInTheDocument();
    });

    it('hides disabled items from the dropdown menu', async () => {
        const customItems: QuickAccessItemConfig[] = [
            { id: 'create-service-job', label: 'Create Service Job', to: '/vehicle-service/jobs/create', group: 'Vehicle Service', icon: 'service', enabled: false, order: 1 },
            { id: 'service-jobs', label: 'Service Job List', to: '/vehicle-service/jobs', group: 'Vehicle Service', icon: 'list', enabled: true, order: 2 },
        ];
        window.localStorage.setItem(QUICK_ACCESS_STORAGE_KEY, JSON.stringify(customItems));

        const user = userEvent.setup();
        render(<TestRouter><QuickAccessMenu sections={sections} activeItemId={null} /></TestRouter>);

        await user.click(screen.getByRole('button', { name: 'Quick Access' }));
        const menu = screen.getByRole('navigation', { name: 'Quick access' });

        expect(within(menu).queryByRole('link', { name: 'Create Service Job' })).not.toBeInTheDocument();
        expect(within(menu).getByRole('link', { name: 'Service Job List' })).toBeInTheDocument();
    });

    it('reacts dynamically to configuration updates without full reload', async () => {
        const user = userEvent.setup();
        render(<TestRouter><QuickAccessMenu sections={sections} activeItemId={null} /></TestRouter>);

        const trigger = screen.getByRole('button', { name: 'Quick Access' });
        await user.click(trigger);

        let menu = screen.getByRole('navigation', { name: 'Quick access' });
        expect(within(menu).getByRole('link', { name: 'Create Service Job' })).toBeInTheDocument();

        // Update items in storage and notify listeners
        act(() => {
            saveQuickAccessItems([
                { id: 'customer-list', label: 'Directory', to: '/customers', group: 'People', icon: 'customer', enabled: true, order: 1 },
            ]);
        });

        menu = screen.getByRole('navigation', { name: 'Quick access' });
        expect(within(menu).queryByRole('link', { name: 'Create Service Job' })).not.toBeInTheDocument();
        expect(within(menu).getByRole('link', { name: 'Directory' })).toBeInTheDocument();
    });

    it('highlights active item when viewing a route with query string', async () => {
        const queryItems: QuickAccessItemConfig[] = [
            { id: 'custom-query-link', label: 'Service Invoices', to: '/vehicle-service/jobs?view=completed', group: 'Service', icon: 'list', enabled: true, order: 1 },
        ];
        window.localStorage.setItem(QUICK_ACCESS_STORAGE_KEY, JSON.stringify(queryItems));

        const user = userEvent.setup();
        render(
            <TestRouter initialEntries={['/vehicle-service/jobs?view=completed']}>
                <QuickAccessMenu sections={sections} activeItemId={null} />
            </TestRouter>
        );

        const trigger = screen.getByRole('button', { name: 'Quick Access' });
        await user.click(trigger);

        const menu = screen.getByRole('navigation', { name: 'Quick access' });
        const link = within(menu).getByRole('link', { name: 'Service Invoices' });
        expect(link).toHaveAttribute('aria-current', 'page');
    });

    it('does not render when storage contains an intentionally empty list', () => {
        window.localStorage.setItem(QUICK_ACCESS_STORAGE_KEY, JSON.stringify([]));
        render(<TestRouter><QuickAccessMenu sections={sections} activeItemId={null} /></TestRouter>);
        expect(screen.queryByRole('button', { name: 'Quick Access' })).not.toBeInTheDocument();
    });
});
