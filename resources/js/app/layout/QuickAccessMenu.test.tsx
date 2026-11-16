import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { TestRouter } from '@/test/TestRouter';
import type { NavigationSection } from '@/app/navigation/navigationTypes';
import { QuickAccessMenu } from './QuickAccessMenu';

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
    ],
}];

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
