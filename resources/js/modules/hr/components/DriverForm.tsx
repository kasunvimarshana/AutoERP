import { useState, type ChangeEvent, type FormEvent } from 'react';
import { Link } from 'react-router-dom';
import { fieldError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { Panel } from '@/shared/components/Panel';
import { Select } from '@/shared/components/Select';
import { Textarea } from '@/shared/components/Textarea';
import type { DriverDetail, DriverPayload } from '../driverTypes';

interface DriverFormProps {
    initial?: DriverDetail | null;
    submitting?: boolean;
    error: ApiError | null;
    isEdit?: boolean;
    onDirty?: () => void;
    onSubmit: (payload: DriverPayload) => Promise<void>;
}

const COMMON_LICENSE_CLASSES = [
    { id: 'A', label: 'A - Motorcycles (>100cc)' },
    { id: 'A1', label: 'A1 - Light Motorcycles' },
    { id: 'B', label: 'B - Dual Purpose / Motor Cars / Light Motor Vehicles' },
    { id: 'B1', label: 'B1 - Motor Tricycles / 3-Wheelers' },
    { id: 'C', label: 'C - Motor Lorries / Heavy Motor Vehicles' },
    { id: 'C1', label: 'C1 - Light Motor Lorries' },
    { id: 'CE', label: 'CE - Heavy Combination / Articulated Vehicles' },
    { id: 'D', label: 'D - Heavy Motor Coaches / Buses' },
    { id: 'D1', label: 'D1 - Light Motor Coaches' },
    { id: 'DE', label: 'DE - Heavy Motor Coach Combinations' },
    { id: 'G1', label: 'G1 - Hand Tractors' },
    { id: 'J', label: 'J - Special Purpose (Forklifts, Cranes)' },
];

export function DriverForm({
    initial,
    submitting = false,
    error,
    isEdit = false,
    onDirty,
    onSubmit,
}: DriverFormProps) {
    const [payload, setPayload] = useState<DriverPayload>({
        first_name: initial?.first_name ?? '',
        middle_name: initial?.middle_name ?? '',
        last_name: initial?.last_name ?? '',
        display_name: initial?.display_name ?? '',
        code: initial?.code ?? '',
        employee_number: initial?.employee_number ?? '',
        email: initial?.email ?? '',
        phone: initial?.phone ?? '',
        mobile: initial?.mobile ?? '',
        date_of_birth: initial?.date_of_birth ?? '',
        gender: initial?.gender ?? 'male',
        joined_date: initial?.joined_date ?? new Date().toISOString().split('T')[0],
        status: initial?.status ?? 'active',
        availability_status: initial?.availability_status ?? 'available',
        default_daily_rate: initial?.default_daily_rate ?? '0.00',
        default_hourly_rate: initial?.default_hourly_rate ?? '0.00',
        work_start_time: initial?.work_start_time ?? '08:00',
        work_end_time: initial?.work_end_time ?? '17:00',
        standard_shift_hours: initial?.standard_shift_hours !== null && initial?.standard_shift_hours !== undefined
            ? String(initial.standard_shift_hours)
            : '9',
        nic_passport: initial?.nic_passport ?? '',
        driving_license_number: initial?.driving_license_number ?? '',
        driving_license_classes: Array.isArray(initial?.driving_license_classes) ? initial.driving_license_classes : ['B'],
        driving_license_issued: initial?.driving_license_issued ?? '',
        driving_license_expiry: initial?.driving_license_expiry ?? '',
        emergency_contact_name: initial?.emergency_contact_name ?? '',
        emergency_contact_phone: initial?.emergency_contact_phone ?? '',
        emergency_contact_relationship: initial?.emergency_contact_relationship ?? '',
        address: initial?.address ?? '',
        city: initial?.city ?? '',
        blood_group: initial?.blood_group ?? '',
        notes: initial?.notes ?? '',
    });

    const updateField = <K extends keyof DriverPayload>(key: K, value: DriverPayload[K]) => {
        onDirty?.();
        setPayload((prev) => {
            const next = { ...prev, [key]: value };
            if (key === 'first_name' || key === 'last_name') {
                const first = key === 'first_name' ? (value as string) : prev.first_name;
                const last = key === 'last_name' ? (value as string) : (prev.last_name ?? '');
                if (!prev.display_name || prev.display_name === `${prev.first_name} ${prev.last_name || ''}`.trim()) {
                    next.display_name = `${first} ${last}`.trim();
                }
            }
            return next;
        });
    };

    const toggleLicenseClass = (clsId: string) => {
        onDirty?.();
        const current = payload.driving_license_classes ?? [];
        if (current.includes(clsId)) {
            updateField('driving_license_classes', current.filter((c) => c !== clsId));
        } else {
            updateField('driving_license_classes', [...current, clsId]);
        }
    };

    const handleSubmit = async (e: FormEvent) => {
        e.preventDefault();
        await onSubmit(payload);
    };

    const strInput = (key: keyof DriverPayload) => ({
        value: String(payload[key] ?? ''),
        onChange: (e: ChangeEvent<HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement>) =>
            updateField(key, e.target.value as any),
        error: fieldError(error, key),
    });

    return (
        <form onSubmit={handleSubmit} className="space-y-6">
            <ErrorAlert error={error} />

            {/* 1. Personal & Identity Information */}
            <Panel title="Personal Information">
                <div className="grid gap-4 md:grid-cols-3">
                    <Input
                        label="First Name"
                        required
                        {...strInput('first_name')}
                        placeholder="e.g. Ruwan"
                    />
                    <Input
                        label="Middle Name"
                        {...strInput('middle_name')}
                        placeholder="Optional"
                    />
                    <Input
                        label="Last Name"
                        {...strInput('last_name')}
                        placeholder="e.g. Kumara"
                    />
                    <Input
                        label="Display / Preferred Name"
                        required
                        {...strInput('display_name')}
                        placeholder="Full professional name"
                    />
                    <Input
                        label="NIC / Passport Number"
                        required
                        {...strInput('nic_passport')}
                        placeholder="e.g. 198512345678 or N1234567"
                    />
                    <Input
                        label="Driver / Employee Code"
                        {...strInput('code')}
                        placeholder={isEdit ? 'Driver Code' : 'Auto-generated if left blank'}
                    />
                    <Input
                        label="Date of Birth"
                        type="date"
                        {...strInput('date_of_birth')}
                    />
                    <Select
                        label="Gender"
                        options={[
                            { value: 'male', label: 'Male' },
                            { value: 'female', label: 'Female' },
                            { value: 'other', label: 'Other' },
                        ]}
                        {...strInput('gender')}
                    />
                    <Input
                        label="Blood Group"
                        {...strInput('blood_group')}
                        placeholder="e.g. O+, A+, B+"
                    />
                </div>
            </Panel>

            {/* 2. Driving License & Endorsements */}
            <Panel title="Driving License & Qualifications">
                <p className="mb-4 text-xs text-slate-500">
                    Motor traffic driving license details required for legal vehicle operation and insurance compliance.
                </p>
                <div className="grid gap-4 md:grid-cols-3">
                    <Input
                        label="Driving License Number"
                        required
                        {...strInput('driving_license_number')}
                        placeholder="e.g. B1234567"
                    />
                    <Input
                        label="License Issue Date"
                        type="date"
                        {...strInput('driving_license_issued')}
                    />
                    <Input
                        label="License Expiry Date"
                        type="date"
                        required
                        {...strInput('driving_license_expiry')}
                    />
                </div>

                <div className="mt-4">
                    <label className="mb-2 block text-sm font-medium text-slate-700">
                        License Classes & Vehicle Authorizations
                    </label>
                    <div className="grid gap-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
                        {COMMON_LICENSE_CLASSES.map((cls) => {
                            const isChecked = (payload.driving_license_classes ?? []).includes(cls.id);
                            return (
                                <label
                                    key={cls.id}
                                    className={`flex items-center gap-2 rounded-lg border p-2 text-xs cursor-pointer transition ${
                                        isChecked
                                            ? 'border-blue-500 bg-blue-50 font-medium text-blue-900'
                                            : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50'
                                    }`}
                                >
                                    <input
                                        type="checkbox"
                                        checked={isChecked}
                                        onChange={() => toggleLicenseClass(cls.id)}
                                        className="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                    />
                                    <span>{cls.label}</span>
                                </label>
                            );
                        })}
                    </div>
                    {fieldError(error, 'driving_license_classes') && (
                        <p className="mt-1 text-xs text-rose-600">
                            {fieldError(error, 'driving_license_classes')}
                        </p>
                    )}
                </div>
            </Panel>

            {/* 3. Contact & Emergency Contacts */}
            <Panel title="Contact & Address Information">
                <div className="grid gap-4 md:grid-cols-3">
                    <Input
                        label="Mobile Number"
                        required
                        {...strInput('mobile')}
                        placeholder="e.g. 0771234567"
                    />
                    <Input
                        label="Alternate Phone"
                        {...strInput('phone')}
                        placeholder="e.g. 0112345678"
                    />
                    <Input
                        label="Email Address"
                        type="email"
                        {...strInput('email')}
                        placeholder="driver@example.com"
                    />
                    <div className="md:col-span-2">
                        <Input
                            label="Residential Address"
                            {...strInput('address')}
                            placeholder="Street address"
                        />
                    </div>
                    <Input
                        label="City / Town"
                        {...strInput('city')}
                        placeholder="e.g. Colombo, Kandy"
                    />
                </div>

                <div className="mt-5 border-t border-slate-100 pt-4">
                    <h4 className="mb-3 text-sm font-semibold text-slate-800">Emergency Contact Person</h4>
                    <div className="grid gap-4 md:grid-cols-3">
                        <Input
                            label="Contact Name"
                            {...strInput('emergency_contact_name')}
                            placeholder="Next of kin or relative name"
                        />
                        <Input
                            label="Relationship"
                            {...strInput('emergency_contact_relationship')}
                            placeholder="e.g. Spouse, Brother, Mother"
                        />
                        <Input
                            label="Emergency Phone"
                            {...strInput('emergency_contact_phone')}
                            placeholder="e.g. 0719876543"
                        />
                    </div>
                </div>
            </Panel>

            {/* 4. Employment, Status & Default Rates */}
            <Panel title="Employment Details & Operational Rates">
                <div className="grid gap-4 md:grid-cols-4">
                    <Input
                        label="Joined Date"
                        type="date"
                        {...strInput('joined_date')}
                    />
                    <Select
                        label="Employment Status"
                        options={[
                            { value: 'active', label: 'Active' },
                            { value: 'on_leave', label: 'On Leave' },
                            { value: 'inactive', label: 'Inactive' },
                            { value: 'suspended', label: 'Suspended' },
                            { value: 'pending_approval', label: 'Pending Approval' },
                        ]}
                        {...strInput('status')}
                    />
                    <Select
                        label="Availability"
                        options={[
                            { value: 'available', label: 'Available for Allocation' },
                            { value: 'assigned', label: 'Currently Assigned' },
                            { value: 'on_leave', label: 'On Leave' },
                            { value: 'unavailable', label: 'Unavailable' },
                        ]}
                        {...strInput('availability_status')}
                    />
                    <Input
                        label="Default Daily Allowance / Rate"
                        type="number"
                        step="0.01"
                        {...strInput('default_daily_rate')}
                        placeholder="0.00"
                    />
                    <Input
                        label="Default Hourly Overtime Rate"
                        type="number"
                        step="0.01"
                        {...strInput('default_hourly_rate')}
                        placeholder="0.00"
                    />
                </div>

                <div className="mt-5 border-t border-slate-100 pt-4">
                    <h4 className="mb-1 text-sm font-semibold text-slate-800">
                        Daily Working Schedule (Duty Start / End Times)
                    </h4>
                    <p className="mb-3 text-xs text-slate-500">
                        Default schedule used when assigning this driver to daily vehicle running charts to calculate duty hours and overtime.
                    </p>
                    <div className="grid gap-4 sm:grid-cols-3">
                        <Input
                            label="Time to Come to Work (Duty Start Time)"
                            type="time"
                            {...strInput('work_start_time')}
                            placeholder="08:00"
                        />
                        <Input
                            label="Time to Leave Work (Duty End Time)"
                            type="time"
                            {...strInput('work_end_time')}
                            placeholder="17:00"
                        />
                        <Input
                            label="Standard Shift Hours (Daily Threshold)"
                            type="number"
                            step="0.5"
                            min="1"
                            max="24"
                            {...strInput('standard_shift_hours')}
                            placeholder="e.g. 8, 9, or 12"
                        />
                    </div>
                </div>

                <div className="mt-4">
                    <Textarea
                        label="Driver Notes & Operational Instructions"
                        rows={3}
                        {...strInput('notes')}
                        placeholder="Medical restrictions, driving history notes, preferred routes, languages spoken..."
                    />
                </div>
            </Panel>

            {/* Actions */}
            <div className="flex items-center justify-end gap-3 pt-4">
                <Link
                    to={isEdit && initial ? `/hr/drivers/${initial.id}` : '/hr/drivers'}
                    className="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                >
                    Cancel
                </Link>
                <Button
                    type="submit"
                    variant="primary"
                    loading={submitting}
                >
                    {isEdit ? 'Save Driver Changes' : 'Register Driver'}
                </Button>
            </div>
        </form>
    );
}
