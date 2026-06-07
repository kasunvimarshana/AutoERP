import { useCallback } from 'react';
import { GenericLookupSelect } from '@/shared/components/GenericLookupSelect';
import { searchDesignations } from '../hrApi';
import type { HrDesignation } from '../hrTypes';
export function HrDesignationSelect({ value, onChange, error }: { value: HrDesignation | null; onChange: (value: HrDesignation | null) => void; error?: string }) { const search = useCallback((q: string, s: AbortSignal) => searchDesignations(q, s), []); return <GenericLookupSelect label="Designation" value={value} onChange={onChange} search={search} formatLabel={(x) => `${x.code} ${x.name}`} error={error} />; }
