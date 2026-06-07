import { useCallback } from 'react';
import { GenericLookupSelect } from '@/shared/components/GenericLookupSelect';
import { searchEmploymentTypes } from '../hrApi';
import type { HrEmploymentType } from '../hrTypes';
export function HrEmploymentTypeSelect({ value, onChange, error }: { value: HrEmploymentType | null; onChange: (value: HrEmploymentType | null) => void; error?: string }) { const search = useCallback((q: string, s: AbortSignal) => searchEmploymentTypes(q, s), []); return <GenericLookupSelect label="Employment Type" value={value} onChange={onChange} search={search} formatLabel={(x) => `${x.code} ${x.name}`} error={error} />; }
