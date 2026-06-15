import { useRef, type KeyboardEvent } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import type { NavigationItem, NavigationLocation, NavigationSection } from './navigationTypes';
import { itemIsActive } from './navigationUtils';

export function NavigationMenu({
    sections,
    location,
    expandedIds,
    compact = false,
    idPrefix = 'desktop',
    onToggle,
    onNavigate,
}: {
    sections: NavigationSection[];
    location: NavigationLocation;
    expandedIds: readonly string[];
    compact?: boolean;
    idPrefix?: string;
    onToggle: (id: string) => void;
    onNavigate: () => void;
}) {
    const navRef = useRef<HTMLElement>(null);

    const handleKeyDown = (event: KeyboardEvent<HTMLElement>) => {
        if (!['ArrowDown', 'ArrowUp', 'Home', 'End'].includes(event.key)) return;
        const controls = Array.from(navRef.current?.querySelectorAll<HTMLElement>('[data-nav-control="true"]') ?? [])
            .filter((control) => !control.closest('[hidden]'));
        if (controls.length === 0) return;
        const currentIndex = controls.indexOf(document.activeElement as HTMLElement);
        const nextIndex = event.key === 'Home'
            ? 0
            : event.key === 'End'
                ? controls.length - 1
                : event.key === 'ArrowDown'
                    ? (currentIndex + 1 + controls.length) % controls.length
                    : (currentIndex - 1 + controls.length) % controls.length;
        event.preventDefault();
        controls[nextIndex]?.focus();
    };

    return (
        <nav
            ref={navRef}
            className={`flex-1 overflow-y-auto overscroll-contain py-3 ${compact ? 'px-2' : 'px-3'}`}
            aria-label="Main navigation"
            onKeyDown={handleKeyDown}
        >
            {sections.map((section) => (
                <section key={section.id} className={compact ? 'mb-2' : 'mb-5'}>
                    {!compact && section.label && (
                        <h2 className="px-3 pb-2 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                            {section.label}
                        </h2>
                    )}
                    <div className="space-y-1">
                        {section.items.map((item) => (
                            <NavigationEntry
                                key={item.id}
                                item={item}
                                location={location}
                                expanded={expandedIds.includes(item.id)}
                                compact={compact}
                                idPrefix={idPrefix}
                                onToggle={onToggle}
                                onNavigate={onNavigate}
                            />
                        ))}
                    </div>
                </section>
            ))}
        </nav>
    );
}

function NavigationEntry({
    item,
    location,
    expanded,
    compact,
    idPrefix,
    onToggle,
    onNavigate,
}: {
    item: NavigationItem;
    location: NavigationLocation;
    expanded: boolean;
    compact: boolean;
    idPrefix: string;
    onToggle: (id: string) => void;
    onNavigate: () => void;
}) {
    const navigate = useNavigate();
    const active = itemIsActive(item, location);
    const baseClass = 'group relative flex min-h-10 w-full items-center rounded-md text-sm font-medium transition motion-reduce:transition-none focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-400';
    const stateClass = active
        ? 'bg-sky-600 text-white shadow-sm'
        : 'text-slate-300 hover:bg-slate-800 hover:text-white';
    const hasChildren = Boolean(item.children?.length);

    if (compact) {
        return (
            <button
                type="button"
                data-nav-control="true"
                aria-label={item.label}
                title={item.label}
                className={`${baseClass} ${stateClass} justify-center px-2`}
                onClick={() => {
                    if (item.route) navigate(item.route);
                    onNavigate();
                }}
            >
                <ModuleIcon item={item} />
                <span className="pointer-events-none absolute left-full z-50 ml-3 hidden whitespace-nowrap rounded bg-slate-900 px-2 py-1 text-xs text-white shadow-lg group-hover:block group-focus-visible:block">
                    {item.label}
                </span>
            </button>
        );
    }

    if (!hasChildren && item.route) {
        return (
            <Link
                to={item.route}
                data-nav-control="true"
                aria-current={active ? 'page' : undefined}
                className={`${baseClass} ${stateClass} gap-3 px-3`}
                onClick={onNavigate}
            >
                {item.icon && <ModuleIcon item={item} />}
                <span className="min-w-0 flex-1 truncate">{item.label}</span>
                {item.badgeKey && <span data-badge-key={item.badgeKey} />}
            </Link>
        );
    }

    return (
        <div>
            <button
                type="button"
                data-nav-control="true"
                className={`${baseClass} ${stateClass} gap-3 px-3`}
                aria-expanded={expanded}
                aria-current={active ? 'page' : undefined}
                aria-controls={`${idPrefix}-navigation-${item.id}`}
                onClick={() => onToggle(item.id)}
            >
                <ModuleIcon item={item} />
                <span className="min-w-0 flex-1 truncate text-left">{item.label}</span>
                <span aria-hidden="true" className={`text-xs transition-transform motion-reduce:transition-none ${expanded ? 'rotate-90' : ''}`}>&gt;</span>
            </button>
            <div id={`${idPrefix}-navigation-${item.id}`} hidden={!expanded} className="ml-5 mt-1 space-y-1 border-l border-slate-800 pl-3">
                {item.children?.map((child) => {
                    const childActive = itemIsActive(child, location);
                    return child.route ? (
                        <Link
                            key={child.id}
                            to={child.route}
                            data-nav-control="true"
                            aria-current={childActive ? 'page' : undefined}
                            onClick={onNavigate}
                            className={`flex min-h-9 items-center rounded-md px-3 py-2 text-sm transition motion-reduce:transition-none focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-400 ${
                                childActive
                                    ? 'bg-slate-800 font-semibold text-sky-300'
                                    : 'text-slate-400 hover:bg-slate-800 hover:text-white'
                            }`}
                        >
                            <span className="min-w-0 flex-1">{child.label}</span>
                            {child.badgeKey && <span data-badge-key={child.badgeKey} />}
                        </Link>
                    ) : null;
                })}
            </div>
        </div>
    );
}

function ModuleIcon({ item }: { item: NavigationItem }) {
    return (
        <span
            aria-hidden="true"
            className="flex h-7 w-7 shrink-0 items-center justify-center rounded border border-current/25 bg-slate-900/20 text-[10px] font-bold"
        >
            {item.icon ?? item.label.slice(0, 2).toUpperCase()}
        </span>
    );
}
