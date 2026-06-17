import { GenericLookupSelect } from '@/shared/components/GenericLookupSelect';
import { searchSkills } from '../hrApi';
import type { HrSkill } from '../hrTypes';

export function HrSkillSelect({ value, onChange, error }: {
    value: HrSkill | null;
    onChange: (value: HrSkill | null) => void;
    error?: string;
}) {
    return (
        <GenericLookupSelect
            label="Skill"
            value={value}
            onChange={onChange}
            search={searchSkills}
            formatLabel={(skill) => `${skill.code} ${skill.name}`}
            error={error}
            loadOnOpen
            minSearchLength={0}
        />
    );
}
