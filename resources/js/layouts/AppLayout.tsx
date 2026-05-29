import { Outlet } from 'react-router-dom';
import { Sidebar } from '../components/navigation/Sidebar';
import { Topbar } from '../components/navigation/Topbar';

export function AppLayout() {
    return (
        <div className="min-h-screen lg:flex">
            <Sidebar />

            <main className="flex-1 px-4 py-4 sm:px-6 lg:px-8 lg:py-6">
                <div className="mx-auto flex max-w-[1680px] flex-col gap-6">
                    <Topbar />
                    <Outlet />
                </div>
            </main>
        </div>
    );
}
