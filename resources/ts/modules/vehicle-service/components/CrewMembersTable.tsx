import { DataTable } from '../../../shared/components/data/DataTable';
import type { CrewMember } from '../types/vehicleService.types';

type CrewMembersTableProps = {
    members: CrewMember[];
};

export function CrewMembersTable({ members }: CrewMembersTableProps) {
    return (
        <DataTable
            dark
            columns={[
                { header: 'Crew ID', key: 'crewId' },
                { header: 'Crew Name', key: 'name' },
                { header: 'Allow', key: 'allow' },
            ]}
            getRowKey={(row) => row.crewId}
            rows={members}
        />
    );
}
