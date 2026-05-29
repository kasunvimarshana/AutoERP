import type { ReactNode } from 'react';

export function AuthLayout({ children }: { children: ReactNode }) {
    return (
        <div className="min-h-screen px-4 py-10 sm:px-6 lg:px-8">
            <div className="mx-auto flex min-h-[calc(100vh-5rem)] max-w-6xl items-center justify-center rounded-[2rem] border border-slate-200/70 bg-white/80 p-6 shadow-[0_30px_80px_-50px_rgba(15,23,42,0.4)] backdrop-blur">
                <div className="w-full max-w-md">{children}</div>
            </div>
        </div>
    );
}
