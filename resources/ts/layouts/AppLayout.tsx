import { NavLink, Outlet, useNavigate } from 'react-router-dom';
import type { ReactNode } from 'react';
import { useAuthContext } from '../contexts/AuthContext';
import { Button } from '../shared/components/ui/Button';
import { cn } from '../shared/utils/cn';

export function AppLayout() {
    const { logout, user } = useAuthContext();
    const navigate = useNavigate();

    async function signOut() {
        await logout();
        navigate('/login', { replace: true });
    }

    return (
        <div className="min-h-screen bg-slate-50">
            <header className="sticky top-0 z-10 border-b border-slate-200 bg-white/95 backdrop-blur">
                <div className="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-3 sm:px-6 lg:px-8">
                    <div className="flex items-center gap-8">
                        <NavLink className="flex items-center gap-3" to="/customers">
                            <span className="flex h-9 w-9 items-center justify-center rounded-lg bg-slate-950 text-xs font-bold text-white">AE</span>
                            <span><span className="block text-sm font-bold text-slate-950">AutoERP</span><span className="block text-[10px] font-bold uppercase tracking-widest text-slate-400">Party management</span></span>
                        </NavLink>
                        <nav className="hidden items-center gap-1 sm:flex">
                            <AppLink to="/customers">Customers</AppLink>
                            <AppLink to="/suppliers">Suppliers</AppLink>
                        </nav>
                    </div>
                    <div className="flex items-center gap-3">
                        <div className="hidden text-right md:block"><p className="text-sm font-semibold text-slate-800">{user?.name}</p><p className="text-xs text-slate-400">{user?.email}</p></div>
                        <Button onClick={() => void signOut()} variant="secondary">Sign out</Button>
                    </div>
                </div>
                <nav className="flex border-t border-slate-100 px-4 py-2 sm:hidden">
                    <AppLink to="/customers">Customers</AppLink>
                    <AppLink to="/suppliers">Suppliers</AppLink>
                </nav>
            </header>
            <main className="mx-auto max-w-7xl px-4 py-7 sm:px-6 lg:px-8"><Outlet /></main>
        </div>
    );
}

function AppLink({ children, to }: { children: ReactNode; to: string }) {
    return <NavLink className={({ isActive }) => cn('rounded-lg px-3 py-2 text-sm font-semibold transition', isActive ? 'bg-blue-50 text-blue-700' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-950')} to={to}>{children}</NavLink>;
}
