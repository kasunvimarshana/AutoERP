import { useEffect, useState } from 'react';
import { PreviewPanel } from '../../../shared/components/business/PreviewPanel';
import { Card } from '../../../shared/components/ui/Card';
import { Input } from '../../../shared/components/ui/Input';
import { Select } from '../../../shared/components/ui/Select';
import { Button } from '../../../shared/components/ui/Button';
import { EmployeeTable } from '../components/EmployeeComponents';
import { AttendancePanel, HrPageHeader, LeavePanel, SalaryProfileTable } from '../components/HrComponents';
import { hrApi } from '../services/hrApi';
import type { Employee, EmployeeAttendanceRecord, EmployeeLeaveRecord, EmployeeSalaryProfile } from '../types/hr.types';

export function EmployeeLookupPage() {
    const [rows, setRows] = useState<Employee[]>([]);

    useEffect(() => {
        hrApi.employees.active().then((response) => setRows(response.data as Employee[]));
    }, []);

    return (
        <div className="space-y-6">
            <HrPageHeader subtitle="Lookup active employees for modules such as VehicleService technicians and VehicleRental drivers." title="Employee Lookup" />
            <Card className="p-4">
                <div className="grid gap-3 md:grid-cols-[1fr_180px_180px_160px]">
                    <Input placeholder="Search employee..." />
                    <Select options={[{ label: 'Any department', value: '' }, { label: 'Workshop', value: 'workshop' }, { label: 'Rental', value: 'rental' }]} />
                    <Select options={[{ label: 'Any role', value: '' }, { label: 'Technician', value: 'technician' }, { label: 'Driver', value: 'driver' }]} />
                    <Button variant="secondary">Lookup</Button>
                </div>
            </Card>
            <PreviewPanel rows={[{ label: 'VehicleService', value: 'Calls HR employee validation' }, { label: 'VehicleRental', value: 'Calls HR driver validation' }]} status="Lookup" title="Cross-module Use" />
            <EmployeeTable rows={rows} />
        </div>
    );
}

export function AttendanceListPage() {
    const [rows, setRows] = useState<EmployeeAttendanceRecord[]>([]);

    useEffect(() => {
        hrApi.attendance.list().then((response) => setRows(response.data));
    }, []);

    return (
        <div className="space-y-6">
            <HrPageHeader subtitle="Attendance basics are displayed from backend. Frontend does not total attendance or overtime." title="Attendance" />
            <AttendancePanel rows={rows} />
        </div>
    );
}

export function LeaveListPage() {
    const [rows, setRows] = useState<EmployeeLeaveRecord[]>([]);

    useEffect(() => {
        hrApi.leave.list().then((response) => setRows(response.data));
    }, []);

    return (
        <div className="space-y-6">
            <HrPageHeader subtitle="Leave basics are displayed from backend. Frontend does not calculate leave balances." title="Leave" />
            <LeavePanel rows={rows} />
        </div>
    );
}

export function SalaryProfileListPage() {
    const [rows, setRows] = useState<EmployeeSalaryProfile[]>([]);

    useEffect(() => {
        hrApi.salaryProfiles.list().then((response) => setRows(response.data));
    }, []);

    return (
        <div className="space-y-6">
            <HrPageHeader subtitle="Salary profile values are backend readonly. Frontend does not calculate salary payable or payroll." title="Salary Profiles" />
            <SalaryProfileTable rows={rows} />
        </div>
    );
}
