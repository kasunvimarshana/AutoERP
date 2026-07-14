import { useState } from 'react';
import { Input } from '@/shared/components/Input';
import { Select } from '@/shared/components/Select';
import { licenseApi } from '../hrApi';
import type { EmployeeLicenseAssignment, EmployeeLicensePayload, HrLicense } from '../hrTypes';
import { EmployeeRelationTab } from './EmployeeRelationTab';
import { HrLicenseSelect } from './HrLicenseSelect';
import { useEmployeeRelationCrud } from './useEmployeeRelationCrud';

const licenseStatuses = ['active', 'expired', 'revoked', 'pending'] as const;

const emptyLicense: EmployeeLicensePayload = {
    license_id: 0,
    license_number: '',
    issued_date: '',
    expiry_date: '',
    status: 'pending',
};

function licenseDraft(row: EmployeeLicenseAssignment): EmployeeLicensePayload {
    return {
        license_id: row.license_id,
        license_number: row.license_number,
        issued_date: row.issued_date,
        expiry_date: row.expiry_date,
        status: row.status,
    };
}

export function EmployeeLicenseTab({ employeeId, canManage }: { employeeId: number; canManage: boolean }) {
    const crud = useEmployeeRelationCrud(employeeId, licenseApi);
    const [master, setMaster] = useState<HrLicense | null>(null);
    const [draft, setDraft] = useState<EmployeeLicensePayload>(emptyLicense);

    const startCreate = () => {
        setMaster(null);
        setDraft(emptyLicense);
        crud.startCreate();
    };

    const startEdit = (row: EmployeeLicenseAssignment) => {
        setMaster(row.license ?? null);
        setDraft(licenseDraft(row));
        crud.startEdit(row);
    };

    return (
        <EmployeeRelationTab
            title="License"
            fields={['license', 'license_number', 'expiry_date', 'status']}
            result={crud}
            open={crud.open}
            editing={crud.editing}
            submitting={crud.submitting}
            actionError={crud.actionError}
            canManage={canManage}
            onCreate={startCreate}
            onEdit={startEdit}
            onDelete={crud.destroy}
            onClose={crud.close}
            onSubmit={() => void crud.submit({ ...draft, license_id: master?.id ?? 0 })}
        >
            <HrLicenseSelect value={master} onChange={setMaster} />
            <Input label="License number" value={draft.license_number ?? ''} onChange={(event) => setDraft({ ...draft, license_number: event.target.value })} />
            <Input label="Issued date" type="date" value={draft.issued_date ?? ''} onChange={(event) => setDraft({ ...draft, issued_date: event.target.value })} />
            <Input label="Expiry date" type="date" value={draft.expiry_date ?? ''} onChange={(event) => setDraft({ ...draft, expiry_date: event.target.value })} />
            <Select
                label="Status"
                value={draft.status}
                options={licenseStatuses.map((value) => ({ value, label: value }))}
                onChange={(event) => setDraft({ ...draft, status: event.target.value })}
            />
        </EmployeeRelationTab>
    );
}
