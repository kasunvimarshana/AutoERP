import { useState } from 'react';
import { Input } from '@/shared/components/Input';
import { Select } from '@/shared/components/Select';
import { skillApi } from '../hrApi';
import type { EmployeeSkillAssignment, EmployeeSkillPayload, HrSkill } from '../hrTypes';
import { EmployeeRelationTab } from './EmployeeRelationTab';
import { HrSkillSelect } from './HrSkillSelect';
import { useEmployeeRelationCrud } from './useEmployeeRelationCrud';

const proficiencyLevels = ['beginner', 'intermediate', 'advanced', 'expert'] as const;

const emptySkill: EmployeeSkillPayload = {
    skill_id: 0,
    proficiency_level: 'beginner',
    years_of_experience: '0.000000',
    is_primary: false,
};

function skillDraft(row: EmployeeSkillAssignment): EmployeeSkillPayload {
    return {
        skill_id: row.skill_id,
        proficiency_level: row.proficiency_level,
        years_of_experience: row.years_of_experience,
        is_primary: row.is_primary,
    };
}

export function EmployeeSkillTab({ employeeId }: { employeeId: number }) {
    const crud = useEmployeeRelationCrud(employeeId, skillApi);
    const [skill, setSkill] = useState<HrSkill | null>(null);
    const [draft, setDraft] = useState<EmployeeSkillPayload>(emptySkill);

    const startCreate = () => {
        setSkill(null);
        setDraft(emptySkill);
        crud.startCreate();
    };

    const startEdit = (row: EmployeeSkillAssignment) => {
        setSkill(row.skill ?? null);
        setDraft(skillDraft(row));
        crud.startEdit(row);
    };

    return (
        <EmployeeRelationTab
            title="Skill"
            fields={['skill', 'proficiency_level', 'years_of_experience', 'is_primary']}
            result={crud}
            open={crud.open}
            editing={crud.editing}
            submitting={crud.submitting}
            actionError={crud.actionError}
            onCreate={startCreate}
            onEdit={startEdit}
            onDelete={crud.destroy}
            onClose={crud.close}
            onSubmit={() => void crud.submit({ ...draft, skill_id: skill?.id ?? 0 })}
        >
            <HrSkillSelect value={skill} onChange={setSkill} />
            <Select
                label="Proficiency"
                value={draft.proficiency_level}
                options={proficiencyLevels.map((value) => ({ value, label: value }))}
                onChange={(event) => setDraft({ ...draft, proficiency_level: event.target.value })}
            />
            <Input label="Years of experience" value={draft.years_of_experience} onChange={(event) => setDraft({ ...draft, years_of_experience: event.target.value })} />
            <label className="flex gap-2 text-sm">
                <input type="checkbox" checked={draft.is_primary} onChange={(event) => setDraft({ ...draft, is_primary: event.target.checked })} />
                Primary skill
            </label>
        </EmployeeRelationTab>
    );
}
