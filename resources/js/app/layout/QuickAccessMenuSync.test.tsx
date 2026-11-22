/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

import { beforeEach, describe, expect, it, vi } from 'vitest';
import { act, render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { TestRouter } from '@/test/TestRouter';
import type { NavigationSection } from '@/app/navigation/navigationTypes';
import { QuickAccessMenu } from './QuickAccessMenu';
import * as quickAccessApi from '@/modules/settings/quickAccessApi';
import { getQuickAccessStorageKey, getStoredRowVersion } from '@/modules/settings/quickAccessService';

const mockAuth = {
    tenant: { id: 7, name: 'Tenant Seven' },
    user: { id: 42, name: 'Test User' },
};

vi.mock('@/modules/auth/AuthProvider', () => ({
    useAuth: () => mockAuth,
}));

const sections: NavigationSection[] = [{
    id: 'operations',
    items: [
        {
            id: 'customers-module', type: 'module', label: 'Customers', icon: 'customer', children: [
                { id: 'customer-list', type: 'link', label: 'Customer List', to: '/customers' },
            ],
        },
    ],
}];

describe('QuickAccessMenu authenticated background sync', () => {
    beforeEach(() => {
        window.localStorage.clear();
        vi.restoreAllMocks();
    });

    it('syncs custom shortcuts from database in the background and updates the menu', async () => {
        const storageKey = getQuickAccessStorageKey(7, 42);
        expect(window.localStorage.getItem(storageKey)).toBeNull();

        const fetchSpy = vi.spyOn(quickAccessApi, 'fetchQuickAccessSettings').mockResolvedValue({
            items: [
                { id: 'customer-list', label: 'Live Synced Customers', to: '/customers', group: 'Customers', icon: 'customer', enabled: true, order: 1 },
            ],
            is_default: false,
            row_version: 3,
        });

        const user = userEvent.setup();
        render(<TestRouter><QuickAccessMenu sections={sections} activeItemId={null} /></TestRouter>);

        expect(fetchSpy).toHaveBeenCalled();

        // Wait for promise resolution and react update
        await act(async () => {
            await Promise.resolve();
        });

        // Verify storage was populated with server items and row version
        const cached = JSON.parse(window.localStorage.getItem(storageKey)!);
        expect(cached[0].label).toBe('Live Synced Customers');
        expect(getStoredRowVersion(storageKey)).toBe(3);

        // Open menu and verify synced items
        await user.click(screen.getByRole('button', { name: 'Quick Access' }));
        const menu = screen.getByRole('navigation', { name: 'Quick access' });
        expect(within(menu).getByRole('link', { name: 'Live Synced Customers' })).toBeInTheDocument();
    });
});
