import { useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { getEmployee, updateEmployee } from './hrApi';
import { EmployeeForm } from './components/EmployeeForm';
import type { Employee, EmployeePayload } from './hrTypes';
export default function EmployeeEditPage() { const id = Number(useParams().id); const navigate = useNavigate(); const [employee, setEmployee] = useState<Employee | null>(null); const [loading, setLoading] = useState(true); const [submitting, setSubmitting] = useState(false); const [error, setError] = useState<ApiError | null>(null); useEffect(() => { const c = new AbortController(); getEmployee(id, c.signal).then(setEmployee).catch((e) => setError(toApiError(e))).finally(() => setLoading(false)); return () => c.abort(); }, [id]); if (loading) return <LoadingState label="Loading employee..." />; if (!employee) return <ErrorAlert error={error} />; return <div><ContentHeader title={`Edit ${employee.display_name}`} /><EmployeeForm initial={employee} submitting={submitting} error={error} onSubmit={async (payload: EmployeePayload) => { setSubmitting(true); try { await updateEmployee(id, payload); navigate(`/hr/employees/${id}`); } catch (e) { setError(toApiError(e)); } finally { setSubmitting(false); } }} /></div>; }
