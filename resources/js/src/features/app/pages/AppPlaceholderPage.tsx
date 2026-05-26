import { Button } from '../../../components/ui/Button';
import { Card } from '../../../components/ui/Card';
import { useAuth } from '../../auth/context/AuthContext';
import { useTenant } from '../../auth/context/TenantContext';

export function AppPlaceholderPage() {
    const { logout, user } = useAuth();
    const { tenantId } = useTenant();

    return (
        <main className="min-h-screen px-6 py-10">
            <div className="mx-auto flex w-full max-w-5xl flex-col gap-6">
                <div className="space-y-2">
                    <p className="text-sm font-medium uppercase tracking-[0.18em] text-stone-500">AutoERP</p>
                    <h1 className="text-3xl font-semibold text-stone-950">Frontend foundation is ready</h1>
                    <p className="max-w-3xl text-sm leading-6 text-stone-600">
                        Phase 1A is wired and runnable. React, TypeScript, routing, auth state, tenant state, and the shared
                        API client are connected. Dashboard, sidebar, and module pages are intentionally deferred.
                    </p>
                </div>

                <div className="grid gap-4 md:grid-cols-2">
                    <Card className="p-6">
                        <h2 className="text-lg font-semibold text-stone-900">Session</h2>
                        <dl className="mt-4 space-y-3 text-sm text-stone-600">
                            <div className="flex items-center justify-between gap-4 border-b border-stone-100 pb-3">
                                <dt>User</dt>
                                <dd className="text-right font-medium text-stone-900">{user?.email ?? 'Unknown user'}</dd>
                            </div>
                            <div className="flex items-center justify-between gap-4 border-b border-stone-100 pb-3">
                                <dt>Name</dt>
                                <dd className="text-right font-medium text-stone-900">
                                    {[user?.first_name, user?.last_name].filter(Boolean).join(' ') || 'Not available'}
                                </dd>
                            </div>
                            <div className="flex items-center justify-between gap-4">
                                <dt>Tenant</dt>
                                <dd className="text-right font-medium text-stone-900">{tenantId}</dd>
                            </div>
                        </dl>
                    </Card>

                    <Card className="p-6">
                        <h2 className="text-lg font-semibold text-stone-900">Next Phase</h2>
                        <ul className="mt-4 space-y-3 text-sm leading-6 text-stone-600">
                            <li>Accordion sidebar and topbar shell</li>
                            <li>Dashboard layout widgets</li>
                            <li>Module route groups and protected screens</li>
                        </ul>
                        <div className="mt-6">
                            <Button onClick={() => void logout()} variant="secondary">
                                Sign out
                            </Button>
                        </div>
                    </Card>
                </div>
            </div>
        </main>
    );
}
