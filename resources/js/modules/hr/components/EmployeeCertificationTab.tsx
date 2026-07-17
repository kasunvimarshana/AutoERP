import { useState } from 'react';
import { Input } from '@/shared/components/Input';
import { Select } from '@/shared/components/Select';
import { certificationApi } from '../hrApi';
import type {
    EmployeeCertificationAssignment,
    EmployeeCertificationPayload,
    HrCertification,
} from '../hrTypes';
import { EmployeeRelationTab } from './EmployeeRelationTab';
import { HrCertificationSelect } from './HrCertificationSelect';
import { useEmployeeRelationCrud } from './useEmployeeRelationCrud';

const certificationStatuses = ['active', 'expired', 'revoked', 'pending'] as const;

const emptyCertification: EmployeeCertificationPayload = {
    certification_id: 0,
    certificate_number: '',
    issued_date: '',
    expiry_date: '',
    status: 'pending',
};

function certificationDraft(row: EmployeeCertificationAssignment): EmployeeCertificationPayload {
    return {
        certification_id: row.certification_id,
        certificate_number: row.certificate_number,
        issued_date: row.issued_date,
        expiry_date: row.expiry_date,
        status: row.status,
    };
}

export function EmployeeCertificationTab({ employeeId, canManage }: { employeeId: number; canManage: boolean }) {
    const crud = useEmployeeRelationCrud(employeeId, certificationApi);
    const [master, setMaster] = useState<HrCertification | null>(null);
    const [draft, setDraft] = useState<EmployeeCertificationPayload>(emptyCertification);

    const startCreate = () => {
        setMaster(null);
        setDraft(emptyCertification);
        crud.startCreate();
    };

    const startEdit = (row: EmployeeCertificationAssignment) => {
        setMaster(row.certification ?? null);
        setDraft(certificationDraft(row));
        crud.startEdit(row);
    };

    return (
        <EmployeeRelationTab
            title="Certification"
            fields={['certification', 'certificate_number', 'expiry_date', 'status']}
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
            onSubmit={() => void crud.submit({ ...draft, certification_id: master?.id ?? 0 })}
        >
            <HrCertificationSelect value={master} onChange={setMaster} />
            <Input label="Certificate number" value={draft.certificate_number ?? ''} onChange={(event) => setDraft({ ...draft, certificate_number: event.target.value })} />
            <Input label="Issued date" type="date" value={draft.issued_date ?? ''} onChange={(event) => setDraft({ ...draft, issued_date: event.target.value })} />
            <Input label="Expiry date" type="date" value={draft.expiry_date ?? ''} onChange={(event) => setDraft({ ...draft, expiry_date: event.target.value })} />
            <Select
                label="Status"
                value={draft.status}
                options={certificationStatuses.map((value) => ({ value, label: value }))}
                onChange={(event) => setDraft({ ...draft, status: event.target.value })}
            />
        </EmployeeRelationTab>
    );
}
