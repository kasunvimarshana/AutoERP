import { useEffect, useState } from 'react';
import { Outlet, useLocation } from 'react-router-dom';
import { findAppPageMeta } from '../router/app-navigation';
import { AppSidebar } from '../../components/layout/AppSidebar';
import { AppTopbar } from '../../components/layout/AppTopbar';

export function AppShellLayout() {
    const location = useLocation();
    const currentPage = findAppPageMeta(location.pathname);
    const [isSidebarOpen, setIsSidebarOpen] = useState(false);

    useEffect(() => {
        setIsSidebarOpen(false);
    }, [location.pathname]);

    return (
        <div className="min-h-screen">
            <div className="flex min-h-screen">
                <AppSidebar currentPage={currentPage} isOpen={isSidebarOpen} onClose={() => setIsSidebarOpen(false)} />

                <div className="flex min-w-0 flex-1 flex-col">
                    <AppTopbar
                        currentPage={currentPage}
                        isSidebarOpen={isSidebarOpen}
                        onSidebarToggle={() => setIsSidebarOpen((current) => !current)}
                    />

                    <main className="flex-1 px-4 py-4 sm:px-6 lg:px-8">
                        <Outlet />
                    </main>
                </div>
            </div>
        </div>
    );
}
