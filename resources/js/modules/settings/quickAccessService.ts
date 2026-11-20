import { useEffect, useState, useCallback } from 'react';
import type { NavigationIconName, NavigationSection } from '@/app/navigation/navigationTypes';
import { tenantWorkspaceNavigationSections } from '@/app/navigation/tenantWorkspaceNavigation';
import { findNavigationMatch } from '@/app/navigation/navigationUtils';
import { useAuth } from '@/modules/auth/AuthProvider';
import {
    fetchQuickAccessSettings,
    saveQuickAccessSettings,
    resetQuickAccessSettings,
} from './quickAccessApi';

export interface QuickAccessItemConfig {
    id: string;
    label: string;
    to: string;
    group: string;
    icon: NavigationIconName;
    enabled: boolean;
    order: number;
}

export interface SystemPresetItem {
    id: string;
    label: string;
    to: string;
    group: string;
    icon: NavigationIconName;
}

export const QUICK_ACCESS_STORAGE_KEY = 'autoerp.quick_access_items';
export const QUICK_ACCESS_ROW_VERSION_KEY = 'autoerp.quick_access_row_version';
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

/**
 * Centrally and dynamically extracts available actions and routes from registered workspace navigation sections.
 * Any new module or route added to tenantWorkspaceNavigationSections is automatically available for Quick Access.
 */
export function getAvailablePresets(sections?: readonly NavigationSection[]): SystemPresetItem[] {
    const sourceSections = sections ?? tenantWorkspaceNavigationSections;
    const presets: SystemPresetItem[] = [];
    const seenPaths = new Set<string>();

    for (const section of sourceSections) {
        for (const item of section.items) {
            if (item.type === 'link') {
                const normalized = normalizePath(item.to);
                if (!seenPaths.has(normalized)) {
                    seenPaths.add(normalized);
                    presets.push({
                        id: item.id,
                        label: item.label,
                        to: item.to,
                        group: section.label || 'General',
                        icon: item.icon || 'list',
                    });
                }
            } else if (item.type === 'module') {
                for (const child of item.children) {
                    const normalized = normalizePath(child.to);
                    if (!seenPaths.has(normalized)) {
                        seenPaths.add(normalized);
                        presets.push({
                            id: child.id,
                            label: child.label,
                            to: child.to,
                            group: item.label,
                            icon: child.icon || item.icon || 'list',
                        });
                    }
                }
            }
        }
    }

    return presets;
}

export const SYSTEM_PRESET_ITEMS: readonly SystemPresetItem[] = getAvailablePresets();

/**
 * Single source of truth for evaluating whether a configured Quick Access shortcut
 * is accessible given the user's active, permission-filtered workspace navigation sections.
 */
export function isQuickAccessItemAccessible(
    item: QuickAccessItemConfig,
    sections: readonly NavigationSection[],
    allSystemSections: readonly NavigationSection[] = tenantWorkspaceNavigationSections,
): boolean {
    const permittedLinks = sections.flatMap((section) =>
        section.items.flatMap((i) => (i.type === 'link' ? [i] : i.children))
    );

    // 1. Direct ID match against permitted navigation link items
    if (permittedLinks.some((link) => link.id === item.id)) {
        return true;
    }

    const itemPath = normalizePath(item.to);
    const itemPathOnly = stripQuery(itemPath);

    // 2. Exact route match against permitted link routes or candidates
    for (const link of permittedLinks) {
        if (normalizePath(link.to) === itemPath) {
            return true;
        }
        if (link.match) {
            for (const candidate of link.match) {
                if (normalizePath(candidate) === itemPath) {
                    return true;
                }
            }
        }
    }

    // 3. Registered specific link check:
    // If the system defines a registered specific navigation item matching this shortcut's ID
    // or exact target route (e.g. /invoices?view=service or /inventory?tab=adjustments),
    // and that registered link is NOT in permittedLinks, then the user's workspace explicitly lacks access.
    const allRegisteredLinks = allSystemSections.flatMap((section) =>
        section.items.flatMap((i) => (i.type === 'link' ? [i] : i.children))
    );

    const isRegisteredSystemItem = allRegisteredLinks.some(
        (link) => link.id === item.id || normalizePath(link.to) === itemPath
    );

    if (isRegisteredSystemItem) {
        return false;
    }

    // 4. For custom shortcuts or generic query parameters not registered as distinct system items:
    // Fall back to matching the base route or candidate match in permittedLinks.
    for (const link of permittedLinks) {
        if (normalizePath(stripQuery(link.to)) === itemPathOnly) {
            return true;
        }
        if (link.match) {
            for (const candidate of link.match) {
                if (normalizePath(candidate) === itemPathOnly) {
                    return true;
                }
            }
        }
    }

    // 5. Router hierarchy check for nested sub-routes (e.g. /customers/create)
    const queryIdx = item.to.indexOf('?');
    const pathname = queryIdx !== -1 ? item.to.slice(0, queryIdx) : item.to;
    const search = queryIdx !== -1 ? item.to.slice(queryIdx) : '';

    const systemMatch = findNavigationMatch(pathname, search, allSystemSections as NavigationSection[]);
    if (systemMatch) {
        return permittedLinks.some((link) => link.id === systemMatch.item.id);
    }

    return false;
}

