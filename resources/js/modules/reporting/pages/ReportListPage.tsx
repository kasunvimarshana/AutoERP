import { Link } from 'react-router-dom';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { Panel } from '@/shared/components/Panel';
import { useApi } from '@/shared/hooks/useApi';
import { listReports } from '../reportingApi';
import type { ReportDefinition } from '../reportingTypes';

export default function ReportListPage() {
    const reports = useApi((signal) => listReports(signal), []);
    const grouped = groupReports(reports.data ?? []);

    return (
        <>
            <ContentHeader title="Reports" description="Operational and financial reports from existing AutoERP data." />
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

function groupReports(reports: ReportDefinition[]): Record<string, ReportDefinition[]> {
    return reports.reduce<Record<string, ReportDefinition[]>>((groups, report) => {
        groups[report.group] = [...(groups[report.group] ?? []), report];
        return groups;
    }, {});
}

function reportPath(key: string): string {
    if (key === 'purchase/detailed') return '/reports/purchase/detailed';
    if (key === 'vehicle-service/detailed') return '/reports/vehicle-service/detailed';
    if (key === 'vehicle-service/employee-incentives') return '/reports/vehicle-service/employee-incentives';
    if (key === 'vehicle-service.technician-work') return '/reports/vehicle-service/technician-work';
    if (key === 'vehicle-service.employee-commissions') return '/reports/vehicle-service/employee-commissions';
    return `/reports/${key}`;
}
