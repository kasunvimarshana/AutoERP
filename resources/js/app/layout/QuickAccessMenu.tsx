import { useEffect, useRef, useState } from 'react';
import { Link, useLocation } from 'react-router-dom';
import { NavigationIcon } from '@/app/navigation/NavigationIcon';
import type { NavigationIconName, NavigationLinkItem, NavigationSection } from '@/app/navigation/navigationTypes';
import { findNavigationMatch } from '@/app/navigation/navigationUtils';
import {
    getStoredQuickAccessItems,
    stripQuery,
    QUICK_ACCESS_UPDATE_EVENT,
    type QuickAccessItemConfig,
} from '@/modules/settings/quickAccessService';

export function QuickAccessMenu({ sections, activeItemId }: { sections: NavigationSection[]; activeItemId: string | null }) {
    const [openLocation, setOpenLocation] = useState<string | null>(null);
    const menuRef = useRef<HTMLDivElement>(null);
    const triggerPointerType = useRef<string | null>(null);
    const location = useLocation();
    const locationKey = `${location.pathname}${location.search}`;
    const open = openLocation === locationKey;

    const [configuredItems, setConfiguredItems] = useState<QuickAccessItemConfig[]>(() => getStoredQuickAccessItems());

    useEffect(() => {
        const handleUpdate = () => {
            setConfiguredItems(getStoredQuickAccessItems());
        };

        window.addEventListener(QUICK_ACCESS_UPDATE_EVENT, handleUpdate);
        window.addEventListener('storage', handleUpdate);
        return () => {
            window.removeEventListener(QUICK_ACCESS_UPDATE_EVENT, handleUpdate);
            window.removeEventListener('storage', handleUpdate);
        };
    }, []);

    const allLinks = sections.flatMap((section) =>
        section.items.flatMap((item) => (item.type === 'link' ? [item] : item.children))
    );
    const visibleLinksById = new Map<string, NavigationLinkItem>(allLinks.map((item) => [item.id, item]));
    const visibleLinksByPath = new Map<string, NavigationLinkItem>();
    for (const link of allLinks) {
        visibleLinksByPath.set(stripQuery(link.to), link);
        if (link.match) {
            for (const candidate of link.match) {
                visibleLinksByPath.set(stripQuery(candidate), link);
            }
        }
    }

    // Group active configured items that pass navigation permissions
    const groups: Array<{ label: string; links: Array<{ id: string; label: string; to: string; icon: NavigationIconName }> }> = [];
    const groupMap = new Map<string, typeof groups[number]>();

    for (const item of configuredItems) {
        if (!item.enabled) continue;

        // Check if accessible in the permitted sections (by ID, by direct path/match, or navigation hierarchy)
        const itemPath = stripQuery(item.to);
        const isAccessible = visibleLinksById.has(item.id)
            || visibleLinksByPath.has(itemPath)
            || Boolean(findNavigationMatch(itemPath, '', sections));
        if (!isAccessible) continue;

        let group = groupMap.get(item.group);
        if (!group) {
            group = { label: item.group, links: [] };
            groupMap.set(item.group, group);
            groups.push(group);
        }

        group.links.push({
            id: item.id,
            label: item.label,
            to: item.to,
            icon: item.icon,
        });
    }

    useEffect(() => {
        if (!open) return undefined;

        const closeOutside = (event: PointerEvent) => {
            if (!menuRef.current?.contains(event.target as Node)) setOpenLocation(null);
        };
        const closeOnEscape = (event: KeyboardEvent) => {
            if (event.key === 'Escape') {
                setOpenLocation(null);
                menuRef.current?.querySelector('button')?.focus();
            }
        };

        document.addEventListener('pointerdown', closeOutside);
        document.addEventListener('keydown', closeOnEscape);
        return () => {
            document.removeEventListener('pointerdown', closeOutside);
            document.removeEventListener('keydown', closeOnEscape);
        };
    }, [open]);

    if (groups.length === 0) return null;

    return (
        <div
            ref={menuRef}
            className="relative shrink-0"
            onPointerEnter={(event) => { if (event.pointerType === 'mouse') setOpenLocation(locationKey); }}
            onPointerLeave={(event) => { if (event.pointerType === 'mouse') setOpenLocation(null); }}
        >
            <button
                type="button"
                className="flex h-10 items-center gap-2 rounded-lg border border-blue-600 px-2.5 text-sm font-semibold text-blue-700 hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-500 sm:px-3"
                aria-label="Quick Access"
                aria-expanded={open}
                aria-controls="quick-access-menu"
                onPointerDown={(event) => { triggerPointerType.current = event.pointerType; }}
                onClick={() => {
                    const pointerType = triggerPointerType.current;
                    setOpenLocation((current) => pointerType === 'mouse'
                        ? locationKey
                        : current === locationKey ? null : locationKey);
                    triggerPointerType.current = null;
                }}
            >
                <svg viewBox="0 0 24 24" className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                    <path d="m13 2-8 11h6l-1 9 9-12h-6l1-8Z" />
                </svg>
                <span className="hidden sm:inline">Quick Access</span>
                <svg viewBox="0 0 24 24" className={`h-4 w-4 transition-transform ${open ? 'rotate-180' : ''}`} fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                    <path d="m6 9 6 6 6-6" />
                </svg>
            </button>
            {open && (
                <div className="absolute right-0 top-full z-30 w-80 max-w-[calc(100vw-2rem)] pt-2">
                    <nav id="quick-access-menu" aria-label="Quick access" className="rounded-xl border border-slate-200 bg-white p-3 shadow-xl">
                        <div className="flex items-center justify-between px-2 pb-2">
                            <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">Quick access</p>
                            <Link
                                to="/settings/quick-access"
                                onClick={() => setOpenLocation(null)}
                                className="text-xs font-medium text-blue-600 hover:text-blue-800 hover:underline"
                                title="Manage Quick Access items"
                            >
                                Customize
                            </Link>
                        </div>
                        {groups.map((group, index) => (
                            <div key={group.label} className={index > 0 ? 'mt-3 border-t border-slate-200 pt-3' : ''}>
                                <p className="px-2 pb-1 text-sm font-bold text-slate-900">{group.label}</p>
                                {group.links.map((item) => {
                                    const isCurrent = activeItemId === item.id
                                        || (item.to === location.pathname && location.pathname !== '/')
                                        || item.to === locationKey
                                        || (stripQuery(item.to) === location.pathname && location.pathname !== '/');
                                    return (
                                        <Link
                                            key={item.id}
                                            to={item.to}
                                            onClick={() => setOpenLocation(null)}
                                            aria-current={isCurrent ? 'page' : undefined}
                                            className={`flex min-h-10 items-center gap-3 rounded-lg px-2 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-blue-500 ${isCurrent ? 'bg-blue-50 text-blue-700' : 'text-slate-800 hover:bg-blue-50 hover:text-blue-700'}`}
                                        >
                                            <NavigationIcon name={item.icon} className="h-5 w-5 shrink-0 text-blue-600" />
                                            {item.label}
                                        </Link>
                                    );
                                })}
                            </div>
                        ))}
                    </nav>
                </div>
            )}
        </div>
    );
}
