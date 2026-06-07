import { useCallback } from 'react';
import { GenericLookupSelect } from '@/shared/components/GenericLookupSelect';
import { searchLicenses } from '../hrApi';
import type { HrLicense } from '../hrTypes';
export function HrLicenseSelect({ value, onChange, error }: { value: HrLicense | null; onChange: (value: HrLicense | null) => void; error?: string }) { const search = useCallback((q: string, s: AbortSignal) => searchLicenses(q, s), []); return <GenericLookupSelect label="License" value={value} onChange={onChange} search={search} formatLabel={(x) => `${x.code} ${x.name}`} error={error} />; }
