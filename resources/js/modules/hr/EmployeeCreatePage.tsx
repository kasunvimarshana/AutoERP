import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { Select } from '@/shared/components/Select';
import { createEmployee, createEmployeeWithRelations } from './hrApi';
import { EmployeeForm } from './components/EmployeeForm';
import type { EmployeePayload, EmployeeRelationsPayload } from './hrTypes';
export default function EmployeeCreatePage() { const navigate = useNavigate(); const [mode, setMode] = useState<'simple' | 'one-shot'>('simple'); const [submitting, setSubmitting] = useState(false); const [error, setError] = useState<ApiError | null>(null); const submit = async (employee: EmployeePayload, relations: EmployeeRelationsPayload) => { setSubmitting(true); try { const created = mode === 'one-shot' ? await createEmployeeWithRelations({ employee, ...relations }) : await createEmployee(employee); navigate(`/hr/employees/${created.id}`); } catch (e) { setError(toApiError(e)); } finally { setSubmitting(false); } }; return <div><ContentHeader title="Create Employee" description="Create workforce master data and optional related records in one transaction." /><div className="mb-4 max-w-xs"><Select label="Create Mode" value={mode} options={[{ value: 'simple', label: 'Simple employee' }, { value: 'one-shot', label: 'Employee with relations' }]} onChange={(e) => setMode(e.target.value as typeof mode)} /></div><EmployeeForm oneShot={mode === 'one-shot'} submitting={submitting} error={error} onSubmit={submit} /></div>; }
