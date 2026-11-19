import { beforeEach, describe, expect, it } from 'vitest';
import { render, screen, within, fireEvent } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { TestRouter } from '@/test/TestRouter';
import QuickAccessSettingsPage from './QuickAccessSettingsPage';
import {
    DEFAULT_QUICK_ACCESS_ITEMS,
    QUICK_ACCESS_STORAGE_KEY,
    getStoredQuickAccessItems,
} from './quickAccessService';

describe('QuickAccessSettingsPage', () => {
    beforeEach(() => {
        window.localStorage.clear();
    });

    it('renders the page header, summary banner, table with default items, and live preview', () => {
        render(
            <TestRouter>
                <QuickAccessSettingsPage />
            </TestRouter>
        );

        expect(screen.getByRole('heading', { level: 1, name: 'Quick Access Settings' })).toBeInTheDocument();
        expect(screen.getByText('Configured Shortcuts:')).toBeInTheDocument();
        expect(screen.getByText('Total:')).toBeInTheDocument();
        expect(screen.getByText('Visible:')).toBeInTheDocument();

        const table = screen.getByRole('table');
        expect(within(table).getByText('Create Service Job')).toBeInTheDocument();
        expect(within(table).getByText('Service Job List')).toBeInTheDocument();
        expect(within(table).getByText('Create Selling')).toBeInTheDocument();
        expect(within(table).getByText('Selling List')).toBeInTheDocument();

        // Check preview section
        expect(screen.getByText('Live Dropdown Preview')).toBeInTheDocument();
    });

    it('toggles visibility of an item and updates localStorage', async () => {
        const user = userEvent.setup();
        render(
            <TestRouter>
                <QuickAccessSettingsPage />
            </TestRouter>
        );

        const toggleBtn = screen.getByRole('switch', { name: 'Toggle visibility for Create Service Job' });
        expect(toggleBtn).toHaveAttribute('aria-checked', 'true');
        expect(within(toggleBtn).getByText('Visible')).toBeInTheDocument();

        await user.click(toggleBtn);

        expect(toggleBtn).toHaveAttribute('aria-checked', 'false');
        expect(within(toggleBtn).getByText('Hidden')).toBeInTheDocument();

        // Verify in localStorage
        const stored = getStoredQuickAccessItems();
        const item = stored.find((i) => i.id === 'create-service-job');
        expect(item?.enabled).toBe(false);
    });

    it('reorders items when clicking move up and move down', async () => {
        const user = userEvent.setup();
        render(
            <TestRouter>
                <QuickAccessSettingsPage />
            </TestRouter>
        );

        const moveDownFirst = screen.getByRole('button', { name: 'Move Create Service Job down' });
        await user.click(moveDownFirst);

        const stored = getStoredQuickAccessItems();
        expect(stored[0].id).toBe('service-jobs');
        expect(stored[1].id).toBe('create-service-job');
    });

    it('reorders items via drag and drop', () => {
        render(
            <TestRouter>
                <QuickAccessSettingsPage />
            </TestRouter>
        );

        const rows = screen.getAllByRole('row');
        // row 0 is header, row 1 is Create Service Job, row 2 is Service Job List
        const firstRow = rows[1];
        const secondRow = rows[2];

        fireEvent.dragStart(firstRow, { dataTransfer: { setData: () => {}, effectAllowed: 'move' } });
        fireEvent.dragOver(secondRow, { preventDefault: () => {}, dataTransfer: { dropEffect: 'move' } });
        fireEvent.drop(secondRow, { preventDefault: () => {} });
        fireEvent.dragEnd(firstRow);

        const stored = getStoredQuickAccessItems();
        expect(stored[0].id).toBe('service-jobs');
        expect(stored[1].id).toBe('create-service-job');
    });

    it('adds a new shortcut via modal with form validation and persistence', async () => {
        const user = userEvent.setup();
        render(
            <TestRouter>
                <QuickAccessSettingsPage />
            </TestRouter>
        );

        await user.click(screen.getByRole('button', { name: 'Add Shortcut' }));

        const modal = screen.getByRole('dialog', { name: 'Add Quick Access Shortcut' });
        expect(modal).toBeInTheDocument();

        // Submit empty to trigger validation
        await user.click(within(modal).getByRole('button', { name: 'Save Shortcut' }));
        expect(screen.getByText('Label is required.')).toBeInTheDocument();

        // Fill form
        await user.type(within(modal).getByLabelText(/Label \/ Title/i), 'Customer Directory');
        await user.type(within(modal).getByLabelText(/Route \/ URL Path/i), '/customers');
        const groupInput = within(modal).getByLabelText(/Group Name/i);
        await user.clear(groupInput);
        await user.type(groupInput, 'CRM');

        await user.click(within(modal).getByRole('button', { name: 'Save Shortcut' }));

        // Modal should close and item should be in table
        expect(screen.queryByRole('dialog', { name: 'Add Quick Access Shortcut' })).not.toBeInTheDocument();
        const table = screen.getByRole('table');
        expect(within(table).getByText('Customer Directory')).toBeInTheDocument();
        expect(within(table).getByText('/customers')).toBeInTheDocument();
        expect(within(table).getByText('CRM')).toBeInTheDocument();

        const stored = getStoredQuickAccessItems();
        expect(stored.some((i) => i.label === 'Customer Directory' && i.to === '/customers')).toBe(true);
    });

    it('edits an existing shortcut and updates localStorage', async () => {
        const user = userEvent.setup();
        render(
            <TestRouter>
                <QuickAccessSettingsPage />
            </TestRouter>
        );

        await user.click(screen.getByRole('button', { name: 'Edit Create Selling' }));

        const dialog = screen.getByRole('dialog', { name: 'Edit Quick Access Shortcut' });
        expect(dialog).toBeInTheDocument();

        const labelInput = within(dialog).getByLabelText(/Label \/ Title/i);
        await user.clear(labelInput);
        await user.type(labelInput, 'New Direct Sale');

        await user.click(within(dialog).getByRole('button', { name: 'Save Changes' }));

        expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
        const table = screen.getByRole('table');
        expect(within(table).getByText('New Direct Sale')).toBeInTheDocument();

        const stored = getStoredQuickAccessItems();
        expect(stored.find((i) => i.id === 'selling-create')?.label).toBe('New Direct Sale');
    });

    it('deletes a shortcut when confirmed', async () => {
        const user = userEvent.setup();
        render(
            <TestRouter>
                <QuickAccessSettingsPage />
            </TestRouter>
        );

        await user.click(screen.getByRole('button', { name: 'Delete Create Service Job' }));

        const confirmModal = screen.getByRole('dialog', { name: 'Delete Quick Access Shortcut' });
        expect(confirmModal).toBeInTheDocument();

        await user.click(within(confirmModal).getByRole('button', { name: 'Delete' }));

        const table = screen.getByRole('table');
        expect(within(table).queryByText('Create Service Job')).not.toBeInTheDocument();
        const stored = getStoredQuickAccessItems();
        expect(stored.some((i) => i.id === 'create-service-job')).toBe(false);
    });

    it('validates against invalid route formats and duplicate route paths', async () => {
        const user = userEvent.setup();
        render(
            <TestRouter>
                <QuickAccessSettingsPage />
            </TestRouter>
        );

        await user.click(screen.getByRole('button', { name: 'Add Shortcut' }));
        const modal = screen.getByRole('dialog', { name: 'Add Quick Access Shortcut' });

        // Protocol-relative invalid route
        await user.type(within(modal).getByLabelText(/Label \/ Title/i), 'Malicious');
        await user.type(within(modal).getByLabelText(/Route \/ URL Path/i), '//evil.com');
        await user.click(within(modal).getByRole('button', { name: 'Save Shortcut' }));
        expect(screen.getByText('Route must start with a single forward slash (/), e.g. /customers.')).toBeInTheDocument();

        // Duplicate route path that already exists in table (/vehicle-service/jobs/create)
        const toInput = within(modal).getByLabelText(/Route \/ URL Path/i);
        await user.clear(toInput);
        await user.type(toInput, '/vehicle-service/jobs/create/'); // trailing slash normalized and detected
        await user.click(within(modal).getByRole('button', { name: 'Save Shortcut' }));
        expect(screen.getByText('A shortcut with this route path already exists.')).toBeInTheDocument();
    });

    it('populates fields when selecting a preset from the dropdown', async () => {
        const user = userEvent.setup();
        render(
            <TestRouter>
                <QuickAccessSettingsPage />
            </TestRouter>
        );

        await user.click(screen.getByRole('button', { name: 'Add Shortcut' }));
        const modal = screen.getByRole('dialog', { name: 'Add Quick Access Shortcut' });

        const presetSelect = within(modal).getByLabelText(/Quick Presets/i);
        await user.selectOptions(presetSelect, 'customer-create');

        expect(within(modal).getByLabelText(/Label \/ Title/i)).toHaveValue('Create Customer');
        expect(within(modal).getByLabelText(/Route \/ URL Path/i)).toHaveValue('/customers/create');
        expect(within(modal).getByLabelText(/Group Name/i)).toHaveValue('Customers');
    });

    it('preserves an empty state in storage when all items are deleted', async () => {
        const user = userEvent.setup();
        // Start with single item
        window.localStorage.setItem(QUICK_ACCESS_STORAGE_KEY, JSON.stringify([
            { id: 'solo-item', label: 'Solo Link', to: '/solo', group: 'Solo Group', icon: 'list', enabled: true, order: 1 },
        ]));

        render(
            <TestRouter>
                <QuickAccessSettingsPage />
            </TestRouter>
        );

        const table = screen.getByRole('table');
        expect(within(table).getByText('Solo Link')).toBeInTheDocument();
        await user.click(screen.getByRole('button', { name: 'Delete Solo Link' }));
        const confirmModal = screen.getByRole('dialog', { name: 'Delete Quick Access Shortcut' });
        await user.click(within(confirmModal).getByRole('button', { name: 'Delete' }));

        expect(screen.getByText('No shortcuts configured')).toBeInTheDocument();
        expect(getStoredQuickAccessItems()).toEqual([]);
    });

    it('resets shortcuts to default presets when confirmed', async () => {
        const user = userEvent.setup();
        // Pre-populate with single item
        window.localStorage.setItem(QUICK_ACCESS_STORAGE_KEY, JSON.stringify([
            { id: 'custom-only', label: 'Only One', to: '/only', group: 'Solo', icon: 'list', enabled: true, order: 1 },
        ]));

        render(
            <TestRouter>
                <QuickAccessSettingsPage />
            </TestRouter>
        );

        const table = screen.getByRole('table');
        expect(within(table).getByText('Only One')).toBeInTheDocument();

        await user.click(screen.getByRole('button', { name: 'Reset Defaults' }));

        const confirmModal = screen.getByRole('dialog', { name: 'Reset to Default Shortcuts' });
        expect(confirmModal).toBeInTheDocument();

        await user.click(within(confirmModal).getByRole('button', { name: 'Reset Defaults' }));

        expect(within(table).queryByText('Only One')).not.toBeInTheDocument();
        expect(within(table).getByText('Create Service Job')).toBeInTheDocument();
        expect(within(table).getByText('Service Job List')).toBeInTheDocument();
        expect(getStoredQuickAccessItems()).toEqual(DEFAULT_QUICK_ACCESS_ITEMS);
    });
});
