import { lazy, Suspense, useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { Panel } from '@/shared/components/Panel';
import { Tabs } from '@/shared/components/Tabs';
import { useOnDemandTab } from '@/shared/hooks/useOnDemandTab';
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
    const id = Number(useParams().id); const [employee, setEmployee] = useState<Employee | null>(null); const tab = useOnDemandTab<Tab>('summary'); const [loading, setLoading] = useState(true); const [error, setError] = useState<ApiError | null>(null);
    useEffect(() => {
        const controller = new AbortController();
        getEmployee(id, controller.signal)
            .then((value) => {
                if (!controller.signal.aborted) setEmployee(value);
            })
            .catch((requestError) => {
                if (!controller.signal.aborted) setError(toApiError(requestError));
            })
            .finally(() => {
                if (!controller.signal.aborted) setLoading(false);
            });
        return () => controller.abort();
    }, [id]);
    if (loading) return <LoadingState label="Loading employee..." />; if (!employee) return <ErrorAlert error={error} />;
    return <div><ContentHeader title={employee.display_name} description={`${employee.employee_number} / ${employee.designation?.name ?? 'No designation'}`} actions={<Link to={`/hr/employees/${id}/edit`}><Button>Edit</Button></Link>} /><ErrorAlert error={error} /><Panel><Tabs<Tab> active={tab.activeTab} onChange={tab.openTab} tabs={[
        { id: 'summary', label: 'Summary' }, { id: 'contacts', label: 'Contacts' }, { id: 'addresses', label: 'Addresses' }, { id: 'documents', label: 'Documents' }, { id: 'skills', label: 'Skills' }, { id: 'certifications', label: 'Certifications' }, { id: 'licenses', label: 'Licenses' }, { id: 'rates', label: 'Rates' }, { id: 'availability', label: 'Availability' }, { id: 'history', label: 'Status History' },
    ]} /><div className="mt-5"><Suspense fallback={<LoadingState label="Loading tab..." />}>{tab.activeTab === 'summary' && <EmployeeSummaryCard employee={employee} />}{tab.openedTabs.has('contacts') && <div hidden={tab.activeTab !== 'contacts'}><EmployeeContactTab employeeId={id} /></div>}{tab.openedTabs.has('addresses') && <div hidden={tab.activeTab !== 'addresses'}><EmployeeAddressTab employeeId={id} /></div>}{tab.openedTabs.has('documents') && <div hidden={tab.activeTab !== 'documents'}><EmployeeDocumentTab employeeId={id} /></div>}{tab.openedTabs.has('skills') && <div hidden={tab.activeTab !== 'skills'}><EmployeeSkillTab employeeId={id} /></div>}{tab.openedTabs.has('certifications') && <div hidden={tab.activeTab !== 'certifications'}><EmployeeCertificationTab employeeId={id} /></div>}{tab.openedTabs.has('licenses') && <div hidden={tab.activeTab !== 'licenses'}><EmployeeLicenseTab employeeId={id} /></div>}{tab.openedTabs.has('rates') && <div hidden={tab.activeTab !== 'rates'}><EmployeeRateTab employeeId={id} /></div>}{tab.openedTabs.has('availability') && <div hidden={tab.activeTab !== 'availability'}><EmployeeAvailabilityTab employeeId={id} /></div>}{tab.openedTabs.has('history') && <div hidden={tab.activeTab !== 'history'}><EmployeeStatusHistoryTab employeeId={id} /></div>}</Suspense></div></Panel></div>;
}
