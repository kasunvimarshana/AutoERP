import { useState } from 'react';
import { NavLink, Outlet, useNavigate } from 'react-router-dom';
import { useAuth } from '@/modules/auth/AuthProvider';
import { Button } from '@/shared/components/Button';

const navigation = [
    { to: '/dashboard', label: 'Dashboard' },
    { to: '/uoms', label: 'UOM' },
    { to: '/uom-conversions', label: 'UOM Conversions' },
    { to: '/uom-convert', label: 'UOM Convert' },
    { to: '/suppliers', label: 'Suppliers' },
    { to: '/customers', label: 'Customers' },
    { to: '/vehicles', label: 'Vehicles' },
    { to: '/items', label: 'Items' },
    { to: '/inventory', label: 'Inventory' },
    { to: '/purchase/orders', label: 'Purchase Orders' },
    { to: '/purchase/goods-receipts', label: 'Goods Receipts' },
    { to: '/purchase/returns', label: 'Purchase Returns' },
    { to: '/purchase/invoices/create', label: 'Supplier Invoice' },
    { to: '/purchase/payments/prepare', label: 'Prepare Payment' },
    { to: '/purchase/debit-notes', label: 'Debit Notes' },
    { to: '/invoices', label: 'Invoices' },
    { to: '/payments', label: 'Payments' },
    { to: '/finance/accounts', label: 'Finance' },
    { to: '/hr/employees', label: 'Employees' },
    { to: '/vehicle-service/jobs', label: 'Vehicle Service' },
];

export function AppLayout() {
    const [sidebarOpen, setSidebarOpen] = useState(false);
    const [loggingOut, setLoggingOut] = useState(false);
    const navigate = useNavigate();
    const auth = useAuth();

    return (
        <div className="min-h-screen bg-slate-100">
            {sidebarOpen && <button type="button" className="fixed inset-0 z-30 bg-slate-950/40 lg:hidden" onClick={() => setSidebarOpen(false)} aria-label="Close navigation" />}
            <aside className={`fixed inset-y-0 left-0 z-40 flex w-64 flex-col border-r border-slate-800 bg-slate-950 text-white transition-transform lg:translate-x-0 ${sidebarOpen ? 'translate-x-0' : '-translate-x-full'}`}>
                <div className="flex h-16 items-center border-b border-slate-800 px-5">
                    <span className="text-lg font-bold tracking-tight">AutoERP</span>
                    <span className="ml-2 rounded bg-sky-500/20 px-2 py-0.5 text-xs text-sky-300">Foundation</span>
                </div>
                <nav className="flex-1 space-y-1 overflow-y-auto p-3">
                    {navigation.map((item) => (
                        <NavLink
                            key={item.to}
                            to={item.to}
                            onClick={() => setSidebarOpen(false)}
                            className={({ isActive }) => `block rounded-lg px-3 py-2.5 text-sm font-medium transition ${isActive ? 'bg-sky-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white'}`}
                        >
                            {item.label}
                        </NavLink>
                    ))}
                </nav>
            </aside>
            <div className="lg:pl-64">
                <header className="sticky top-0 z-20 flex h-16 items-center justify-between border-b border-slate-200 bg-white/95 px-4 backdrop-blur sm:px-6">
                    <button type="button" className="rounded-lg border border-slate-300 px-3 py-2 text-sm lg:hidden" onClick={() => setSidebarOpen(true)}>Menu</button>
                    <div className="min-w-0 text-sm text-slate-500">
                        <div className="truncate">
                            <strong className="text-slate-900">{auth.user?.name ?? auth.user?.email}</strong>
                            <span className="hidden sm:inline">
                                <span className="mx-2 text-slate-300">/</span>
                                Tenant <strong className="text-slate-800">{auth.tenant?.name ?? 'Context loaded'}</strong>
                                <span className="mx-2 text-slate-300">/</span>
                                Org <strong className="text-slate-800">{auth.organizationUnit?.name ?? (auth.organizationUnit ? 'Context loaded' : 'not set')}</strong>
                            </span>
                        </div>
                    </div>
                    <Button
                        variant="secondary"
                        loading={loggingOut}
                        onClick={async () => {
                            setLoggingOut(true);
                            try {
                                await auth.logout();
                            } finally {
                                setLoggingOut(false);
                                navigate('/login', { replace: true });
                            }
                        }}
                    >
                        Logout
                    </Button>
                </header>
                <main className="p-4 sm:p-6 lg:p-8">
                    <Outlet />
                </main>
            </div>
        </div>
    );
}
