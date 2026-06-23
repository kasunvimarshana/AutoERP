import { useMemo, useState, type ChangeEvent } from 'react';
import { fieldError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { Panel } from '@/shared/components/Panel';
import { Select } from '@/shared/components/Select';
import { Tabs } from '@/shared/components/Tabs';
import { Textarea } from '@/shared/components/Textarea';
import type {
    Employee, EmployeeAddressPayload, EmployeeAvailabilityPayload, EmployeeCertificationPayload, EmployeeContactPayload,
    EmployeeDocumentPayload, EmployeeLicensePayload, EmployeePayload, EmployeeRatePayload, EmployeeRelationsPayload,
    EmployeeSkillPayload, EmployeeSummary, HrCertification, HrDepartment, HrDesignation, HrEmploymentType, HrLicense, HrSkill,
} from '../hrTypes';
import { EmployeeLookupSelect } from './EmployeeLookupSelect';
import { HrCertificationSelect } from './HrCertificationSelect';
import { HrDepartmentSelect } from './HrDepartmentSelect';
import { HrDesignationSelect } from './HrDesignationSelect';
import { HrEmploymentTypeSelect } from './HrEmploymentTypeSelect';
import { HrLicenseSelect } from './HrLicenseSelect';
import { HrSkillSelect } from './HrSkillSelect';

type Tab = 'basic' | 'contacts' | 'addresses' | 'documents' | 'skills' | 'certifications' | 'licenses' | 'rates' | 'availability' | 'review';
const emptyRelations: EmployeeRelationsPayload = { contacts: [], addresses: [], documents: [], skills: [], certifications: [], licenses: [], rates: [] };

export function EmployeeForm({ initial, oneShot = false, submitting, error, onDirty, onSubmit }: {
    initial?: Employee | null; oneShot?: boolean; submitting?: boolean; error: ApiError | null;
    onDirty?: () => void;
    onSubmit: (employee: EmployeePayload, relations: EmployeeRelationsPayload) => Promise<void>;
}) {
    const [tab, setTab] = useState<Tab>('basic');
    const [department, setDepartment] = useState<HrDepartment | null>(initial?.department ?? null);
    const [designation, setDesignation] = useState<HrDesignation | null>(initial?.designation ?? null);
    const [employmentType, setEmploymentType] = useState<HrEmploymentType | null>(initial?.employment_type ?? null);
    const [manager, setManager] = useState<EmployeeSummary | null>(initial?.reporting_manager ?? null);
    const [payload, setPayload] = useState<EmployeePayload>({
        employee_number: initial?.employee_number ?? '', code: initial?.code ?? '', first_name: initial?.first_name ?? '',
        middle_name: initial?.middle_name ?? '', last_name: initial?.last_name ?? '', display_name: initial?.display_name ?? '',
        email: initial?.email ?? '', phone: initial?.phone ?? '', mobile: initial?.mobile ?? '', joined_date: initial?.joined_date ?? '',
        resigned_date: initial?.resigned_date ?? '', date_of_birth: initial?.date_of_birth ?? '', gender: initial?.gender ?? 'not_specified',
        status: initial?.status ?? 'pending_approval', availability_status: initial?.availability_status ?? 'available',
        default_hourly_rate: initial?.default_hourly_rate ?? '0.000000', default_daily_rate: initial?.default_daily_rate ?? '0.000000',
        default_service_rate: initial?.default_service_rate ?? '0.000000', notes: initial?.notes ?? '',
    });
    const [relations, setRelations] = useState<EmployeeRelationsPayload>(emptyRelations);
    const finalPayload = useMemo(() => ({ ...payload, department_id: department?.id ?? null, designation_id: designation?.id ?? null, employment_type_id: employmentType?.id ?? null, reporting_manager_id: manager?.id ?? null }), [department, designation, employmentType, manager, payload]);
    const updatePayload = (patch: Partial<EmployeePayload>) => {
        onDirty?.();
        setPayload((current) => ({ ...current, ...patch }));
    };
    const updateRelations = (patch: Partial<EmployeeRelationsPayload>) => {
        onDirty?.();
        setRelations((current) => ({ ...current, ...patch }));
    };
    const input = (key: keyof EmployeePayload) => ({
        value: String(payload[key] ?? ''),
        onChange: (e: ChangeEvent<HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement>) => updatePayload({ [key]: e.target.value } as Partial<EmployeePayload>),
        error: fieldError(error, key),
    });
    const disabledRelations = !oneShot;

    return <Panel><ErrorAlert error={error} /><Tabs<Tab> active={tab} onChange={setTab} tabs={[
        { id: 'basic', label: 'Basic' }, { id: 'contacts', label: 'Contacts' }, { id: 'addresses', label: 'Addresses' },
        { id: 'documents', label: 'Documents' }, { id: 'skills', label: 'Skills' }, { id: 'certifications', label: 'Certifications' },
        { id: 'licenses', label: 'Licenses' }, { id: 'rates', label: 'Rates' }, { id: 'availability', label: 'Availability' }, { id: 'review', label: 'Review' },
    ]} /><div className="mt-5 space-y-5">
        {tab === 'basic' && <div className="grid gap-4 md:grid-cols-3">
            <Input label="Employee Number" {...input('employee_number')} disabled={Boolean(initial)} /><Input label="Code" {...input('code')} /><Input label="Display Name" {...input('display_name')} />
            <Input label="First Name" {...input('first_name')} /><Input label="Middle Name" {...input('middle_name')} /><Input label="Last Name" {...input('last_name')} />
            <Input label="Email" {...input('email')} /><Input label="Phone" {...input('phone')} /><Input label="Mobile" {...input('mobile')} />
            <HrDepartmentSelect value={department} onChange={(value) => { onDirty?.(); setDepartment(value); }} error={fieldError(error, 'department_id')} /><HrDesignationSelect value={designation} onChange={(value) => { onDirty?.(); setDesignation(value); }} error={fieldError(error, 'designation_id')} /><HrEmploymentTypeSelect value={employmentType} onChange={(value) => { onDirty?.(); setEmploymentType(value); }} error={fieldError(error, 'employment_type_id')} />
            <EmployeeLookupSelect value={manager} onChange={(value) => { onDirty?.(); setManager(value); }} excludeId={initial?.id} error={fieldError(error, 'reporting_manager_id')} />
            <Input label="Joined Date" type="date" {...input('joined_date')} /><Input label="Resigned Date" type="date" {...input('resigned_date')} /><Input label="Date of Birth" type="date" {...input('date_of_birth')} />
            <Select label="Gender" options={['male', 'female', 'other', 'not_specified'].map(option)} {...input('gender')} />
            {!initial && <Select label="Status" options={['pending_approval', 'active', 'inactive', 'on_leave', 'suspended'].map(option)} {...input('status')} />}
            <Select label="Availability" options={['available', 'assigned', 'on_leave', 'unavailable', 'suspended', 'inactive'].map(option)} {...input('availability_status')} />
            <Input label="Hourly Rate" {...input('default_hourly_rate')} /><Input label="Daily Rate" {...input('default_daily_rate')} /><Input label="Service Rate" {...input('default_service_rate')} />
            <div className="md:col-span-3"><Textarea label="Notes" {...input('notes')} /></div>
        </div>}
        {tab === 'contacts' && <ContactBuilder disabled={disabledRelations} rows={relations.contacts} setRows={(contacts) => updateRelations({ contacts })} />}
        {tab === 'addresses' && <AddressBuilder disabled={disabledRelations} rows={relations.addresses} setRows={(addresses) => updateRelations({ addresses })} />}
        {tab === 'documents' && <DocumentBuilder disabled={disabledRelations} rows={relations.documents} setRows={(documents) => updateRelations({ documents })} />}
        {tab === 'skills' && <SkillBuilder disabled={disabledRelations} rows={relations.skills} setRows={(skills) => updateRelations({ skills })} />}
        {tab === 'certifications' && <CertificationBuilder disabled={disabledRelations} rows={relations.certifications} setRows={(certifications) => updateRelations({ certifications })} />}
        {tab === 'licenses' && <LicenseBuilder disabled={disabledRelations} rows={relations.licenses} setRows={(licenses) => updateRelations({ licenses })} />}
        {tab === 'rates' && <RateBuilder disabled={disabledRelations} rows={relations.rates} setRows={(rates) => updateRelations({ rates })} />}
        {tab === 'availability' && <AvailabilityBuilder disabled={disabledRelations} value={relations.availability} setValue={(availability) => updateRelations({ availability })} />}
        {tab === 'review' && <div className="border border-slate-200 bg-slate-50 p-4 text-sm"><p className="font-semibold">{finalPayload.display_name || finalPayload.first_name || 'New employee'}</p><p>{department?.name ?? 'No department'} / {designation?.name ?? 'No designation'}</p><p className="mt-2">Relations: {relations.contacts.length} contacts, {relations.addresses.length} addresses, {relations.documents.length} documents, {relations.skills.length} skills, {relations.certifications.length} certifications, {relations.licenses.length} licenses, {relations.rates.length} rates.</p></div>}
        <div className="flex justify-end"><Button loading={submitting} onClick={() => void onSubmit(finalPayload, relations)}>{initial ? 'Save Employee' : 'Create Employee'}</Button></div>
    </div></Panel>;
}

const option = (value: string) => ({ value, label: value.replaceAll('_', ' ') });
function Unavailable({ disabled, children }: { disabled: boolean; children: React.ReactNode }) { return disabled ? <p className="border border-slate-200 bg-slate-50 p-4 text-sm text-slate-500">Manage this relation from the employee detail page after saving.</p> : <>{children}</>; }
function Count({ count }: { count: number }) { return <p className="text-sm text-slate-500">Queued: {count}</p>; }
function ContactBuilder({ disabled, rows, setRows }: { disabled: boolean; rows: EmployeeContactPayload[]; setRows: (v: EmployeeContactPayload[]) => void }) { const [d, setD] = useState<EmployeeContactPayload>({ contact_name: '', relationship: '', email: '', phone: '', mobile: '', is_emergency_contact: false, is_primary: false, is_active: true, notes: '' }); return <Unavailable disabled={disabled}><div className="grid gap-3 md:grid-cols-3"><Input label="Name" value={d.contact_name} onChange={(e) => setD({ ...d, contact_name: e.target.value })} /><Input label="Relationship" value={d.relationship ?? ''} onChange={(e) => setD({ ...d, relationship: e.target.value })} /><Input label="Mobile" value={d.mobile ?? ''} onChange={(e) => setD({ ...d, mobile: e.target.value })} /></div><Button onClick={() => { setRows([...rows, d]); setD({ ...d, contact_name: '', mobile: '' }); }}>Add Contact</Button><Count count={rows.length} /></Unavailable>; }
function AddressBuilder({ disabled, rows, setRows }: { disabled: boolean; rows: EmployeeAddressPayload[]; setRows: (v: EmployeeAddressPayload[]) => void }) { const [d, setD] = useState<EmployeeAddressPayload>({ address_type: 'current', address_line_1: '', city: '', country: '', is_primary: false, is_active: true }); return <Unavailable disabled={disabled}><div className="grid gap-3 md:grid-cols-3"><Select label="Type" value={d.address_type} options={['permanent', 'current', 'work', 'other'].map(option)} onChange={(e) => setD({ ...d, address_type: e.target.value })} /><Input label="Address" value={d.address_line_1} onChange={(e) => setD({ ...d, address_line_1: e.target.value })} /><Input label="City" value={d.city ?? ''} onChange={(e) => setD({ ...d, city: e.target.value })} /></div><Button onClick={() => { setRows([...rows, d]); setD({ ...d, address_line_1: '' }); }}>Add Address</Button><Count count={rows.length} /></Unavailable>; }
function DocumentBuilder({ disabled, rows, setRows }: { disabled: boolean; rows: EmployeeDocumentPayload[]; setRows: (v: EmployeeDocumentPayload[]) => void }) { const [d, setD] = useState<EmployeeDocumentPayload>({ document_type: 'id_document', document_number: '', status: 'pending' }); return <Unavailable disabled={disabled}><div className="grid gap-3 md:grid-cols-3"><Select label="Type" value={d.document_type} options={['id_document', 'contract', 'certification', 'license', 'medical', 'employment_letter', 'other'].map(option)} onChange={(e) => setD({ ...d, document_type: e.target.value })} /><Input label="Number" value={d.document_number ?? ''} onChange={(e) => setD({ ...d, document_number: e.target.value })} /><Input label="Expiry" type="date" value={d.expiry_date ?? ''} onChange={(e) => setD({ ...d, expiry_date: e.target.value })} /></div><Button onClick={() => { setRows([...rows, d]); setD({ ...d, document_number: '' }); }}>Add Document</Button><Count count={rows.length} /></Unavailable>; }
function SkillBuilder({ disabled, rows, setRows }: { disabled: boolean; rows: EmployeeSkillPayload[]; setRows: (v: EmployeeSkillPayload[]) => void }) { const [master, setMaster] = useState<HrSkill | null>(null); const [level, setLevel] = useState('beginner'); return <Unavailable disabled={disabled}><div className="grid gap-3 md:grid-cols-2"><HrSkillSelect value={master} onChange={setMaster} /><Select label="Proficiency" value={level} options={['beginner', 'intermediate', 'advanced', 'expert'].map(option)} onChange={(e) => setLevel(e.target.value)} /></div><Button onClick={() => { if (master) { setRows([...rows, { skill_id: master.id, proficiency_level: level, years_of_experience: '0.000000', is_primary: rows.length === 0 }]); setMaster(null); } }}>Add Skill</Button><Count count={rows.length} /></Unavailable>; }
function CertificationBuilder({ disabled, rows, setRows }: { disabled: boolean; rows: EmployeeCertificationPayload[]; setRows: (v: EmployeeCertificationPayload[]) => void }) { const [master, setMaster] = useState<HrCertification | null>(null); const [number, setNumber] = useState(''); return <Unavailable disabled={disabled}><div className="grid gap-3 md:grid-cols-2"><HrCertificationSelect value={master} onChange={setMaster} /><Input label="Certificate Number" value={number} onChange={(e) => setNumber(e.target.value)} /></div><Button onClick={() => { if (master) { setRows([...rows, { certification_id: master.id, certificate_number: number, status: 'pending' }]); setMaster(null); setNumber(''); } }}>Add Certification</Button><Count count={rows.length} /></Unavailable>; }
function LicenseBuilder({ disabled, rows, setRows }: { disabled: boolean; rows: EmployeeLicensePayload[]; setRows: (v: EmployeeLicensePayload[]) => void }) { const [master, setMaster] = useState<HrLicense | null>(null); const [number, setNumber] = useState(''); return <Unavailable disabled={disabled}><div className="grid gap-3 md:grid-cols-2"><HrLicenseSelect value={master} onChange={setMaster} /><Input label="License Number" value={number} onChange={(e) => setNumber(e.target.value)} /></div><Button onClick={() => { if (master) { setRows([...rows, { license_id: master.id, license_number: number, status: 'pending' }]); setMaster(null); setNumber(''); } }}>Add License</Button><Count count={rows.length} /></Unavailable>; }
function RateBuilder({ disabled, rows, setRows }: { disabled: boolean; rows: EmployeeRatePayload[]; setRows: (v: EmployeeRatePayload[]) => void }) { const [d, setD] = useState<EmployeeRatePayload>({ rate_type: 'hourly', amount: '0.000000', is_active: true }); return <Unavailable disabled={disabled}><div className="grid gap-3 md:grid-cols-2"><Select label="Type" value={d.rate_type} options={['hourly', 'daily', 'monthly', 'service', 'fixed', 'commission'].map(option)} onChange={(e) => setD({ ...d, rate_type: e.target.value })} /><Input label="Amount" value={d.amount} onChange={(e) => setD({ ...d, amount: e.target.value })} /></div><Button onClick={() => setRows([...rows, d])}>Add Rate</Button><Count count={rows.length} /></Unavailable>; }
function AvailabilityBuilder({ disabled, value, setValue }: { disabled: boolean; value?: EmployeeAvailabilityPayload; setValue: (v: EmployeeAvailabilityPayload) => void }) { const d = value ?? { availability_status: 'available' as const, availability_date: '' }; return <Unavailable disabled={disabled}><div className="grid gap-3 md:grid-cols-2"><Select label="Status" value={d.availability_status} options={['available', 'assigned', 'on_leave', 'unavailable', 'suspended', 'inactive'].map(option)} onChange={(e) => setValue({ ...d, availability_status: e.target.value as EmployeeAvailabilityPayload['availability_status'] })} /><Input label="Date" type="date" value={d.availability_date ?? ''} onChange={(e) => setValue({ ...d, availability_date: e.target.value })} /></div></Unavailable>; }
