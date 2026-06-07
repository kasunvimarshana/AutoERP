import { useCallback } from 'react';
import { GenericLookupSelect } from '@/shared/components/GenericLookupSelect';
import { searchDepartments } from '../hrApi';
import type { HrDepartment } from '../hrTypes';
export function HrDepartmentSelect({ value, onChange, error }: { value: HrDepartment | null; onChange: (value: HrDepartment | null) => void; error?: string }) { const search = useCallback((q: string, s: AbortSignal) => searchDepartments(q, s), []); return <GenericLookupSelect label="Department" value={value} onChange={onChange} search={search} formatLabel={(x) => `${x.code} ${x.name}`} error={error} />; }
