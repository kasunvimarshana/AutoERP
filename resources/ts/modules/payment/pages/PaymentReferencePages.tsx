import { useEffect, useState } from 'react';
import type { ReactNode } from 'react';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { SearchFilterBar } from '../../../shared/components/data/SearchFilterBar';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import {
    AdvancePaymentPanel,
    CashRegisterPanel,
    CheckPaymentPanel,
    PaymentAllocationPanel,
    PaymentGroupsTable,
    PaymentMethodForm,
    PaymentMethodsTable,
    RefundPanel,
    WriteOffPanel,
} from '../components/PaymentComponents';
import { paymentApi } from '../services/paymentApi';
import type { AdvancePayment, CashRegister, CheckPayment, PaymentAllocation, PaymentGroup, PaymentMethod, Refund, WriteOff } from '../types/payment.types';

export function PaymentMethodListPage() {
    const [rows, setRows] = useState<PaymentMethod[]>([]);
    const load = () => { paymentApi.listPaymentMethods().then((response) => setRows(response.data)); };
    useEffect(load, []);
    return <ReferencePage subtitle="Reusable payment methods for cash, bank, card, check, online, and other channels." title="Payment Methods"><PaymentMethodForm onSaved={load} /><PaymentMethodsTable methods={rows} /></ReferencePage>;
}

export function PaymentGroupListPage() {
    const [rows, setRows] = useState<PaymentGroup[]>([]);
    useEffect(() => { paymentApi.listPaymentGroups().then((response) => setRows(response.data)); }, []);
    return <ReferencePage subtitle="Payment groups collect batch payments and deposits. Backend owns group totals and status." title="Payment Groups">{rows.length ? <PaymentGroupsTable groups={rows} /> : <EmptyState description="No groups returned yet." title="No payment groups" />}</ReferencePage>;
}

export function PaymentAllocationListPage() {
    const [rows, setRows] = useState<PaymentAllocation[]>([]);
    useEffect(() => { paymentApi.listAllocations().then((response) => setRows(response.data)); }, []);
    return <ReferencePage subtitle="Allocation workspace. Frontend requests preview and confirmation only; backend calculates allocated and remaining amounts." title="Payment Allocations"><PaymentAllocationPanel allocations={rows} /></ReferencePage>;
}

export function AdvancePaymentListPage() {
    const [rows, setRows] = useState<AdvancePayment[]>([]);
    useEffect(() => { paymentApi.listAdvancePayments().then((response) => setRows(response.data)); }, []);
    return <ReferencePage subtitle="Customer, supplier, and generic advances. Remaining amount is backend-owned." title="Advance Payments">{rows.length ? <AdvancePaymentPanel advances={rows} /> : <EmptyState description="No advances returned yet." title="No advances" />}</ReferencePage>;
}

export function RefundListPage() {
    const [rows, setRows] = useState<Refund[]>([]);
    useEffect(() => { paymentApi.listRefunds().then((response) => setRows(response.data)); }, []);
    return <ReferencePage subtitle="Refund requests and outcomes. Backend validates refundable amount and posting effects." title="Refunds"><RefundPanel refunds={rows} /></ReferencePage>;
}

export function WriteOffListPage() {
    const [rows, setRows] = useState<WriteOff[]>([]);
    useEffect(() => { paymentApi.listWriteOffs().then((response) => setRows(response.data)); }, []);
    return <ReferencePage subtitle="Write-offs are requested here; backend validates approval, accounts, and journal impact." title="Write-offs">{rows.length ? <WriteOffPanel writeOffs={rows} /> : <EmptyState description="No write-offs returned yet." title="No write-offs" />}</ReferencePage>;
}

export function CashRegisterListPage() {
    const [rows, setRows] = useState<CashRegister[]>([]);
    useEffect(() => { paymentApi.listCashRegisters().then((response) => setRows(response.data)); }, []);
    return <ReferencePage subtitle="Cash register sessions and balances. Backend owns opening/current/closing balance." title="Cash Registers">{rows.length ? <CashRegisterPanel registers={rows} /> : <EmptyState description="No cash registers returned yet." title="No cash registers" />}</ReferencePage>;
}

export function CheckListPage() {
    const [rows, setRows] = useState<CheckPayment[]>([]);
    useEffect(() => { paymentApi.listChecks().then((response) => setRows(response.data)); }, []);
    return <ReferencePage subtitle="Inbound and outbound checks with clearing state and linked payment references." title="Checks / Cheques">{rows.length ? <CheckPaymentPanel checks={rows} /> : <EmptyState description="No checks returned yet." title="No checks" />}</ReferencePage>;
}

function ReferencePage({ children, subtitle, title }: { children: ReactNode; subtitle: string; title: string }) {
    return (
        <div className="space-y-6">
            <PageHeader eyebrow="Payments" subtitle={subtitle} title={title} />
            <SearchFilterBar />
            {children}
        </div>
    );
}
