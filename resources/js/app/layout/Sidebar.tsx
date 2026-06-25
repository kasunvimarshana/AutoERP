import { useRef } from 'react';
import { Link } from 'react-router-dom';
import { NavigationIcon } from '@/app/navigation/NavigationIcon';
import type { NavigationLinkItem, NavigationSection } from '@/app/navigation/navigationTypes';
import type { AuthOrganizationUnit, AuthTenant, AuthUser } from '@/modules/auth/authTypes';
import { useDialogAccessibility } from '@/shared/hooks/useDialogAccessibility';

export function Sidebar({
    sections,
    activeItemId,
    activeParentId,
    expandedModuleId,
    mobileOpen,
    collapsed,
    user,
    tenant,
    organizationUnit,
    homePath,
    workspaceLabel,
    onCloseMobile,
    onExpandDesktop,
    onToggleModule,
}: {
    sections: NavigationSection[];
    activeItemId: string | null;
    activeParentId: string | null;
    expandedModuleId: string | null;
    mobileOpen: boolean;
    collapsed: boolean;
    user: AuthUser | null;
    tenant: AuthTenant | null;
    organizationUnit: AuthOrganizationUnit | null;
    homePath: string;
    workspaceLabel: string;
    onCloseMobile: () => void;
    onExpandDesktop: () => void;
    onToggleModule: (moduleId: string) => void;
}) {
    const sidebarWidth = collapsed ? 'lg:w-20' : 'lg:w-72';
    const sidebarRef = useRef<HTMLElement>(null);
    useDialogAccessibility(mobileOpen, sidebarRef, onCloseMobile);

    return (
        <>
            {mobileOpen && (
                <button
                    type="button"
                    className="fixed inset-0 z-30 bg-slate-950/55 lg:hidden"
                    onClick={onCloseMobile}
                    aria-label="Close navigation"
                />
            )}
            <aside
                ref={sidebarRef}
                id="app-sidebar"
                tabIndex={mobileOpen ? -1 : undefined}
                className={`app-sidebar fixed inset-y-0 left-0 z-40 flex w-72 flex-col border-r border-slate-800 bg-slate-950 text-white transition-transform ${sidebarWidth} ${mobileOpen ? 'translate-x-0' : '-translate-x-full'} lg:translate-x-0`}
                aria-label="Application sidebar"
            >
                <div className="flex h-16 shrink-0 items-center gap-3 border-b border-slate-800 px-4">
                    <Link
                        to={homePath}
                        onClick={onCloseMobile}
                        className="flex min-w-0 flex-1 items-center gap-3 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-400"
                        title={collapsed ? 'AutoERP' : undefined}
                    >
                        <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-600 text-white shadow-lg shadow-blue-950/30">
                            <NavigationIcon name="vehicle" className="h-6 w-6" />
                        </span>
                        <span className={`min-w-0 ${collapsed ? 'lg:hidden' : ''}`}>
                            <span className="block truncate text-lg font-bold tracking-tight">AUTO<span className="text-blue-400">ERP</span></span>
                            <span className="block truncate text-[11px] font-medium uppercase tracking-[0.18em] text-slate-400">{workspaceLabel}</span>
                        </span>
                    </Link>
                    <button
                        type="button"
                        className="inline-flex h-9 w-9 items-center justify-center rounded-lg text-slate-300 hover:bg-slate-800 hover:text-white focus:outline-none focus:ring-2 focus:ring-blue-400 lg:hidden"
                        onClick={onCloseMobile}
                        aria-label="Close navigation"
                    >
                        <CloseIcon />
                    </button>
                </div>

                <nav className="min-h-0 flex-1 overflow-y-auto px-3 py-4" aria-label="Main navigation">
                    {sections.map((section) => (
                        <section key={section.id} className="mb-5 last:mb-0">
                            {section.label && (
                                <h2 className={`mb-2 px-3 text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500 ${collapsed ? 'lg:text-center lg:text-[0]' : ''}`}>
                                    <span className={collapsed ? 'lg:hidden' : ''}>{section.label}</span>
                                    <span className={`hidden h-px bg-slate-800 ${collapsed ? 'lg:block' : ''}`} aria-hidden="true" />
                                </h2>
                            )}
                            <div className="space-y-1">
                                {section.items.map((item) => {
                                    if (item.type === 'link') {
                                        return (
                                            <SidebarLink
                                                key={item.id}
                                                item={item}
                                                active={activeItemId === item.id}
                                                collapsed={collapsed}
                                                onNavigate={onCloseMobile}
                                            />
                                        );
                                    }

                                    const expanded = expandedModuleId === item.id;
                                    const active = activeParentId === item.id;
                                    return (
                                        <div key={item.id}>
                                            <button
                                                type="button"
                                                className={`flex min-h-11 w-full items-center gap-3 rounded-lg px-3 text-left text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-blue-400 ${active ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-900 hover:text-white'}`}
                                                onClick={() => {
                                                    if (collapsed) onExpandDesktop();
                                                    onToggleModule(item.id);
                                                }}
                                                aria-expanded={expanded}
                                                aria-controls={`sidebar-module-${item.id}`}
                                                title={collapsed ? item.label : undefined}
                                            >
                                                <NavigationIcon name={item.icon} className={`h-5 w-5 shrink-0 ${active ? 'text-blue-400' : 'text-slate-400'}`} />
                                                <span className={`min-w-0 flex-1 truncate ${collapsed ? 'lg:hidden' : ''}`}>{item.label}</span>
                                                <ChevronIcon className={`h-4 w-4 shrink-0 ${expanded ? 'rotate-180' : ''} ${collapsed ? 'lg:hidden' : ''}`} />
                                            </button>
                                            {expanded && (
                                                <div id={`sidebar-module-${item.id}`} className={`mt-1 space-y-1 pl-4 ${collapsed ? 'lg:hidden' : ''}`}>
                                                    {item.children.map((child) => (
                                                        <SidebarLink
                                                            key={child.id}
                                                            item={child}
                                                            active={activeItemId === child.id}
                                                            child
                                                            onNavigate={onCloseMobile}
                                                        />
                                                    ))}
                                                </div>
                                            )}
                                        </div>
                                    );
                                })}
                            </div>
                        </section>
                    ))}
                </nav>

                <div className="shrink-0 border-t border-slate-800 p-3">
                    <div className={`flex items-center gap-3 rounded-xl bg-slate-900 px-3 py-3 ${collapsed ? 'lg:justify-center lg:px-2' : ''}`}>
                        <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-blue-600 text-xs font-bold text-white">
                            {initials(user)}
                        </span>
                        <div className={`min-w-0 ${collapsed ? 'lg:hidden' : ''}`}>
                            <p className="truncate text-sm font-semibold text-white">{user?.name ?? user?.email ?? 'AutoERP user'}</p>
                            <p className="truncate text-xs text-slate-400">{organizationUnit?.name ?? tenant?.name ?? 'Current workspace'}</p>
                        </div>
                    </div>
                </div>
            </aside>
        </>
    );
}

