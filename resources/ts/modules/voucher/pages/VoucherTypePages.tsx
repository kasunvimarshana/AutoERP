import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { PreviewPanel } from '../../../shared/components/business/PreviewPanel';
import { Button } from '../../../shared/components/ui/Button';
import { Card } from '../../../shared/components/ui/Card';
import { Input } from '../../../shared/components/ui/Input';
import { Select } from '../../../shared/components/ui/Select';
import { getVoucherTypeById } from '../mock/voucherMock';
import { voucherApi } from '../services/voucherApi';
import { VoucherPageHeader, VoucherTypeForm, VoucherTypeSummaryCard, VoucherTypeTable } from '../components/VoucherComponents';
import type { VoucherType } from '../types/voucher.types';

export function VoucherTypeListPage() {
    const [rows, setRows] = useState<VoucherType[]>([]);

    useEffect(() => {
        voucherApi.types.list().then((response) => setRows(response.data));
    }, []);

    return (
        <div className="space-y-6">
            <VoucherPageHeader
                actions={<Link to="/vouchers/types/new"><Button>New Type</Button></Link>}
                subtitle="Voucher types define generic voucher behavior, direction, payment requirements, approval, balance validation, sequence, and document defaults."
                title="Voucher Types"
            />
            <Card className="p-4">
                <div className="grid gap-3 md:grid-cols-[1fr_180px_180px_160px]">
                    <Input placeholder="Search type code, name, category..." />
                    <Select options={[{ label: 'Any direction', value: '' }, { label: 'Payment', value: 'payment' }, { label: 'Receipt', value: 'receipt' }, { label: 'Journal', value: 'journal' }, { label: 'Adjustment', value: 'adjustment' }]} />
                    <Select options={[{ label: 'Any status', value: '' }, { label: 'Active', value: 'active' }, { label: 'Inactive', value: 'inactive' }]} />
                    <Button variant="secondary">Filter</Button>
                </div>
            </Card>
            <VoucherTypeTable rows={rows} />
        </div>
    );
}

export function VoucherTypeCreatePage() {
    return (
        <div className="space-y-6">
            <VoucherPageHeader
                actions={<><Link to="/vouchers/types"><Button variant="secondary">Cancel</Button></Link><Button>Save Type</Button></>}
                subtitle="Create a generic voucher type. Backend validates sequence, document, payment, and workflow configuration."
                title="New Voucher Type"
            />
            <VoucherTypeForm />
        </div>
    );
}

export function VoucherTypeEditPage() {
    const { id = 'vtype-001' } = useParams();
    const [type, setType] = useState<VoucherType>(getVoucherTypeById(id));

    useEffect(() => {
        voucherApi.types.get(id).then((response) => setType(response.data));
    }, [id]);

    return (
        <div className="space-y-6">
            <VoucherPageHeader
                actions={<><Link to={`/vouchers/types/${type.id}`}><Button variant="secondary">View</Button></Link><Button>Save Changes</Button></>}
                subtitle="Edit voucher type setup. Type behavior stays generic and backend-owned."
                title={`Edit ${type.name}`}
            />
            <VoucherTypeForm type={type} />
        </div>
    );
}

export function VoucherTypeDetailPage() {
    const { id = 'vtype-001' } = useParams();
    const [type, setType] = useState<VoucherType>(getVoucherTypeById(id));

    useEffect(() => {
        voucherApi.types.get(id).then((response) => setType(response.data));
    }, [id]);

    return (
        <div className="space-y-6">
            <VoucherPageHeader
                actions={<><Link to={`/vouchers/types/${type.id}/edit`}><Button>Edit</Button></Link><Button variant="secondary">Activate</Button><Button variant="danger">Deactivate</Button></>}
                subtitle="Type detail shows reusable voucher behavior without Purchase, Sales, Service, or Rental-specific workflow logic."
                title={type.name}
            />
            <div className="grid gap-5 xl:grid-cols-[1fr_360px]">
                <VoucherTypeSummaryCard type={type} />
                <PreviewPanel rows={[
                    { label: 'Sequence', value: type.defaultSequence },
                    { label: 'Document definition', value: type.defaultDocumentDefinition },
                    { label: 'Balance validation', value: type.requiresBalancedLines ? 'Backend required' : 'Backend optional' },
                    { label: 'Payment method', value: type.requiresPaymentMethod ? 'Backend required' : 'Not required' },
                ]} status="Type Behavior" title="Behavior" />
            </div>
        </div>
    );
}
