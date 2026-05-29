import { Link } from 'react-router-dom';

export function NotFoundPage() {
    return (
        <div className="flex min-h-[60vh] items-center justify-center">
            <div className="max-w-xl rounded-[2rem] border border-slate-200 bg-white/90 p-8 text-center shadow-[0_20px_50px_-35px_rgba(15,23,42,0.4)]">
                <div className="font-display text-3xl font-semibold text-slate-950">Page not found</div>
                <p className="mt-3 text-sm leading-7 text-slate-600">The requested route is not part of the current ERP shell.</p>
                <Link to="/dashboard" className="mt-6 inline-flex rounded-full bg-slate-950 px-5 py-2.5 text-sm font-medium text-white">
                    Return to dashboard
                </Link>
            </div>
        </div>
    );
}
