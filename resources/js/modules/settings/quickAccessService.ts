import { useEffect, useState } from 'react';
import type { NavigationIconName } from '@/app/navigation/navigationTypes';

export interface QuickAccessItemConfig {
    id: string;
    label: string;
    to: string;
    group: string;
    icon: NavigationIconName;
    enabled: boolean;
    order: number;
}

export const QUICK_ACCESS_STORAGE_KEY = 'autoerp.quick_access_items';
export const QUICK_ACCESS_UPDATE_EVENT = 'autoerp:quick-access-updated';

export const QUICK_ACCESS_ICONS: readonly NavigationIconName[] = [
    'service',
    'purchase',
    'customer',
    'supplier',
    'item',
    'vehicle',
    'invoice',
    'payment',
    'voucher',
    'list',
    'users',
    'settings',
    'dashboard',
    'role',
    'permission',
];

export const DEFAULT_QUICK_ACCESS_ITEMS: readonly QuickAccessItemConfig[] = [
    {
        id: 'create-service-job',
        label: 'Create Service Job',
        to: '/vehicle-service/jobs/create',
        group: 'Vehicle Service',
        icon: 'service',
        enabled: true,
        order: 1,
    },
    {
        id: 'service-jobs',
        label: 'Service Job List',
        to: '/vehicle-service/jobs',
        group: 'Vehicle Service',
        icon: 'list',
        enabled: true,
        order: 2,
    },
    {
        id: 'selling-create',
        label: 'Create Selling',
        to: '/selling/create',
        group: 'Selling',
        icon: 'purchase',
        enabled: true,
        order: 3,
    },
    {
        id: 'selling-list',
        label: 'Selling List',
        to: '/selling',
        group: 'Selling',
        icon: 'list',
        enabled: true,
        order: 4,
    },
];

export interface SystemPresetItem {
    id: string;
    label: string;
    to: string;
    group: string;
    icon: NavigationIconName;
}

export const SYSTEM_PRESET_ITEMS: readonly SystemPresetItem[] = [
    { id: 'create-service-job', label: 'Create Service Job', to: '/vehicle-service/jobs/create', group: 'Vehicle Service', icon: 'service' },
    { id: 'service-jobs', label: 'Service Job List', to: '/vehicle-service/jobs', group: 'Vehicle Service', icon: 'list' },
    { id: 'service-promotions', label: 'Service Cards & Offers', to: '/vehicle-service/promotions', group: 'Vehicle Service', icon: 'service' },
    { id: 'service-invoices', label: 'Service Invoices', to: '/invoices?view=service', group: 'Vehicle Service', icon: 'invoice' },
    { id: 'service-payments', label: 'Customer Receipts (Service)', to: '/payments?view=service', group: 'Vehicle Service', icon: 'payment' },
    { id: 'selling-create', label: 'Create Selling', to: '/selling/create', group: 'Selling', icon: 'purchase' },
    { id: 'selling-list', label: 'Selling List', to: '/selling', group: 'Selling', icon: 'list' },
    { id: 'selling-returns', label: 'Sales Returns', to: '/selling/returns', group: 'Selling', icon: 'purchase' },
    { id: 'customer-list', label: 'Customer List', to: '/customers', group: 'Customers', icon: 'customer' },
    { id: 'customer-create', label: 'Create Customer', to: '/customers/create', group: 'Customers', icon: 'customer' },
    { id: 'supplier-list', label: 'Supplier List', to: '/suppliers', group: 'Suppliers', icon: 'supplier' },
    { id: 'supplier-create', label: 'Create Supplier', to: '/suppliers/create', group: 'Suppliers', icon: 'supplier' },
    { id: 'supplier-vehicles', label: 'Supplier Vehicles', to: '/supplier-vehicles', group: 'Suppliers', icon: 'vehicle' },
    { id: 'item-list', label: 'Items', to: '/items', group: 'Master Data', icon: 'item' },
    { id: 'item-create', label: 'Create Item', to: '/items/create', group: 'Master Data', icon: 'item' },
    { id: 'uom-convert', label: 'UOM Converter', to: '/uom-convert', group: 'Master Data', icon: 'list' },
    { id: 'inventory-stock-card', label: 'Stock Card', to: '/inventory/stock-card', group: 'Inventory', icon: 'list' },
    { id: 'purchase-orders', label: 'Purchase Orders', to: '/purchase/orders', group: 'Purchases', icon: 'purchase' },
    { id: 'purchase-create', label: 'Create Purchase Order', to: '/purchase/orders/create', group: 'Purchases', icon: 'purchase' },
    { id: 'purchase-invoices', label: 'Supplier Invoices', to: '/purchase/invoices', group: 'Purchases', icon: 'invoice' },
    { id: 'vouchers', label: 'Vouchers', to: '/vouchers', group: 'Finance', icon: 'voucher' },
    { id: 'rental-billing', label: 'Rental Invoices & Bills', to: '/vehicle-rental/billing', group: 'Vehicle Rental', icon: 'vehicle' },
    { id: 'rental-reservations', label: 'Rental Reservations', to: '/vehicle-rental/reservations', group: 'Vehicle Rental', icon: 'vehicle' },
    { id: 'hr-employees', label: 'Employees', to: '/hr/employees', group: 'Human Resources', icon: 'users' },
    { id: 'settings-config', label: 'System Configuration', to: '/settings', group: 'Administration', icon: 'settings' },
    { id: 'settings-quick-access', label: 'Quick Access Settings', to: '/settings/quick-access', group: 'Administration', icon: 'settings' },
    { id: 'reference-data', label: 'Reference Data', to: '/reference-data', group: 'Administration', icon: 'settings' },
    { id: 'audit-logs', label: 'Audit Logs', to: '/administration/audit-logs', group: 'Administration', icon: 'list' },
];

