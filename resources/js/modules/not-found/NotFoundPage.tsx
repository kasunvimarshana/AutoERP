import { DASHBOARD_PATH, PLATFORM_HOME_PATH } from '@/app/routePaths';
import { useAuth } from '@/modules/auth/AuthProvider';
import { LinkButton } from '@/shared/components/Button';

export default function NotFoundPage() {
    const auth = useAuth();
    const homePath = auth.isPlatformOperator ? PLATFORM_HOME_PATH : DASHBOARD_PATH;
    const homeLabel = auth.isPlatformOperator ? 'Platform Administration' : 'Dashboard';

    return (
        <div className="flex min-h-[50vh] items-center justify-center py-12">
            <div className="w-full max-w-md rounded-2xl border border-slate-200/80 bg-white p-8 text-center shadow-xs">
                <div className="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-50 text-blue-600">
                    <svg className="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="1.75" aria-hidden="true">
                        <path strokeLinecap="round" strokeLinejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                    </svg>
                </div>
                <h1 className="text-xl font-bold tracking-tight text-slate-900">Page not found</h1>
                <p className="mt-2 text-sm text-slate-500">The requested AutoERP route does not exist or may have been moved.</p>
                <div className="mt-6 flex justify-center">
                    <LinkButton to={homePath}>Return to {homeLabel}</LinkButton>
                </div>
            </div>
        </div>
    );
}
