import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { PreviewPanel } from '../../../shared/components/business/PreviewPanel';
import { Button } from '../../../shared/components/ui/Button';
import { Card } from '../../../shared/components/ui/Card';
import { Input } from '../../../shared/components/ui/Input';
import { Select } from '../../../shared/components/ui/Select';
import { getVoucherById } from '../mock/voucherMock';
import { voucherApi } from '../services/voucherApi';
import {
    VoucherActivityTimeline,
    VoucherAllocationTable,
    VoucherApprovalPanel,
    VoucherPageHeader,
    VoucherPaymentImpactPanel,
    VoucherPostingPreviewPanel,
    VoucherSettingsForm,
    VoucherTable,
} from '../components/VoucherComponents';
import type { Voucher, VoucherAllocation, VoucherSettings } from '../types/voucher.types';

export function VoucherApprovalListPage() {
    const [rows, setRows] = useState<Voucher[]>([]);

    useEffect(() => {
        voucherApi.vouchers.list().then((response) => setRows(response.data));
    }, []);

    return (
        <div className="space-y-6">
            <VoucherPageHeader
                subtitle="Submitted and approved vouchers are shown for backend-controlled approve, reject, post, cancel, and reverse actions."
                title="Voucher Approvals"
            />
            <VoucherTable rows={rows} />
            <VoucherApprovalPanel voucher={rows[0] ?? getVoucherById('vou-001')} />
        </div>
    );
}

export function VoucherAllocationListPage() {
    const [rows, setRows] = useState<VoucherAllocation[]>([]);

    useEffect(() => {
        voucherApi.allocations.list('vou-001').then((response) => setRows(response.data));
    }, []);

    return (
        <div className="space-y-6">
            <VoucherPageHeader
                subtitle="Voucher allocations target generic documents or module records. Backend owns allocation balance and target eligibility."
                title="Voucher Allocations"
            />
            <Card className="p-4">
                <div className="grid gap-3 md:grid-cols-[1fr_180px_180px_160px]">
                    <Input placeholder="Search target reference..." />
                    <Select options={[{ label: 'Any module', value: '' }, { label: 'Payment', value: 'payment' }, { label: 'Finance', value: 'finance' }, { label: 'Generic', value: 'generic' }]} />
                    <Select options={[{ label: 'Any status', value: '' }, { label: 'Preview', value: 'preview' }, { label: 'Allocated', value: 'allocated' }, { label: 'Reversed', value: 'reversed' }]} />
                    <Button variant="secondary">Filter</Button>
                </div>
            </Card>
            <VoucherAllocationTable rows={rows} />
            <VoucherPaymentImpactPanel voucher={getVoucherById('vou-001')} />
        </div>
    );
}

export function VoucherPostingPreviewPage() {
    const voucher = getVoucherById('vou-001');

    return (
        <div className="space-y-6">
            <VoucherPageHeader
                actions={<Link to="/vouchers/new"><Button>New Voucher</Button></Link>}
                subtitle="Standalone posting preview sends voucher source or draft line input to backend. React does not balance or post journals."
                title="Voucher Posting Preview"
            />
            <Card className="p-5">
                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <Input placeholder="Voucher or source reference" />
                    <Select options={[{ label: 'Voucher draft', value: 'voucher' }, { label: 'Manual lines', value: 'manual' }, { label: 'Payment source', value: 'payment' }, { label: 'Finance source', value: 'finance' }]} />
                    <Input placeholder="Fiscal period checked by backend" />
                    <Button variant="blue">Preview Posting</Button>
                </div>
            </Card>
            <div className="grid gap-5 xl:grid-cols-2">
                <VoucherPostingPreviewPanel voucher={voucher} />
                <VoucherPaymentImpactPanel voucher={voucher} />
            </div>
            <PreviewPanel status="Validation" title="Backend Validation">
                <div className="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                    {['Voucher type active', 'Fiscal period open', 'Accounts active and postable', 'Balanced lines if required', 'Payment method valid if required', 'No cross-tenant references'].map((item) => (
                        <div className="rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm font-semibold text-slate-700" key={item}>{item}</div>
                    ))}
                </div>
            </PreviewPanel>
        </div>
    );
}

export function VoucherSettingsPage() {
    const [settings, setSettings] = useState<VoucherSettings | null>(null);

    useEffect(() => {
        voucherApi.settings.get().then((response) => setSettings(response.data));
    }, []);

    return (
        <div className="space-y-6">
            <VoucherPageHeader
                actions={<Button>Save Settings</Button>}
                subtitle="Voucher settings are module defaults. They do not encode Purchase, Sales, Service, or Rental-specific workflow."
                title="Voucher Settings"
            />
            {settings ? <VoucherSettingsForm settings={settings} /> : null}
            <VoucherActivityTimeline rows={getVoucherById('vou-001').activity} />
        </div>
    );
}
