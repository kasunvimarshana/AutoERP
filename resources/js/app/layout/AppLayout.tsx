import { useEffect, useMemo, useState } from 'react';
import { Outlet, useLocation } from 'react-router-dom';
import { navigationSections } from '@/app/navigation/navigationConfig';
import {
    filterNavigation,
    findActiveModuleId,
    findNavigationMatch,
} from '@/app/navigation/navigationUtils';
import type { NavigationModuleItem } from '@/app/navigation/navigationTypes';
import { useAuth } from '@/modules/auth/AuthProvider';
import { AppHeader } from './AppHeader';
import { Sidebar } from './Sidebar';

export function AppLayout() {
    const [mobileOpen, setMobileOpen] = useState(false);
    const [collapsed, setCollapsed] = useState(() => window.localStorage.getItem('autoerp.sidebar.collapsed') === 'true');
    const location = useLocation();
    const auth = useAuth();
    const visibleSections = useMemo(() => filterNavigation(navigationSections, {
        tenantId: auth.tenant?.id ?? null,
        organizationUnitId: auth.organizationUnit?.id ?? null,
        roles: auth.roles,
        permissions: auth.permissions,
        permissionsLoaded: !auth.isLoading && auth.isAuthenticated,
        enabledModules: auth.enabledModules,
    }), [auth.enabledModules, auth.isAuthenticated, auth.isLoading, auth.organizationUnit?.id, auth.permissions, auth.roles, auth.tenant?.id]);
    const match = useMemo(
        () => findNavigationMatch(location.pathname, location.search, visibleSections),
        [location.pathname, location.search, visibleSections],
    );
    const activeModuleId = findActiveModuleId(location.pathname, location.search, visibleSections);
    const firstModuleId = visibleSections
        .flatMap((section) => section.items)
        .find((item): item is NavigationModuleItem => item.type === 'module')?.id ?? null;
    const [expandedModuleId, setExpandedModuleId] = useState<string | null>(activeModuleId ?? firstModuleId);

    useEffect(() => {
        if (activeModuleId) setExpandedModuleId(activeModuleId);
        setMobileOpen(false);
    }, [activeModuleId, location.pathname, location.search]);

    useEffect(() => {
        if (expandedModuleId && !visibleSections.some((section) => section.items.some((item) => item.id === expandedModuleId))) {
            setExpandedModuleId(firstModuleId);
        }
    }, [expandedModuleId, firstModuleId, visibleSections]);

    useEffect(() => {
        window.localStorage.setItem('autoerp.sidebar.collapsed', String(collapsed));
    }, [collapsed]);

    return (
        <div className="min-h-screen bg-slate-100 text-slate-900">
            <Sidebar
                sections={visibleSections}
                activeItemId={match?.item.id ?? null}
                activeParentId={match?.parent?.id ?? null}
                expandedModuleId={expandedModuleId}
                mobileOpen={mobileOpen}
                collapsed={collapsed}
                user={auth.user}
                tenant={auth.tenant}
                organizationUnit={auth.organizationUnit}
                onCloseMobile={() => setMobileOpen(false)}
                onExpandDesktop={() => setCollapsed(false)}
                onToggleModule={(moduleId) => setExpandedModuleId((current) => current === moduleId ? null : moduleId)}
            />
            <div className={`app-content min-h-screen ${collapsed ? 'lg:pl-20' : 'lg:pl-72'}`}>
                <AppHeader
                    collapsed={collapsed}
                    match={match}
                    onToggleCollapsed={() => setCollapsed((current) => !current)}
                    onOpenMobile={() => setMobileOpen(true)}
                />
                <main className="app-main mx-auto w-full max-w-[1600px] p-4 sm:p-6 lg:p-8">
                    <Outlet />
                </main>
            </div>
        </div>
    );
}
