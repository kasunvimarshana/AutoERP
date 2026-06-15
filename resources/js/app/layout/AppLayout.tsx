import { useCallback, useEffect, useMemo, useState } from 'react';
import { Link, Outlet, useLocation, useNavigate } from 'react-router-dom';
import { useAuth } from '@/modules/auth/AuthProvider';
import { Button } from '@/shared/components/Button';
import { navigationActions, navigationSections } from '../navigation/navigationConfig';
import { NavigationMenu } from '../navigation/NavigationMenu';
import { NavigationPalette } from '../navigation/NavigationPalette';
import { activeNavigationTrail, filterNavigation, initialExpandedIds, resolveEnabledModules } from '../navigation/navigationUtils';
import { useFocusTrap } from '../navigation/useFocusTrap';

const EXPANDED_KEY = 'autoerp.navigation.expanded';
const COMPACT_KEY = 'autoerp.navigation.compact';

export function AppLayout() {
    const [sidebarOpen, setSidebarOpen] = useState(false);
    const [paletteOpen, setPaletteOpen] = useState(false);
    const [compact, setCompact] = useState(() => localStorage.getItem(COMPACT_KEY) === 'true');
    const [loggingOut, setLoggingOut] = useState(false);
    const navigate = useNavigate();
    const location = useLocation();
    const auth = useAuth();
    const visibility = useMemo(() => ({
        permissions: auth.user?.permissions ?? [],
        roles: auth.user?.roles ?? [],
        enabledModules: resolveEnabledModules(
            auth.tenant?.enabled_modules,
            auth.organizationUnit?.enabled_modules,
        ),
        features: auth.tenant?.features,
    }), [auth.organizationUnit?.enabled_modules, auth.tenant?.enabled_modules, auth.tenant?.features, auth.user?.permissions, auth.user?.roles]);
    const sections = useMemo(() => filterNavigation(navigationSections, visibility), [visibility]);
    const actions = useMemo(
        () => filterNavigation([{ id: 'actions', items: navigationActions }], visibility)[0]?.items ?? [],
        [visibility],
    );
    const navLocation = useMemo(
        () => ({ pathname: location.pathname, search: location.search }),
        [location.pathname, location.search],
    );
    const [expandedIds, setExpandedIds] = useState<string[]>(() =>
        initialExpandedIds(sections, navLocation, readStoredExpanded()));
    const activeTrail = useMemo(
        () => activeNavigationTrail(sections, navLocation),
        [navLocation, sections],
    );
    const closeMobile = useCallback(() => setSidebarOpen(false), []);
    const mobileRef = useFocusTrap<HTMLElement>(sidebarOpen, closeMobile);

    useEffect(() => {
        setExpandedIds((current) => initialExpandedIds(sections, navLocation, current));
    }, [navLocation, sections]);

    useEffect(() => {
        localStorage.setItem(EXPANDED_KEY, JSON.stringify(expandedIds));
    }, [expandedIds]);

    useEffect(() => {
        localStorage.setItem(COMPACT_KEY, String(compact));
    }, [compact]);

    useEffect(() => {
        const handleShortcut = (event: globalThis.KeyboardEvent) => {
            if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
                event.preventDefault();
                setPaletteOpen(true);
            }
        };
        window.addEventListener('keydown', handleShortcut);
        return () => window.removeEventListener('keydown', handleShortcut);
    }, []);

    const toggleExpanded = (id: string) => {
        setExpandedIds((current) =>
            current.includes(id) ? current.filter((value) => value !== id) : [...current, id]);
    };
    const logout = async () => {
        setLoggingOut(true);
        try {
            await auth.logout();
        } finally {
            setLoggingOut(false);
            navigate('/login', { replace: true });
        }
    };

    return (
        <div className="min-h-screen bg-slate-100 text-slate-900">
            {sidebarOpen && (
                <button
                    type="button"
                    className="fixed inset-0 z-40 bg-slate-950/50 lg:hidden"
                    onClick={closeMobile}
                    aria-label="Close navigation"
                />
            )}

            <aside
                className={`app-sidebar fixed inset-y-0 left-0 z-50 hidden flex-col border-r border-slate-800 bg-slate-950 text-white transition-[width] motion-reduce:transition-none lg:flex ${
                    compact ? 'w-20' : 'w-72'
                }`}
            >
                <SidebarBrand compact={compact} onNavigate={() => undefined} />
                <NavigationMenu
                    sections={sections}
                    location={navLocation}
                    expandedIds={expandedIds}
                    compact={compact}
                    idPrefix="desktop"
                    onToggle={toggleExpanded}
                    onNavigate={() => undefined}
                />
                <SidebarFooter
                    compact={compact}
                    userName={auth.user?.name ?? auth.user?.email ?? 'Account'}
                    loggingOut={loggingOut}
                    onSearch={() => setPaletteOpen(true)}
                    onLogout={() => void logout()}
                    onCompact={() => setCompact((value) => !value)}
                />
            </aside>

            <aside
                ref={mobileRef}
                inert={!sidebarOpen}
                aria-hidden={!sidebarOpen}
                role="dialog"
                aria-modal="true"
                aria-label="Main navigation"
                className={`app-sidebar fixed inset-y-0 left-0 z-50 flex w-[min(19rem,88vw)] flex-col border-r border-slate-800 bg-slate-950 text-white shadow-2xl transition-transform motion-reduce:transition-none lg:hidden ${
                    sidebarOpen ? 'translate-x-0' : '-translate-x-full'
                }`}
            >
                <SidebarBrand onNavigate={closeMobile} />
                <NavigationMenu
                    sections={sections}
                    location={navLocation}
                    expandedIds={expandedIds}
                    idPrefix="mobile"
                    onToggle={toggleExpanded}
                    onNavigate={closeMobile}
                />
                <SidebarFooter
                    userName={auth.user?.name ?? auth.user?.email ?? 'Account'}
                    loggingOut={loggingOut}
                    onSearch={() => {
                        closeMobile();
                        setPaletteOpen(true);
                    }}
                    onLogout={() => void logout()}
                    onClose={closeMobile}
                />
            </aside>

            <div className={`app-content transition-[padding] motion-reduce:transition-none ${compact ? 'lg:pl-20' : 'lg:pl-72'}`}>
                <header className="app-header sticky top-0 z-30 flex h-16 items-center justify-between border-b border-slate-200 bg-white/95 px-4 backdrop-blur sm:px-6">
                    <div className="flex min-w-0 items-center gap-3">
                        <button
                            type="button"
                            className="min-h-10 rounded-md border border-slate-300 px-3 text-sm font-semibold focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-500 lg:hidden"
                            onClick={() => setSidebarOpen(true)}
                            aria-expanded={sidebarOpen}
                            aria-label="Open navigation"
                        >
                            Menu
                        </button>
                        <nav className="min-w-0" aria-label="Breadcrumb">
                            <ol className="flex min-w-0 items-center gap-2 text-sm">
                                {activeTrail.map((item, index) => (
                                    <li key={item.id} className="flex min-w-0 items-center gap-2">
                                        {index > 0 && <span aria-hidden="true" className="text-slate-300">/</span>}
                                        {item.route && index < activeTrail.length - 1 ? (
                                            <Link className="truncate text-slate-500 hover:text-sky-700" to={item.route}>
                                                {item.label}
                                            </Link>
                                        ) : (
                                            <span className="truncate font-semibold text-slate-900">{item.label}</span>
                                        )}
                                    </li>
                                ))}
                            </ol>
                            <p className="hidden truncate text-xs text-slate-500 sm:block">
                                {auth.tenant?.name ?? 'Tenant context'} / {auth.organizationUnit?.name ?? 'Global organization'}
                            </p>
                        </nav>
                    </div>
                    <button
                        type="button"
                        onClick={() => setPaletteOpen(true)}
                        className="min-h-10 rounded-md border border-slate-300 bg-white px-3 text-sm font-medium text-slate-600 hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-500"
                    >
                        Search
                        <span className="ml-2 hidden text-xs text-slate-400 sm:inline">Ctrl K</span>
                    </button>
                </header>
                <main className="app-main p-4 sm:p-6 lg:p-8">
                    <Outlet />
                </main>
            </div>

            <NavigationPalette
                open={paletteOpen}
                sections={sections}
                actions={actions}
                onClose={() => setPaletteOpen(false)}
            />
        </div>
    );
}

