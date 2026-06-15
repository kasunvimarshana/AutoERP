import { Link, useSearchParams } from 'react-router-dom';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { Panel } from '@/shared/components/Panel';
import { useApi } from '@/shared/hooks/useApi';
import { listReports } from '../reportingApi';
import type { ReportDefinition } from '../reportingTypes';

export default function ReportListPage() {
    const [searchParams] = useSearchParams();
    const reports = useApi((signal) => listReports(signal), []);
    const scope = searchParams.get('group');
    const grouped = groupReports(filterReports(reports.data ?? [], scope));

    return (
        <>
            <ContentHeader title={reportTitle(scope)} description="Reports generated from existing AutoERP data." />
            <ErrorAlert error={reports.error} />
            {reports.loading ? <LoadingState label="Loading reports..." /> : (
                <div className="grid gap-5 xl:grid-cols-2">
                    {Object.entries(grouped).map(([group, rows]) => (
                        <Panel key={group} title={group}>
                            <div className="divide-y divide-slate-100">
                                {rows.map((report) => (
                                    <Link key={report.key} to={reportPath(report.key)} className="block px-1 py-3 hover:bg-slate-50">
                                        <div className="font-semibold text-slate-900">{report.title}</div>
                                        <div className="text-sm text-slate-500">{report.key}</div>
                                    </Link>
                                ))}
                            </div>
                        </Panel>
                    ))}
                </div>
            )}
        </>
    );
}

function filterReports(reports: ReportDefinition[], scope: string | null): ReportDefinition[] {
    if (!scope || scope === 'operational') {
        return scope === 'operational'
            ? reports.filter((report) => !['Finance', 'Tax', 'Invoice & Payment', 'Vouchers', 'Masters'].includes(report.group))
            : reports;
    }
    const groupNames: Record<string, string[]> = {
        sales: ['Sales'],
        purchase: ['Purchase'],
        inventory: ['Inventory'],
        'vehicle-service': ['Vehicle Service'],
        'vehicle-rental': ['Vehicle Rental'],
        finance: ['Finance', 'Invoice & Payment', 'Vouchers'],
    };
    return reports.filter((report) => groupNames[scope]?.includes(report.group));
}

function reportTitle(scope: string | null): string {
    const titles: Record<string, string> = {
        operational: 'Operational Reports',
        sales: 'Sales Reports',
        purchase: 'Purchase Reports',
        inventory: 'Inventory Reports',
        'vehicle-service': 'Vehicle Service Reports',
        'vehicle-rental': 'Vehicle Rental Reports',
        finance: 'Financial Reports',
    };
    return scope ? titles[scope] ?? 'Reports' : 'Reports';
}

function groupReports(reports: ReportDefinition[]): Record<string, ReportDefinition[]> {
    return reports.reduce<Record<string, ReportDefinition[]>>((groups, report) => {
        groups[report.group] = [...(groups[report.group] ?? []), report];
        return groups;
    }, {});
}

function reportPath(key: string): string {
    if (key === 'vehicle-service.technician-work') return '/reports/vehicle-service/technician-work';
    if (key === 'vehicle-service.employee-commissions') return '/reports/vehicle-service/employee-commissions';
    return `/reports/${key}`;
}
