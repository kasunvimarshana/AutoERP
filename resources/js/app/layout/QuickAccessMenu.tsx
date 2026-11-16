import { useEffect, useRef, useState } from 'react';
import { Link, useLocation } from 'react-router-dom';
import { NavigationIcon } from '@/app/navigation/NavigationIcon';
import type { NavigationIconName, NavigationLinkItem, NavigationSection } from '@/app/navigation/navigationTypes';

const QUICK_ACCESS_GROUPS: { label: string; links: { id: string; icon: NavigationIconName }[] }[] = [
    {
        label: 'Vehicle Service',
        links: [
            { id: 'create-service-job', icon: 'service' },
            { id: 'service-jobs', icon: 'list' },
        ],
    },
    {
        label: 'Selling',
        links: [
            { id: 'selling-create', icon: 'purchase' },
            { id: 'selling-list', icon: 'list' },
        ],
    },
];

export function QuickAccessMenu({ sections, activeItemId }: { sections: NavigationSection[]; activeItemId: string | null }) {
    const [openLocation, setOpenLocation] = useState<string | null>(null);
    const menuRef = useRef<HTMLDivElement>(null);
    const triggerPointerType = useRef<string | null>(null);
    const location = useLocation();
    const locationKey = `${location.pathname}${location.search}`;
    const open = openLocation === locationKey;
    const visibleLinks = new Map<string, NavigationLinkItem>(sections.flatMap((section) => section.items.flatMap((item) => (
        item.type === 'link' ? [item] : item.children
    ))).map((item) => [item.id, item]));
    const groups = QUICK_ACCESS_GROUPS.map((group) => ({
        label: group.label,
        links: group.links.flatMap(({ id, icon }) => {
            const item = visibleLinks.get(id);
            return item ? [{ ...item, icon }] : [];
        }),
    })).filter((group) => group.links.length > 0);

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
                        <p className="px-2 pb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Quick access</p>
                        {groups.map((group, index) => (
                            <div key={group.label} className={index > 0 ? 'mt-3 border-t border-slate-200 pt-3' : ''}>
                                <p className="px-2 pb-1 text-sm font-bold text-slate-900">{group.label}</p>
                                {group.links.map((item) => (
                                    <Link
                                        key={item.id}
                                        to={item.to}
                                        onClick={() => setOpenLocation(null)}
                                        aria-current={activeItemId === item.id ? 'page' : undefined}
                                        className={`flex min-h-10 items-center gap-3 rounded-lg px-2 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-blue-500 ${activeItemId === item.id ? 'bg-blue-50 text-blue-700' : 'text-slate-800 hover:bg-blue-50 hover:text-blue-700'}`}
                                    >
                                        <NavigationIcon name={item.icon} className="h-5 w-5 shrink-0 text-blue-600" />
                                        {item.label}
                                    </Link>
                                ))}
                            </div>
                        ))}
                    </nav>
                </div>
            )}
        </div>
    );
}