export function stripQuery(path: string): string {
    const queryIndex = path.indexOf('?');
    return queryIndex === -1 ? path : path.slice(0, queryIndex);
}

export function normalizePath(path: string): string {
    const trimmed = path.trim();
    if (!trimmed) return '/';
    const withLeading = trimmed.startsWith('/') ? trimmed : `/${trimmed}`;
    const queryIdx = withLeading.indexOf('?');
    const hashIdx = withLeading.indexOf('#');
    const cutIdx = queryIdx !== -1 && hashIdx !== -1
        ? Math.min(queryIdx, hashIdx)
        : queryIdx !== -1
            ? queryIdx
            : hashIdx;

    const pathname = cutIdx !== -1 ? withLeading.slice(0, cutIdx) : withLeading;
    const remainder = cutIdx !== -1 ? withLeading.slice(cutIdx) : '';
    const cleanPathname = pathname.length > 1 ? pathname.replace(/\/+$/, '') : pathname;
    return `${cleanPathname || '/'}${remainder}`;
}

export function getStoredQuickAccessItems(): QuickAccessItemConfig[] {
    if (typeof window === 'undefined' || !window.localStorage) {
        return [...DEFAULT_QUICK_ACCESS_ITEMS];
    }

    try {
        const raw = window.localStorage.getItem(QUICK_ACCESS_STORAGE_KEY);
        if (raw === null) {
            return [...DEFAULT_QUICK_ACCESS_ITEMS];
        }

        const parsed = JSON.parse(raw);
        if (!Array.isArray(parsed)) {
            return [...DEFAULT_QUICK_ACCESS_ITEMS];
        }

        // An empty array explicitly saved by user indicates an intentional empty list
        if (parsed.length === 0) {
            return [];
        }

        const validItems = parsed
            .filter((item): item is QuickAccessItemConfig => Boolean(
                item
                && typeof item.id === 'string'
                && typeof item.label === 'string'
                && typeof item.to === 'string'
                && typeof item.group === 'string'
            ))
            .map((item, index) => ({
                id: item.id,
                label: item.label.trim(),
                to: normalizePath(item.to),
                group: item.group.trim() || 'General',
                icon: (QUICK_ACCESS_ICONS.includes(item.icon) ? item.icon : 'list') as NavigationIconName,
                enabled: typeof item.enabled === 'boolean' ? item.enabled : true,
                order: typeof item.order === 'number' ? item.order : index + 1,
            }))
            .sort((a, b) => a.order - b.order);

        // If parsed was non-empty array but had no valid items (corrupt objects), fallback to defaults
        return validItems.length > 0 ? validItems : [...DEFAULT_QUICK_ACCESS_ITEMS];
    } catch {
        return [...DEFAULT_QUICK_ACCESS_ITEMS];
    }
}

export function saveQuickAccessItems(items: QuickAccessItemConfig[]): QuickAccessItemConfig[] {
    const sorted = items.map((item, index) => ({
        ...item,
        order: index + 1,
    }));

    if (typeof window !== 'undefined' && window.localStorage) {
        try {
            window.localStorage.setItem(QUICK_ACCESS_STORAGE_KEY, JSON.stringify(sorted));
            window.dispatchEvent(new CustomEvent(QUICK_ACCESS_UPDATE_EVENT, { detail: sorted }));
        } catch (error) {
            console.error('Failed to save Quick Access items to localStorage', error);
        }
    }

    return sorted;
}

export function resetQuickAccessItems(): QuickAccessItemConfig[] {
    const defaults = [...DEFAULT_QUICK_ACCESS_ITEMS];
    saveQuickAccessItems(defaults);
    return defaults;
}

export function useQuickAccessConfig() {
    const [items, setItems] = useState<QuickAccessItemConfig[]>(() => getStoredQuickAccessItems());

    useEffect(() => {
        const handleUpdate = () => {
            setItems(getStoredQuickAccessItems());
        };

        window.addEventListener(QUICK_ACCESS_UPDATE_EVENT, handleUpdate);
        window.addEventListener('storage', handleUpdate);
        return () => {
            window.removeEventListener(QUICK_ACCESS_UPDATE_EVENT, handleUpdate);
            window.removeEventListener('storage', handleUpdate);
        };
    }, []);

    const updateItems = (nextItems: QuickAccessItemConfig[]) => {
        const saved = saveQuickAccessItems(nextItems);
        setItems(saved);
    };

    const resetToDefaults = () => {
        const defaults = resetQuickAccessItems();
        setItems(defaults);
    };

    return {
        items,
        updateItems,
        resetToDefaults,
    };
}
