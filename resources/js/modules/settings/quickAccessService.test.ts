import { beforeEach, describe, expect, it, vi } from 'vitest';
import {
    DEFAULT_QUICK_ACCESS_ITEMS,
    QUICK_ACCESS_STORAGE_KEY,
    QUICK_ACCESS_UPDATE_EVENT,
    getAvailablePresets,
    getQuickAccessStorageKey,
    getStoredQuickAccessItems,
    getStoredRowVersion,
    normalizePath,
    parseRawItems,
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

    it('parseRawItems correctly parses arrays, preserves empty arrays, and falls back on corruption', () => {
        expect(parseRawItems([])).toEqual([]);

        expect(parseRawItems([{ corrupted: true }, null])).toEqual(DEFAULT_QUICK_ACCESS_ITEMS);

        const parsed = parseRawItems([
            { id: 'item-2', label: 'Item 2', to: '/path-2', group: 'G2', icon: 'list', enabled: true, order: 2 },
            { id: 'item-1', label: 'Item 1', to: '/path-1', group: 'G1', icon: 'list', enabled: true, order: 1 },
        ]);
        expect(parsed.length).toBe(2);
        expect(parsed[0].id).toBe('item-1');
        expect(parsed[1].id).toBe('item-2');
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

    it('scopes storage keys per tenant and user for isolated multi-tenant caching', () => {
        expect(getQuickAccessStorageKey()).toBe(QUICK_ACCESS_STORAGE_KEY);
        expect(getQuickAccessStorageKey(1, 42)).toBe('autoerp.quick_access_items.1.42');
        expect(getQuickAccessStorageKey('tenant-xyz', 'user-abc')).toBe('autoerp.quick_access_items.tenant-xyz.user-abc');

        // Storage isolation between users
        const keyUser1 = getQuickAccessStorageKey(1, 101);
        const keyUser2 = getQuickAccessStorageKey(1, 102);

        // Even if an unscoped global key exists in localStorage, keyUser2 does NOT leak it
        window.localStorage.setItem(QUICK_ACCESS_STORAGE_KEY, JSON.stringify([
            { id: 'global-leak', label: 'Global Leak', to: '/leak', group: 'Leak', icon: 'list', enabled: true, order: 1 },
        ]));

        saveQuickAccessItems([
            { id: 'u1-item', label: 'User 1 Item', to: '/customers', group: 'CRM', icon: 'customer', enabled: true, order: 1 },
        ], keyUser1);

        expect(getStoredQuickAccessItems(keyUser1)[0].id).toBe('u1-item');
        // User 2 sees defaults because their key has not been written, and does NOT leak global key
        expect(getStoredQuickAccessItems(keyUser2)).toEqual(DEFAULT_QUICK_ACCESS_ITEMS);
    });

    it('dynamically extracts presets from provided navigation sections', () => {
        const customSections = [
            {
                id: 'custom-section',
                label: 'Custom Operations',
                items: [
                    { id: 'single-link', type: 'link' as const, label: 'Single Link', to: '/custom-op', icon: 'service' as const },
                    {
                        id: 'nested-mod',
                        type: 'module' as const,
                        label: 'Nested Module',
                        icon: 'settings' as const,
                        children: [
                            { id: 'child-1', type: 'link' as const, label: 'Child One', to: '/nested/child-1', icon: 'list' as const },
                        ],
                    },
                ],
            },
        ];

        const presets = getAvailablePresets(customSections);
        expect(presets.some((p) => p.id === 'single-link' && p.group === 'Custom Operations')).toBe(true);
        expect(presets.some((p) => p.id === 'child-1' && p.group === 'Nested Module')).toBe(true);
    });

    it('stores and retrieves row version for optimistic concurrency control', () => {
        const key = getQuickAccessStorageKey(10, 20);
        expect(getStoredRowVersion(key)).toBeNull();

        saveQuickAccessItems(DEFAULT_QUICK_ACCESS_ITEMS, key, 3);
        expect(getStoredRowVersion(key)).toBe(3);
    });
});
