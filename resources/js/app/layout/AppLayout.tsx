import { useState } from 'react';
import { Link, NavLink, Outlet, useLocation, useNavigate } from 'react-router-dom';
import { useAuth } from '@/modules/auth/AuthProvider';
import { Button } from '@/shared/components/Button';

type NavigationItem = {
    to: string;
    label: string;
    match?: string[];
    exclude?: string[];
};

const navigationSections: Array<{ title: string; items: NavigationItem[] }> = [
    {
        title: 'Workflows',
        items: [
            { to: '/dashboard', label: 'Task center', match: ['/dashboard'] },
            { to: '/vehicle-service/jobs', label: 'Vehicle service workspace', match: ['/vehicle-service'] },
            { to: '/purchase/orders', label: 'Purchase workflow', match: ['/purchase'] },
            { to: '/sales/orders', label: 'Sales workflow', match: ['/sales'] },
        ],
    },
    {
        title: 'Masters',
        items: [
            { to: '/customers', label: 'Customers' },
            { to: '/vehicles', label: 'Vehicles' },
            { to: '/suppliers', label: 'Suppliers' },
            { to: '/items', label: 'Items' },
            { to: '/hr/employees', label: 'Employees' },
            { to: '/uoms', label: 'UOM' },
            { to: '/uom-conversions', label: 'UOM conversions' },
            { to: '/uom-convert', label: 'UOM convert' },
        ],
    },
    {
        title: 'Operations',
        items: [
            { to: '/inventory', label: 'Inventory' },
            { to: '/purchase/goods-receipts', label: 'Goods receipts' },
            { to: '/purchase/returns', label: 'Purchase returns' },
            { to: '/purchase/debit-notes', label: 'Debit notes' },
            { to: '/sales/quotations', label: 'Sales quotations' },
            { to: '/sales/deliveries', label: 'Sales deliveries' },
            { to: '/sales/returns', label: 'Sales returns' },
            { to: '/sales/credit-notes', label: 'Credit notes' },
        ],
    },
    {
        title: 'Finance',
        items: [
            { to: '/purchase/invoices/create', label: 'Supplier invoice' },
            { to: '/purchase/payments/prepare', label: 'Prepare supplier payment' },
            { to: '/sales/invoices/create', label: 'Customer invoice' },
            { to: '/sales/payments/prepare', label: 'Prepare customer receipt' },
            { to: '/invoices', label: 'Invoices' },
            { to: '/payments', label: 'Payments', exclude: ['/payments/cheque-templates'] },
            { to: '/payments/cheque-templates', label: 'Cheque templates' },
            { to: '/finance/accounts', label: 'Chart of accounts' },
            { to: '/finance/journals', label: 'Journals' },
            { to: '/finance/ledger', label: 'General ledger' },
            { to: '/finance/trial-balance', label: 'Trial balance' },
            { to: '/finance/account-balances', label: 'Account balances' },
            { to: '/reports', label: 'Reports' },
        ],
    },
];

export function AppLayout() {
    const [sidebarOpen, setSidebarOpen] = useState(false);
    const [loggingOut, setLoggingOut] = useState(false);
    const navigate = useNavigate();
    const location = useLocation();
    const auth = useAuth();

    return (
        <div className="min-h-screen bg-slate-100 text-slate-900">
            {sidebarOpen && <button type="button" className="fixed inset-0 z-30 bg-slate-950/40 lg:hidden" onClick={() => setSidebarOpen(false)} aria-label="Close navigation" />}
            <aside className={`app-sidebar fixed inset-y-0 left-0 z-40 flex w-72 flex-col border-r border-slate-800 bg-slate-950 text-white transition-transform lg:translate-x-0 ${sidebarOpen ? 'translate-x-0' : '-translate-x-full'}`}>
                <div className="flex h-16 items-center border-b border-slate-800 px-5">
                    <Link to="/dashboard" onClick={() => setSidebarOpen(false)} className="min-w-0">
                        <span className="block text-lg font-bold tracking-tight">AutoERP</span>
                        <span className="block text-xs font-medium text-slate-400">Workflow console</span>
                    </Link>
                </div>
                <nav className="flex-1 space-y-5 overflow-y-auto p-3" aria-label="Main navigation">
                    {navigationSections.map((section) => (
                        <section key={section.title}>
                            <h2 className="px-3 pb-2 text-[11px] font-bold uppercase tracking-wider text-slate-500">{section.title}</h2>
                            <div className="space-y-1">
                                {section.items.map((item) => {
                                    const active = item.match
                                        ? item.match.some((prefix) => location.pathname === prefix || location.pathname.startsWith(`${prefix}/`))
                                        : location.pathname === item.to || location.pathname.startsWith(`${item.to}/`);
                                    const visibleActive = active && !item.exclude?.some((prefix) => location.pathname === prefix || location.pathname.startsWith(`${prefix}/`));
                                    return (
                                        <NavLink
                                            key={item.to}
                                            to={item.to}
                                            onClick={() => setSidebarOpen(false)}
                                            className={`block rounded-md px-3 py-2 text-sm font-medium transition focus:outline-none focus:ring-2 focus:ring-sky-400 ${visibleActive ? 'bg-sky-600 text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white'}`}
                                        >
                                            {item.label}
                                        </NavLink>
                                    );
                                })}
                            </div>
                        </section>
                    ))}
                </nav>
            </aside>
            <div className="app-content lg:pl-72">
                <header className="app-header sticky top-0 z-20 flex h-16 items-center justify-between border-b border-slate-200 bg-white/95 px-4 backdrop-blur sm:px-6">
                    <button type="button" className="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold lg:hidden" onClick={() => setSidebarOpen(true)}>Menu</button>
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
                <main className="app-main p-4 sm:p-6 lg:p-8">
                    <Outlet />
                </main>
            </div>
        </div>
    );
}
