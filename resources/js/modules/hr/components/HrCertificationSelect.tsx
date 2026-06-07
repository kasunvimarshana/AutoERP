import { useCallback } from 'react';
import { GenericLookupSelect } from '@/shared/components/GenericLookupSelect';
import { searchCertifications } from '../hrApi';
import type { HrCertification } from '../hrTypes';
export function HrCertificationSelect({ value, onChange, error }: { value: HrCertification | null; onChange: (value: HrCertification | null) => void; error?: string }) { const search = useCallback((q: string, s: AbortSignal) => searchCertifications(q, s), []); return <GenericLookupSelect label="Certification" value={value} onChange={onChange} search={search} formatLabel={(x) => `${x.code} ${x.name}`} error={error} />; }