function SidebarLink({
    item,
    active,
    collapsed = false,
    child = false,
    onNavigate,
}: {
    item: NavigationLinkItem;
    active: boolean;
    collapsed?: boolean;
    child?: boolean;
    onNavigate: () => void;
}) {
    return (
        <Link
            to={item.to}
            onClick={onNavigate}
            className={`group flex min-h-10 items-center gap-3 rounded-lg px-3 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-blue-400 ${active ? 'bg-blue-600 text-white shadow-sm shadow-blue-950/30' : 'text-slate-300 hover:bg-slate-900 hover:text-white'} ${child ? 'relative pl-7' : ''}`}
            aria-current={active ? 'page' : undefined}
            title={collapsed ? item.label : undefined}
        >
            {child ? (
                <span className={`h-1.5 w-1.5 shrink-0 rounded-full ${active ? 'bg-white' : 'bg-slate-500 group-hover:bg-slate-300'}`} aria-hidden="true" />
            ) : item.icon ? (
                <NavigationIcon name={item.icon} className={`h-5 w-5 shrink-0 ${active ? 'text-white' : 'text-slate-400 group-hover:text-white'}`} />
            ) : (
                <NavigationIcon name="list" className="h-5 w-5 shrink-0 text-slate-400 group-hover:text-white" />
            )}
            <span className={`truncate ${collapsed ? 'lg:hidden' : ''}`}>{item.label}</span>
        </Link>
    );
}

function initials(user: AuthUser | null): string {
    const value = user?.name ?? user?.email ?? 'AU';
    return value.split(/\s+/).filter(Boolean).slice(0, 2).map((part) => part[0]?.toUpperCase()).join('');
}

function ChevronIcon({ className }: { className: string }) {
    return (
        <svg viewBox="0 0 24 24" className={className} fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
            <path d="m6 9 6 6 6-6" />
        </svg>
    );
}

function CloseIcon() {
    return (
        <svg viewBox="0 0 24 24" className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" aria-hidden="true">
            <path d="M6 6l12 12M18 6 6 18" />
        </svg>
    );
}
