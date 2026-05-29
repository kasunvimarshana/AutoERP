import { AssignedSubItemsTable } from './AssignedSubItemsTable';
import { CrewMembersTable } from './CrewMembersTable';
import { SubItemForm } from './SubItemForm';
import { SubItemsTable } from './SubItemsTable';
import { Button } from '../../../shared/components/ui/Button';
import { Select } from '../../../shared/components/ui/Select';
import { Textarea } from '../../../shared/components/ui/Textarea';
import { useVehicleServiceMock } from '../hooks/useVehicleServiceMock';

export function CrewAssignmentPanel() {
    const data = useVehicleServiceMock();
    const serviceOptions = data.serviceItems.map((item) => ({ label: item.label, value: item.id }));
    const supervisorOptions = data.supervisors.map((supervisor) => ({ label: supervisor.label, value: supervisor.id }));

    return (
        <div className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm md:p-8">
            <div className="grid gap-6 rounded-lg border border-slate-200 p-5 md:grid-cols-2">
                <label className="space-y-2">
                    <span className="text-sm font-semibold text-slate-600">Service Item</span>
                    <Select defaultValue="full-service" options={serviceOptions} />
                </label>
                <label className="space-y-2">
                    <span className="text-sm font-semibold text-slate-600">Supervicer name</span>
                    <Select options={supervisorOptions} placeholder="Select" />
                </label>
            </div>

            <div className="mt-6 rounded-lg border border-slate-100 p-5">
                <div className="grid gap-8 lg:grid-cols-2">
                    <section className="space-y-4">
                        <h2 className="text-base font-bold text-slate-950">Sub items</h2>
                        <SubItemsTable items={data.subItems} />
                    </section>
                    <section className="space-y-4">
                        <h2 className="text-base font-bold text-slate-950">All Crew members</h2>
                        <CrewMembersTable members={data.crewMembers} />
                    </section>
                </div>

                <div className="mt-8">
                    <SubItemForm />
                </div>

                <section className="mt-10 space-y-4">
                    <h2 className="text-base font-bold text-slate-950">Assigned Sub items</h2>
                    <AssignedSubItemsTable items={data.assignedSubItems} />
                </section>

                <div className="mt-24 border-t border-slate-200 pt-5">
                    <div className="flex justify-end gap-3">
                        <Button variant="secondary">Discard</Button>
                        <Button variant="blue">Next ›</Button>
                    </div>
                </div>
            </div>

            <div className="mt-8 rounded-lg border border-slate-200 bg-white p-6">
                <h2 className="text-lg font-bold text-slate-950">🔧 Sub Item</h2>
                <div className="mt-5 grid gap-4 md:grid-cols-2">
                    <label className="space-y-2">
                        <span className="text-sm font-semibold text-slate-600">Service Item</span>
                        <Select options={serviceOptions} placeholder="Select Item" />
                    </label>
                    <label className="space-y-2">
                        <span className="text-sm font-semibold text-slate-600">Sub Item</span>
                        <Select options={data.subItems.map((item) => ({ label: item.name, value: item.crewId }))} placeholder="Select Sub Item" />
                    </label>
                </div>
                <label className="mt-5 block space-y-2">
                    <span className="text-sm font-semibold text-slate-600">Description</span>
                    <Textarea placeholder="Enter detailed service description..." />
                </label>
            </div>
        </div>
    );
}
