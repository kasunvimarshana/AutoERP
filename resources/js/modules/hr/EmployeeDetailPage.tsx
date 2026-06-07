import { lazy, Suspense, useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { Panel } from '@/shared/components/Panel';
import { Tabs } from '@/shared/components/Tabs';
import { getEmployee } from './hrApi';
import { EmployeeSummaryCard } from './components/EmployeeSummaryCard';
import type { Employee } from './hrTypes';

const EmployeeContactTab = lazy(() => import('./components/EmployeeContactTab').then((m) => ({ default: m.EmployeeContactTab })));
const EmployeeAddressTab = lazy(() => import('./components/EmployeeAddressTab').then((m) => ({ default: m.EmployeeAddressTab })));
const EmployeeDocumentTab = lazy(() => import('./components/EmployeeDocumentTab').then((m) => ({ default: m.EmployeeDocumentTab })));
const EmployeeSkillTab = lazy(() => import('./components/EmployeeSkillTab').then((m) => ({ default: m.EmployeeSkillTab })));
const EmployeeCertificationTab = lazy(() => import('./components/EmployeeCertificationTab').then((m) => ({ default: m.EmployeeCertificationTab })));
const EmployeeLicenseTab = lazy(() => import('./components/EmployeeLicenseTab').then((m) => ({ default: m.EmployeeLicenseTab })));
const EmployeeRateTab = lazy(() => import('./components/EmployeeRateTab').then((m) => ({ default: m.EmployeeRateTab })));
const EmployeeAvailabilityTab = lazy(() => import('./components/EmployeeAvailabilityTab').then((m) => ({ default: m.EmployeeAvailabilityTab })));
const EmployeeStatusHistoryTab = lazy(() => import('./components/EmployeeStatusHistoryTab').then((m) => ({ default: m.EmployeeStatusHistoryTab })));
type Tab = 'summary' | 'contacts' | 'addresses' | 'documents' | 'skills' | 'certifications' | 'licenses' | 'rates' | 'availability' | 'history';

export default function EmployeeDetailPage() {
    const id = Number(useParams().id); const [employee, setEmployee] = useState<Employee | null>(null); const [tab, setTab] = useState<Tab>('summary'); const [loading, setLoading] = useState(true); const [error, setError] = useState<ApiError | null>(null);
    useEffect(() => { const c = new AbortController(); getEmployee(id, c.signal).then(setEmployee).catch((e) => setError(toApiError(e))).finally(() => { if (!c.signal.aborted) setLoading(false); }); return () => c.abort(); }, [id]);
    if (loading) return <LoadingState label="Loading employee..." />; if (!employee) return <ErrorAlert error={error} />;
    return <div><ContentHeader title={employee.display_name} description={`${employee.employee_number} / ${employee.designation?.name ?? 'No designation'}`} actions={<Link to={`/hr/employees/${id}/edit`}><Button>Edit</Button></Link>} /><ErrorAlert error={error} /><Panel><Tabs<Tab> active={tab} onChange={setTab} tabs={[
        { id: 'summary', label: 'Summary' }, { id: 'contacts', label: 'Contacts' }, { id: 'addresses', label: 'Addresses' }, { id: 'documents', label: 'Documents' }, { id: 'skills', label: 'Skills' }, { id: 'certifications', label: 'Certifications' }, { id: 'licenses', label: 'Licenses' }, { id: 'rates', label: 'Rates' }, { id: 'availability', label: 'Availability' }, { id: 'history', label: 'Status History' },
    ]} /><div className="mt-5"><Suspense fallback={<LoadingState label="Loading tab..." />}>{tab === 'summary' && <EmployeeSummaryCard employee={employee} />}{tab === 'contacts' && <EmployeeContactTab employeeId={id} />}{tab === 'addresses' && <EmployeeAddressTab employeeId={id} />}{tab === 'documents' && <EmployeeDocumentTab employeeId={id} />}{tab === 'skills' && <EmployeeSkillTab employeeId={id} />}{tab === 'certifications' && <EmployeeCertificationTab employeeId={id} />}{tab === 'licenses' && <EmployeeLicenseTab employeeId={id} />}{tab === 'rates' && <EmployeeRateTab employeeId={id} />}{tab === 'availability' && <EmployeeAvailabilityTab employeeId={id} />}{tab === 'history' && <EmployeeStatusHistoryTab employeeId={id} />}</Suspense></div></Panel></div>;
}