function SidebarBrand({ compact = false, onNavigate }: { compact?: boolean; onNavigate: () => void }) {
    return (
        <div className="flex h-16 shrink-0 items-center border-b border-slate-800 px-4">
            <Link
                to="/dashboard"
                onClick={onNavigate}
                className="min-w-0 rounded focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-400"
                aria-label="AutoERP dashboard"
            >
                {compact ? (
                    <span className="flex h-9 w-9 items-center justify-center rounded-md bg-sky-600 text-sm font-bold">AE</span>
                ) : (
                    <>
                        <span className="block text-lg font-bold">AutoERP</span>
                        <span className="block text-xs font-medium text-slate-400">Business operations</span>
                    </>
                )}
            </Link>
        </div>
    );
}

function SidebarFooter({
    compact = false,
    userName,
    loggingOut,
    onSearch,
    onLogout,
    onCompact,
    onClose,
}: {
    compact?: boolean;
    userName: string;
    loggingOut: boolean;
    onSearch: () => void;
    onLogout: () => void;
    onCompact?: () => void;
    onClose?: () => void;
}) {
    const buttonClass = 'min-h-10 rounded-md border border-slate-700 text-sm font-semibold text-slate-300 hover:bg-slate-800 hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-400';
    return (
        <div className="shrink-0 border-t border-slate-800 p-3">
            {!compact && <p className="mb-3 truncate px-1 text-xs text-slate-400">{userName}</p>}
            <div className={`grid gap-2 ${compact ? 'grid-cols-1' : 'grid-cols-2'}`}>
                <button type="button" title="Search navigation" aria-label="Search navigation" className={buttonClass} onClick={onSearch}>
                    {compact ? 'S' : 'Search'}
                </button>
                {onCompact && (
                    <button type="button" title={compact ? 'Expand sidebar' : 'Collapse sidebar'} aria-label={compact ? 'Expand sidebar' : 'Collapse sidebar'} className={buttonClass} onClick={onCompact}>
                        {compact ? '>' : 'Compact'}
                    </button>
                )}
                {onClose && (
                    <button type="button" className={buttonClass} onClick={onClose}>Close</button>
                )}
                <Button
                    variant="ghost"
                    loading={loggingOut}
                    onClick={onLogout}
                    className={`${compact ? '' : 'col-span-2'} border border-slate-700 text-slate-300 hover:bg-slate-800 hover:text-white`}
                    aria-label="Logout"
                >
                    {compact ? 'X' : 'Logout'}
                </Button>
            </div>
        </div>
    );
}

function readStoredExpanded(): string[] {
    try {
        const value: unknown = JSON.parse(localStorage.getItem(EXPANDED_KEY) ?? '[]');
        return Array.isArray(value) ? value.filter((item): item is string => typeof item === 'string') : [];
    } catch {
        return [];
    }
}
