import { beforeEach, describe, expect, it, vi } from 'vitest';
import {
    DEFAULT_QUICK_ACCESS_ITEMS,
    QUICK_ACCESS_STORAGE_KEY,
    QUICK_ACCESS_UPDATE_EVENT,
    getStoredQuickAccessItems,
    normalizePath,
    resetQuickAccessItems,
    saveQuickAccessItems,
    stripQuery,
    type QuickAccessItemConfig,
} from './quickAccessService';

describe('quickAccessService', () => {
    beforeEach(() => {
        window.localStorage.clear();
    });

    it('returns default quick access items when localStorage is empty', () => {
        const items = getStoredQuickAccessItems();
        expect(items).toEqual(DEFAULT_QUICK_ACCESS_ITEMS);
        expect(items.length).toBe(4);
        expect(items.map((i) => i.id)).toEqual([
            'create-service-job',
            'service-jobs',
            'selling-create',
            'selling-list',
        ]);
    });

    it('returns default items when localStorage contains invalid or corrupt JSON', () => {
        window.localStorage.setItem(QUICK_ACCESS_STORAGE_KEY, '{not-valid-json');
        expect(getStoredQuickAccessItems()).toEqual(DEFAULT_QUICK_ACCESS_ITEMS);

        window.localStorage.setItem(QUICK_ACCESS_STORAGE_KEY, JSON.stringify('a string not array'));
        expect(getStoredQuickAccessItems()).toEqual(DEFAULT_QUICK_ACCESS_ITEMS);

        // Corrupt array containing only invalid objects falls back to defaults
        window.localStorage.setItem(QUICK_ACCESS_STORAGE_KEY, JSON.stringify([{ corrupt: true }]));
        expect(getStoredQuickAccessItems()).toEqual(DEFAULT_QUICK_ACCESS_ITEMS);
    });

    it('preserves an intentionally empty list when user clears all shortcuts', () => {
        window.localStorage.setItem(QUICK_ACCESS_STORAGE_KEY, JSON.stringify([]));
        expect(getStoredQuickAccessItems()).toEqual([]);
    });

    it('sanitizes and parses valid stored items', () => {
        const custom: QuickAccessItemConfig[] = [
            {
                id: 'custom-1',
                label: 'Custom Customers',
                to: 'customers/', // missing leading slash and has trailing slash
                group: 'CRM',
                icon: 'customer',
                enabled: true,
                order: 2,
            },
            {
                id: 'custom-2',
                label: 'Custom Invoices',
                to: '/invoices?view=service',
                group: 'Finance',
                icon: 'invoice',
                enabled: false,
                order: 1,
            },
        ];
        window.localStorage.setItem(QUICK_ACCESS_STORAGE_KEY, JSON.stringify(custom));

        const stored = getStoredQuickAccessItems();
        expect(stored.length).toBe(2);
        // Sorted by order
        expect(stored[0].id).toBe('custom-2');
        expect(stored[1].id).toBe('custom-1');
        expect(stored[1].to).toBe('/customers'); // normalized with leading slash and trimmed trailing slash
    });

    it('saves items to localStorage, normalizes order, and dispatches the update event', () => {
        const listener = vi.fn();
        window.addEventListener(QUICK_ACCESS_UPDATE_EVENT, listener);

        const itemsToSave: QuickAccessItemConfig[] = [
            { id: 'item-1', label: 'Item 1', to: '/path-1', group: 'G1', icon: 'list', enabled: true, order: 99 },
            { id: 'item-2', label: 'Item 2', to: '/path-2', group: 'G2', icon: 'service', enabled: false, order: 100 },
        ];

        saveQuickAccessItems(itemsToSave);

        const raw = window.localStorage.getItem(QUICK_ACCESS_STORAGE_KEY);
        expect(raw).toBeTruthy();
        const parsed = JSON.parse(raw!);
        expect(parsed[0].order).toBe(1);
        expect(parsed[1].order).toBe(2);
        expect(listener).toHaveBeenCalledTimes(1);

        window.removeEventListener(QUICK_ACCESS_UPDATE_EVENT, listener);
    });

    it('resets items to defaults and notifies listeners', () => {
        window.localStorage.setItem(QUICK_ACCESS_STORAGE_KEY, JSON.stringify([{ id: 'foo', label: 'Bar', to: '/bar', group: 'G', icon: 'list', enabled: true, order: 1 }]));
        expect(getStoredQuickAccessItems().length).toBe(1);

        const reset = resetQuickAccessItems();
        expect(reset).toEqual(DEFAULT_QUICK_ACCESS_ITEMS);
        expect(getStoredQuickAccessItems()).toEqual(DEFAULT_QUICK_ACCESS_ITEMS);
    });

    it('correctly strips query strings and normalizes paths', () => {
        expect(stripQuery('/invoices?view=service&tab=all')).toBe('/invoices');
        expect(stripQuery('/settings')).toBe('/settings');

        expect(normalizePath('customers/create')).toBe('/customers/create');
        expect(normalizePath('/customers/create/')).toBe('/customers/create');
        expect(normalizePath('customers/')).toBe('/customers');
        expect(normalizePath('/invoices/?view=service')).toBe('/invoices?view=service');
        expect(normalizePath('')).toBe('/');
        expect(normalizePath('/')).toBe('/');
    });
});
