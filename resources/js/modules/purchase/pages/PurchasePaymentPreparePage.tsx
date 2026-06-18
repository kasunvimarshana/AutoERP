import { PurchasePaymentCreateForm } from '../components/PurchasePaymentCreateForm';

export default function PurchasePaymentPreparePage() {
    return (
        <div className="space-y-5">
            <header>
                <h1 className="text-2xl font-semibold text-slate-950">Prepare Supplier Payment</h1>
                <p className="text-sm text-slate-500">Preview allocations and balances before creating a payment.</p>
            </header>
            <PurchasePaymentCreateForm mode="prepare" />
        </div>
    );
}