export function getQuickAccessStorageKey(
    tenantId?: number | string | null,
    userId?: number | string | null,
): string {
    if (tenantId && userId) {
        return `${QUICK_ACCESS_STORAGE_KEY}.${tenantId}.${userId}`;
    }
    return QUICK_ACCESS_STORAGE_KEY;
}

export function getVersionKeyFromStorageKey(storageKey: string): string {
    return storageKey.replace(QUICK_ACCESS_STORAGE_KEY, QUICK_ACCESS_ROW_VERSION_KEY);
}

export function getQuickAccessVersionKey(
    tenantId?: number | string | null,
    userId?: number | string | null,
): string {
    if (tenantId && userId) {
        return `${QUICK_ACCESS_ROW_VERSION_KEY}.${tenantId}.${userId}`;
    }
    return QUICK_ACCESS_ROW_VERSION_KEY;
}

export function getStoredQuickAccessItems(storageKey: string = QUICK_ACCESS_STORAGE_KEY): QuickAccessItemConfig[] {
    if (typeof window === 'undefined' || !window.localStorage) {
        return [...DEFAULT_QUICK_ACCESS_ITEMS];
    }

    try {
        const raw = window.localStorage.getItem(storageKey);
        if (raw === null) {
            return [...DEFAULT_QUICK_ACCESS_ITEMS];
        }

        const parsed = JSON.parse(raw);
        if (!Array.isArray(parsed)) {
            return [...DEFAULT_QUICK_ACCESS_ITEMS];
        }

        return parseRawItems(parsed);
    } catch {
        return [...DEFAULT_QUICK_ACCESS_ITEMS];
    }
}

