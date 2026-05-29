import { Button } from '../../../components/ui/Button';
import { DataTable, type DataTableColumn } from '../../../components/tables';
import { FormField } from '../../../components/forms/FormField';
import { Input } from '../../../components/ui/Input';
import { SectionCard } from '../../../components/forms/SectionCard';
import { Select } from '../../../components/ui/Select';
import type { CrewMember, SubItem } from '../types';
import { AssignedCrewTable } from './AssignedCrewTable';
import { assignedCrew, crewMembers, subItems } from '../mockData';

export function CrewAssignmentPanel() {
    const subItemColumns: DataTableColumn<SubItem>[] = [
        { key: 'crewId', header: 'CREW ID', render: (item) => item.crewId },
        { key: 'name', header: 'Crew Name', render: (item) => item.crewName },
        { key: 'allow', header: 'Allow', render: (item) => item.allow.toFixed(2) },
    ];

    const crewColumns: DataTableColumn<CrewMember>[] = [
        { key: 'id', header: 'CREW ID', render: (item) => item.id },
        { key: 'name', header: 'Crew Name', render: (item) => item.name },
        { key: 'allow', header: 'Allow', render: (item) => <span className="inline-flex rounded-full bg-slate-100 px-2 py-1 text-xs font-medium text-slate-600">${item.allow.toFixed(2)}</span> },
    ];

    return (
        <SectionCard title="Crew Assignment" description="Reference-only crew allocation screen matching the second screenshot.">
            <div className="grid gap-4 xl:grid-cols-2">
                <div className="rounded-[1.15rem] border border-slate-200/80 bg-white p-5">
                    <div className="grid gap-4 xl:grid-cols-2">
                        <FormField label="Service Item" required>
                            <Select defaultValue="Full service">
                                <option>Full service</option>
                                <option>Engine service</option>
                                <option>Brake service</option>
                            </Select>
                        </FormField>
                        <FormField label="Supervisor name" required>
                            <Select defaultValue="270">
                                <option value="">Select</option>
                                {crewMembers.map((member) => (
                                    <option key={member.id} value={member.id}>
                                        {member.name}
                                    </option>
                                ))}
                            </Select>
                        </FormField>
                    </div>
                </div>

                <div className="rounded-[1.15rem] border border-slate-200/80 bg-white p-5">
                    <div className="grid gap-4 xl:grid-cols-2">
                        <div>
                            <h4 className="mb-3 font-semibold text-slate-950">Sub items</h4>
                            <DataTable columns={subItemColumns} getRowKey={(row) => row.id} rows={subItems} />
                        </div>
                        <div>
                            <h4 className="mb-3 font-semibold text-slate-950">All Crew members</h4>
                            <DataTable columns={crewColumns} getRowKey={(row) => row.id} rows={crewMembers} />
                        </div>
                    </div>
                </div>
            </div>

            <div className="grid items-end gap-4 md:grid-cols-[0.7fr_1.4fr_1fr_auto]">
                <FormField label="Sub Item ID">
                    <Input defaultValue="271" />
                </FormField>
                <FormField label="Sub Item Name">
                    <Input defaultValue="Finishing" />
                </FormField>
                <FormField label="Price">
                    <Input defaultValue="100.00" />
                </FormField>
                <Button variant="secondary">ADD</Button>
            </div>

            <AssignedCrewTable rows={assignedCrew} />
        </SectionCard>
    );
}
