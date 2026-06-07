import { useCallback } from 'react';
import { GenericLookupSelect } from '@/shared/components/GenericLookupSelect';
import { searchSkills } from '../hrApi';
import type { HrSkill } from '../hrTypes';
export function HrSkillSelect({ value, onChange, error }: { value: HrSkill | null; onChange: (value: HrSkill | null) => void; error?: string }) { const search = useCallback((q: string, s: AbortSignal) => searchSkills(q, s), []); return <GenericLookupSelect label="Skill" value={value} onChange={onChange} search={search} formatLabel={(x) => `${x.code} ${x.name}`} error={error} />; }