export function parseRawItems(parsed: unknown[]): QuickAccessItemConfig[] {
    if (parsed.length === 0) {
        return [];
    }

    const validItems = parsed
        .filter((item): item is QuickAccessItemConfig => Boolean(
            item
            && typeof item === 'object'
            && typeof (item as Record<string, unknown>).id === 'string'
            && typeof (item as Record<string, unknown>).label === 'string'
            && typeof (item as Record<string, unknown>).to === 'string'
            && typeof (item as Record<string, unknown>).group === 'string'
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

    return validItems.length > 0 ? validItems : [...DEFAULT_QUICK_ACCESS_ITEMS];
}

export function getStoredRowVersion(storageKey: string = QUICK_ACCESS_STORAGE_KEY): number | null {
    if (typeof window === 'undefined' || !window.localStorage) {
        return null;
    }
    try {
        const versionKey = getVersionKeyFromStorageKey(storageKey);
        const raw = window.localStorage.getItem(versionKey);
        return raw !== null && !Number.isNaN(Number(raw)) ? Number(raw) : null;
    } catch {
        return null;
    }
}

export function saveQuickAccessItems(
    items: readonly QuickAccessItemConfig[],
    storageKey: string = QUICK_ACCESS_STORAGE_KEY,
    rowVersion?: number | null,
): QuickAccessItemConfig[] {
    const sorted = items.map((item, index) => ({
        ...item,
        order: index + 1,
    }));

    if (typeof window !== 'undefined' && window.localStorage) {
        try {
            window.localStorage.setItem(storageKey, JSON.stringify(sorted));
            const versionKey = getVersionKeyFromStorageKey(storageKey);
            if (rowVersion !== undefined && rowVersion !== null) {
                window.localStorage.setItem(versionKey, String(rowVersion));
            } else if (rowVersion === null) {
                window.localStorage.removeItem(versionKey);
            }
            window.dispatchEvent(new CustomEvent(QUICK_ACCESS_UPDATE_EVENT, { detail: sorted }));
        } catch (error) {
            console.error('Failed to save Quick Access items to localStorage', error);
        }
    }

    return sorted;
}

export function resetQuickAccessItems(storageKey: string = QUICK_ACCESS_STORAGE_KEY): QuickAccessItemConfig[] {
    const defaults = [...DEFAULT_QUICK_ACCESS_ITEMS];
    if (typeof window !== 'undefined' && window.localStorage) {
        try {
            const versionKey = getVersionKeyFromStorageKey(storageKey);
            window.localStorage.removeItem(versionKey);
        } catch {
            // ignore
        }
    }
    saveQuickAccessItems(defaults, storageKey, null);
    return defaults;
}

function useAuthSafe() {
    try {
        return useAuth();
    } catch {
        return null;
    }
}

export function useQuickAccessConfig() {
    const auth = useAuthSafe();
    const tenantId = auth?.tenant?.id ?? null;
    const userId = auth?.user?.id ?? null;
    const storageKey = getQuickAccessStorageKey(tenantId, userId);

    const [items, setItems] = useState<QuickAccessItemConfig[]>(() => getStoredQuickAccessItems(storageKey));
    const [rowVersion, setRowVersion] = useState<number | null>(() => getStoredRowVersion(storageKey));
    const [isDefault, setIsDefault] = useState<boolean>(false);
    const [isLoading, setIsLoading] = useState<boolean>(true);
    const [isSaving, setIsSaving] = useState<boolean>(false);
    const [error, setError] = useState<string | null>(null);

    const refreshFromServer = useCallback(async (signal?: AbortSignal) => {
        setIsLoading(true);
        setError(null);
        try {
            const data = await fetchQuickAccessSettings(signal);
            if (signal?.aborted) return null;
            const serverItems = Array.isArray(data.items) ? data.items : [];
            const parsed = parseRawItems(serverItems);
            saveQuickAccessItems(parsed, storageKey, data.row_version);
            setItems(parsed);
            setRowVersion(data.row_version);
            setIsDefault(Boolean(data.is_default));
            return parsed;
        } catch (err) {
            if (signal?.aborted) return null;
            // Keep cached items if offline or error
            const fallback = getStoredQuickAccessItems(storageKey);
            setItems(fallback);
            return fallback;
        } finally {
            if (!signal?.aborted) {
                setIsLoading(false);
            }
        }
    }, [storageKey]);

    useEffect(() => {
        // Fast initial load from LocalStorage cache
        setItems(getStoredQuickAccessItems(storageKey));
        setRowVersion(getStoredRowVersion(storageKey));

        // Background server fetch to sync database single source of truth
        const abortController = new AbortController();
        refreshFromServer(abortController.signal).catch(() => {});

        const handleLocalUpdate = () => {
            setItems(getStoredQuickAccessItems(storageKey));
            setRowVersion(getStoredRowVersion(storageKey));
        };

        const handleStorageEvent = (event: StorageEvent) => {
            const versionKey = getVersionKeyFromStorageKey(storageKey);
            if (event.key && event.key !== storageKey && event.key !== versionKey) {
                return;
            }
            handleLocalUpdate();
        };

        window.addEventListener(QUICK_ACCESS_UPDATE_EVENT, handleLocalUpdate);
        window.addEventListener('storage', handleStorageEvent);

        return () => {
            abortController.abort();
            window.removeEventListener(QUICK_ACCESS_UPDATE_EVENT, handleLocalUpdate);
            window.removeEventListener('storage', handleStorageEvent);
        };
    }, [storageKey, refreshFromServer]);

    const updateItems = async (nextItems: QuickAccessItemConfig[]): Promise<{ success: boolean; conflict?: boolean; message?: string }> => {
        // Optimistic update: cache immediately in LocalStorage and fire event so header dropdown updates instantly
        const saved = saveQuickAccessItems(nextItems, storageKey, rowVersion);
        setItems(saved);
        setIsSaving(true);
        setError(null);

        try {
            const response = await saveQuickAccessSettings(saved, rowVersion);
            setRowVersion(response.row_version);
            setIsDefault(response.is_default);
            // Confirm cache with server row version
            const updated = saveQuickAccessItems(response.items, storageKey, response.row_version);
            setItems(updated);
            return { success: true };
        } catch (err: unknown) {
            const apiErr = err as { status?: number; message?: string };
            if (apiErr.status === 409) {
                // Concurrency conflict: reload latest from database
                await refreshFromServer();
                const conflictMsg = 'Quick Access settings were updated in another session. Refreshed with latest settings.';
                setError(conflictMsg);
                return { success: false, conflict: true, message: conflictMsg };
            }
            const errorMsg = apiErr.message || 'Failed to save settings to server.';
            setError(errorMsg);
            return { success: false, conflict: false, message: errorMsg };
        } finally {
            setIsSaving(false);
        }
    };

    const resetToDefaults = async (): Promise<{ success: boolean; message?: string }> => {
        const defaults = resetQuickAccessItems(storageKey);
        setItems(defaults);
        setRowVersion(null);
        setIsDefault(true);
        setIsSaving(true);
        setError(null);

        try {
            await resetQuickAccessSettings();
            return { success: true };
        } catch (err: unknown) {
            const apiErr = err as { message?: string };
            const errorMsg = apiErr.message || 'Failed to reset settings on server.';
            setError(errorMsg);
            return { success: false, message: errorMsg };
        } finally {
            setIsSaving(false);
        }
    };

    return {
        items,
        rowVersion,
        isDefault,
        isLoading,
        isSaving,
        error,
        updateItems,
        resetToDefaults,
        refreshFromServer,
    };
}
