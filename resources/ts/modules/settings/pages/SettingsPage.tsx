import { useEffect, useState } from 'react';
import { Link, useLocation } from 'react-router-dom';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { Card } from '../../../shared/components/ui/Card';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import { settingsApi } from '../services/settingsApi';
import type { SettingRecord } from '../types/settings.types';

const moduleSettings = [
    { description: 'Supplier defaults, purchase document definitions, workflow controls, and purchase account mappings.', label: 'Purchase Settings', path: '/purchase/settings' },
    { description: 'Customer defaults, sales document definitions, stock issue behavior, and sales account mappings.', label: 'Sales Settings', path: '/sales/settings' },
    { description: 'Workshop defaults, stock reservation, invoice generation, finance posting, and service document setup.', label: 'Vehicle Service Settings', path: '/vehicle-service/settings' },
    { description: 'Rental document definitions, rate defaults, provider payable behavior, and rental workflow controls.', label: 'Vehicle Rental Settings', path: '/vehicle-rental/settings' },
    { description: 'Voucher account mappings, payment method defaults, document definitions, approval, and posting rules.', label: 'Voucher Settings', path: '/vouchers/settings' },
];

function routeTitle(pathname: string) {
    if (pathname.endsWith('/users')) {
        return 'Users & Permissions';
    }

    if (pathname.endsWith('/organization-units')) {
        return 'Organization Units';
    }

    return 'Settings';
}

export function SettingsPage() {
    const location = useLocation();
    const [rows, setRows] = useState<SettingRecord[]>([]);
    const [error, setError] = useState<Error | null>(null);
    const [isLoading, setIsLoading] = useState(true);

    useEffect(() => {
        let active = true;
        settingsApi.list()
            .then((response) => {
                if (active) {
                    setRows(response.data);
                    setError(null);
                }
            })
            .catch((caught: Error) => {
                if (active) {
                    setError(caught);
                }
            })
            .finally(() => {
                if (active) {
                    setIsLoading(false);
                }
            });

        return () => {
            active = false;
        };
    }, []);

    const title = routeTitle(location.pathname);

    return (
        <div className="space-y-6">
            <PageHeader
                eyebrow="Administration"
                subtitle="Tenant settings and module configuration entry points. Module pages load their own backend settings on demand."
                title={title}
            />

            {error ? <EmptyState description={error.message} title="Settings failed to load" /> : null}

            <section className="grid gap-4 lg:grid-cols-2">
                {moduleSettings.map((item) => (
                    <Link className="block" key={`settings-link-${item.path}`} to={item.path}>
                        <Card className="h-full p-5 transition hover:border-slate-300 hover:shadow-md">
                            <div className="flex items-start justify-between gap-4">
                                <div>
                                    <h2 className="text-sm font-semibold text-slate-950">{item.label}</h2>
                                    <p className="mt-2 text-sm leading-6 text-slate-500">{item.description}</p>
                                </div>
                                <span className="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">Backend</span>
                            </div>
                        </Card>
                    </Link>
                ))}
            </section>

            <Card className="overflow-hidden">
                <div className="border-b border-slate-200 p-5">
                    <h2 className="text-sm font-semibold text-slate-950">Tenant Settings</h2>
                    <p className="mt-1 text-sm text-slate-500">Current tenant-level key/value settings returned by the backend.</p>
                </div>
                {isLoading ? (
                    <div className="p-5 text-sm text-slate-500">Loading tenant settings...</div>
                ) : rows.length === 0 ? (
                    <div className="p-5">
                        <EmptyState description="No tenant settings were returned for the active tenant context." title="No tenant settings configured" />
                    </div>
                ) : (
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-slate-200 text-sm">
                            <thead className="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                <tr>
                                    <th className="px-5 py-3">Area</th>
                                    <th className="px-5 py-3">Key</th>
                                    <th className="px-5 py-3">Value</th>
                                    <th className="px-5 py-3">Status</th>
                                    <th className="px-5 py-3">Updated</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100 bg-white">
                                {rows.map((row) => (
                                    <tr key={`tenant-setting-${row.id || row.key}`}>
                                        <td className="px-5 py-3 font-medium text-slate-900">{row.area}</td>
                                        <td className="px-5 py-3 text-slate-600">{row.key}</td>
                                        <td className="max-w-md truncate px-5 py-3 text-slate-600">{row.value}</td>
                                        <td className="px-5 py-3 text-slate-600">{row.status}</td>
                                        <td className="px-5 py-3 text-slate-500">{row.updatedAt ?? 'Not available'}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </Card>
        </div>
    );
}
