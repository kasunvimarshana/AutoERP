import { useState } from 'react';
import { NavLink, Outlet } from 'react-router-dom';
import { useAppContext } from '@/app/providers/AppProviders';
import { Button } from '@/shared/components/Button';
import { Input } from '@/shared/components/Input';
import { Modal } from '@/shared/components/Modal';

const navigation = [
    { to: '/dashboard', label: 'Dashboard' },
    { to: '/suppliers', label: 'Suppliers' },
    { to: '/items', label: 'Items' },
    { to: '/inventory', label: 'Inventory' },
    { to: '/purchase/orders', label: 'Purchase Orders' },
    { to: '/invoices', label: 'Invoices' },
    { to: '/payments', label: 'Payments' },
    { to: '/finance/accounts', label: 'Finance' },
];

function ContextSettings({ open, onClose }: { open: boolean; onClose: () => void }) {
    const context = useAppContext();
    const [token, setToken] = useState(context.accessToken ?? '');
    const [tenant, setTenant] = useState(context.tenantId?.toString() ?? '');
    const [organizationUnit, setOrganizationUnit] = useState(context.organizationUnitId?.toString() ?? '');

    return (
        <Modal open={open} title="API context" onClose={onClose}>
            <form
                className="space-y-4"
                onSubmit={(event) => {
                    event.preventDefault();
                    context.setAccessToken(token.trim() || null);
                    context.setScope(tenant ? Number(tenant) : null, organizationUnit ? Number(organizationUnit) : null);
                    onClose();
                }}
            >
                <Input label="Access token" type="password" value={token} onChange={(event) => setToken(event.target.value)} hint="Stored in this browser and sent as a Bearer token." />
                <div className="grid gap-4 sm:grid-cols-2">
                    <Input label="Tenant ID" type="number" min="1" value={tenant} onChange={(event) => setTenant(event.target.value)} required />
                    <Input label="Organization unit ID" type="number" min="1" value={organizationUnit} onChange={(event) => setOrganizationUnit(event.target.value)} />
                </div>
                <div className="flex justify-end gap-2">
                    <Button type="button" variant="secondary" onClick={onClose}>Cancel</Button>
                    <Button type="submit">Apply context</Button>
                </div>
            </form>
        </Modal>
    );
}

export function AppLayout() {
    const [sidebarOpen, setSidebarOpen] = useState(false);
    const [settingsOpen, setSettingsOpen] = useState(false);
    const { tenantId, organizationUnitId } = useAppContext();

    return (
        <div className="min-h-screen bg-slate-100">
            {sidebarOpen && <button className="fixed inset-0 z-30 bg-slate-950/40 lg:hidden" onClick={() => setSidebarOpen(false)} aria-label="Close navigation" />}
            <aside className={`fixed inset-y-0 left-0 z-40 w-64 border-r border-slate-800 bg-slate-950 text-white transition-transform lg:translate-x-0 ${sidebarOpen ? 'translate-x-0' : '-translate-x-full'}`}>
                <div className="flex h-16 items-center border-b border-slate-800 px-5">
                    <span className="text-lg font-bold tracking-tight">AutoERP</span>
                    <span className="ml-2 rounded bg-sky-500/20 px-2 py-0.5 text-xs text-sky-300">Foundation</span>
                </div>
                <nav className="space-y-1 p-3">
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
                    <button className="rounded-lg border border-slate-300 px-3 py-2 text-sm lg:hidden" onClick={() => setSidebarOpen(true)}>Menu</button>
                    <div className="hidden text-sm text-slate-500 sm:block">
                        Tenant <strong className="text-slate-800">{tenantId ?? 'not set'}</strong>
                        <span className="mx-2 text-slate-300">/</span>
                        Org unit <strong className="text-slate-800">{organizationUnitId ?? 'not set'}</strong>
                    </div>
                    <Button variant="secondary" onClick={() => setSettingsOpen(true)}>API context</Button>
                </header>
                <main className="p-4 sm:p-6 lg:p-8">
                    <Outlet />
                </main>
            </div>
            <ContextSettings open={settingsOpen} onClose={() => setSettingsOpen(false)} />
        </div>
    );
}
