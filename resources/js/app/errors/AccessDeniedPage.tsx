import { useLocation } from 'react-router-dom';
import { useAuth } from '@/modules/auth/AuthProvider';
import { PLATFORM_HOME_PATH, DASHBOARD_PATH } from '@/app/routePaths';
import { LinkButton } from '@/shared/components/Button';

interface AccessDeniedPageProps {
    title?: string;
    message?: string;
}

export function AccessDeniedPage({
    title = 'Access denied',
    message = 'Your account does not have permission to open this page.',
}: AccessDeniedPageProps) {
    const auth = useAuth();
    const location = useLocation();
    const homePath = auth.isPlatformOperator ? PLATFORM_HOME_PATH : DASHBOARD_PATH;

    return (
        <main className="flex min-h-[60vh] items-center justify-center px-4 py-12">
            <section className="w-full max-w-xl rounded-2xl border border-amber-200/80 bg-white p-7 shadow-xl">
                <p className="text-sm font-bold uppercase tracking-wider text-amber-700">403</p>
                <h1 className="mt-2 text-2xl font-bold tracking-tight text-slate-900">{title}</h1>
                <p className="mt-3 text-sm leading-relaxed text-slate-600">{message}</p>
                <p className="mt-3 break-all rounded-lg bg-slate-50 px-3 py-2 text-xs text-slate-500">
                    Requested page: {location.pathname}
                </p>
                <LinkButton
                    to={homePath}
                    replace
                    className="mt-6"
                >
                    Return to {auth.isPlatformOperator ? 'Platform Administration' : 'Dashboard'}
                </LinkButton>
            </section>
        </main>
    );
}
