import { Outlet } from 'react-router-dom';

export function AuthLayout() {
    return (
        <div className="flex min-h-screen items-center justify-center bg-slate-50 p-6">
            <Outlet />
        </div>
    );
}
