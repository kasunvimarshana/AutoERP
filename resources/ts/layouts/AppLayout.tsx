import { Outlet } from 'react-router-dom';
import { Sidebar } from './components/Sidebar';
import { Topbar } from './components/Topbar';

export function AppLayout() {
    return (
        <div className="min-h-screen bg-slate-50">
            <Sidebar />
            <div className="min-h-screen lg:pl-64">
                <Topbar />
                <main className="px-5 py-7 md:px-8">
                    <Outlet />
                </main>
            </div>
        </div>
    );
}
