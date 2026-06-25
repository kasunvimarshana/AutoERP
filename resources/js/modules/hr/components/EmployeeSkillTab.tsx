import { useEffect, useState } from 'react';
import { Input } from '@/shared/components/Input';
import { Select } from '@/shared/components/Select';
import { skillApi } from '../hrApi';
import type { EmployeeSkillPayload, HrSkill } from '../hrTypes';
import { EmployeeRelationTab } from './EmployeeRelationTab';
import { HrSkillSelect } from './HrSkillSelect';
import { useEmployeeRelationCrud } from './useEmployeeRelationCrud';
export function EmployeeSkillTab({ employeeId }: { employeeId: number }) {
    const crud = useEmployeeRelationCrud(employeeId, skillApi); const [skill, setSkill] = useState<HrSkill | null>(null); const [draft, setDraft] = useState<EmployeeSkillPayload>({ skill_id: 0, proficiency_level: 'beginner', years_of_experience: '0.000000', is_primary: false });
    useEffect(() => { const row = crud.editing; setSkill(row?.skill ?? null); setDraft(row ? { skill_id: row.skill_id, proficiency_level: row.proficiency_level, years_of_experience: row.years_of_experience, is_primary: row.is_primary } : { skill_id: 0, proficiency_level: 'beginner', years_of_experience: '0.000000', is_primary: false }); }, [crud.editing, crud.open]);
    return <EmployeeRelationTab title="Skill" fields={['skill', 'proficiency_level', 'years_of_experience', 'is_primary']} result={crud} open={crud.open} editing={crud.editing} submitting={crud.submitting} actionError={crud.actionError} onCreate={crud.startCreate} onEdit={crud.startEdit} onDelete={crud.destroy} onClose={crud.close} onSubmit={() => void crud.submit({ ...draft, skill_id: skill?.id ?? 0 })}><HrSkillSelect value={skill} onChange={setSkill} /><Select label="Proficiency" value={draft.proficiency_level} options={['beginner', 'intermediate', 'advanced', 'expert'].map((v) => ({ value: v, label: v }))} onChange={(e) => setDraft({ ...draft, proficiency_level: e.target.value })} /><Input label="Years of experience" value={draft.years_of_experience} onChange={(e) => setDraft({ ...draft, years_of_experience: e.target.value })} /><label className="flex gap-2 text-sm"><input type="checkbox" checked={draft.is_primary} onChange={(e) => setDraft({ ...draft, is_primary: e.target.checked })} />Primary skill</label></EmployeeRelationTab>;
}
