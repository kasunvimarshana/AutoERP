import { Link } from 'react-router-dom';
import { DASHBOARD_PATH } from '@/app/routePaths';

export function OrganizationUnitRequiredPage() {
    return (
        <main className="flex min-h-[60vh] items-center justify-center px-4 py-12">
            <section className="w-full max-w-xl rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <p className="text-sm font-semibold uppercase tracking-wide text-slate-500">Workspace required</p>
                <h1 className="mt-2 text-2xl font-bold text-slate-900">Select an organization unit</h1>
                <p className="mt-3 text-sm leading-6 text-slate-600">
                    This operational page requires an active branch or department context. Select an organization unit before continuing.
                </p>
                <Link
                    to={DASHBOARD_PATH}
                    replace
                    className="mt-5 inline-flex min-h-10 items-center justify-center rounded-lg bg-blue-600 px-4 text-sm font-semibold text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                >
                    Return to Dashboard
                </Link>
            </section>
        </main>
    );
}
