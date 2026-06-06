import { useState } from 'react';
import { Outlet, useNavigate } from 'react-router-dom';
import { useAuthContext } from '../contexts/AuthContext';
import { Sidebar } from '../shared/components/layout/Sidebar';
import { Topbar } from '../shared/components/layout/Topbar';

export function AppLayout() {
    const { logout, user } = useAuthContext();
    const navigate = useNavigate();
    const [sidebarCollapsed, setSidebarCollapsed] = useState(false);
    const [mobileSidebarOpen, setMobileSidebarOpen] = useState(false);

    async function signOut() {
        await logout();
        navigate('/login', { replace: true });
    }

    return (
        <div className="min-h-screen bg-slate-100">
            <Sidebar
                collapsed={sidebarCollapsed}
                mobileOpen={mobileSidebarOpen}
                onCloseMobile={() => setMobileSidebarOpen(false)}
                onToggle={() => setSidebarCollapsed((current) => !current)}
            />
            <div className={sidebarCollapsed ? 'min-h-screen transition-[padding] duration-200 lg:pl-20' : 'min-h-screen transition-[padding] duration-200 lg:pl-64'}>
                <Topbar
                    onOpenMobile={() => setMobileSidebarOpen(true)}
                    onSignOut={() => void signOut()}
                    user={user}
                />
                <main className="px-4 py-6 sm:px-6 lg:px-8">
                    <div className="mx-auto max-w-[1600px]">
                        <Outlet />
                    </div>
                </main>
            </div>
        </div>
    );
}
