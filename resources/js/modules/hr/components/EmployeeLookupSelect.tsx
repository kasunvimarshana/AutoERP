import { useCallback } from 'react';
import { GenericLookupSelect } from '@/shared/components/GenericLookupSelect';
import { searchEmployees } from '../hrApi';
import type { EmployeeSummary } from '../hrTypes';
export function EmployeeLookupSelect({ value, onChange, excludeId, error }: { value: EmployeeSummary | null; onChange: (value: EmployeeSummary | null) => void; excludeId?: number | null; error?: string }) { const search = useCallback((q: string, s: AbortSignal) => searchEmployees(q, s), []); return <GenericLookupSelect label="Reporting Manager" value={value} onChange={onChange} search={search} excludeId={excludeId} formatLabel={(x) => `${x.employee_number} ${x.display_name}`} error={error} />; }
