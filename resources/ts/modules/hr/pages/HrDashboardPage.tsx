import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { PreviewPanel } from '../../../shared/components/business/PreviewPanel';
import { Button } from '../../../shared/components/ui/Button';
import { EmployeeTable } from '../components/EmployeeComponents';
import { HrDashboardCards, HrPageHeader } from '../components/HrComponents';
import { hrApi } from '../services/hrApi';
import type { Employee, HrDashboardMetric } from '../types/hr.types';

export function HrDashboardPage() {
    const [metrics, setMetrics] = useState<HrDashboardMetric[]>([]);
    const [employees, setEmployees] = useState<Employee[]>([]);

    useEffect(() => {
        hrApi.dashboard.summary().then((response) => setMetrics(response.data));
        hrApi.employees.list().then((response) => setEmployees(response.data));
    }, []);

    return (
        <div className="space-y-6">
            <HrPageHeader
                actions={<><Link to="/hr/employees/new"><Button>New Employee</Button></Link><Link to="/hr/employees"><Button variant="blue">Employees</Button></Link></>}
                subtitle="Employee profiles, departments, designations, contacts, addresses, employment details, optional user access, lookup, attendance, leave, salary profile, and audit."
                title="HR Dashboard"
            />
            <HrDashboardCards metrics={metrics} />
            <PreviewPanel status="Architecture" title="Employee and User Separation">
                <div className="grid gap-3 text-sm md:grid-cols-2 xl:grid-cols-4">
                    {['Employee profile is HR-owned', 'User access is optional', 'No automatic user creation', 'Vehicle modules validate employees through HR', 'Backend owns leave totals', 'Backend owns attendance totals', 'Backend owns payroll values', 'Status history is backend-owned'].map((item) => (
                        <div className="rounded-lg border border-slate-200 bg-slate-50 p-3 font-semibold text-slate-700" key={item}>{item}</div>
                    ))}
                </div>
            </PreviewPanel>
            <div className="space-y-3">
                <h2 className="text-base font-bold text-slate-950">Recent Employees</h2>
                <EmployeeTable rows={employees} />
            </div>
        </div>
    );
}

export { HrDashboardPage as HrPage };
