import { Link } from 'react-router-dom';
import { hasPermission } from '@/modules/auth/accessControl';
import { useAuth } from '@/modules/auth/AuthProvider';
import { financePermissions } from '@/modules/finance/financePermissions';
import { inventoryPermissions } from '@/modules/inventory/inventoryPermissions';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { Panel } from '@/shared/components/Panel';
import { useApi } from '@/shared/hooks/useApi';
import { listReports } from '../reportingApi';
import type { ReportDefinition } from '../reportingTypes';

export default function ReportListPage() {
    const auth = useAuth();
    const reports = useApi((signal) => listReports(signal), []);
    const grouped = groupReports(reports.data ?? []);
    const sections = Object.entries(grouped);
    if (!grouped.Finance) sections.push(['Finance', []]);
    const canViewCustomerOutstanding = hasPermission(auth, financePermissions.reportsView);
    const canViewConsumableUsage = hasPermission(auth, inventoryPermissions.consumableUsageView)
        && auth.enabledModules?.includes('finance')
        && auth.enabledModules?.includes('inventory');

    return (
        <>
            <ContentHeader title="Reports" description="Operational and financial reports from existing AutoERP data." />
            <Link to="/reports/payment-method-wise-summary" className="mb-5 block rounded-xl border border-sky-200 bg-sky-50 p-4 text-sm font-semibold text-sky-900 hover:bg-sky-100">Payment Method Wise Summary — daily receipts and new credit sales</Link>
            <ErrorAlert error={reports.error} />
            {reports.loading ? <LoadingState label="Loading reports..." /> : (
                <div className="grid gap-5 xl:grid-cols-2">
                    {sections.map(([group, rows]) => (
                        <Panel key={group} title={group}>
                            <div className="divide-y divide-slate-100">
                                {group === 'Finance' && canViewCustomerOutstanding && <Link to="/reports/customer-outstanding" className="block px-1 py-3 hover:bg-slate-50">
                                    <div className="font-semibold text-slate-900">Customer Outstanding</div>
                                    <div className="text-sm text-slate-500">Customer balances by currency, aging, and invoice</div>
                                </Link>}
                                {group === 'Finance' && canViewConsumableUsage && <Link to="/reports/consumable-usage" className="block px-1 py-3 hover:bg-slate-50">
                                    <div className="font-semibold text-slate-900">Consumable Usage</div>
                                    <div className="text-sm text-slate-500">Quantity used and inventory cost by item and date</div>
                                </Link>}
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
    if (key === 'purchase/grn-payables') return '/reports/purchase/grn-payables';
    if (key === 'purchase/goods-receipt-summary') return '/reports/purchase/goods-receipt-summary';
    if (key === 'vehicle-service/detailed') return '/reports/vehicle-service/detailed';
    if (key === 'vehicle-service/employee-incentives') return '/reports/vehicle-service/employee-incentives';
    if (key === 'vehicle-service.technician-work') return '/reports/vehicle-service/technician-work';
    if (key === 'vehicle-service.employee-commissions') return '/reports/vehicle-service/employee-commissions';
    if (key === 'vehicle-service/service-history') return '/reports/vehicle-service/service-history';
    return `/reports/${key}`;
}
