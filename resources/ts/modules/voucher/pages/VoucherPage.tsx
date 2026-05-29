import { ModulePlaceholderPage } from '../../../shared/components/business/ModulePlaceholderPage';

export function VoucherPage() {
    return (
        <ModulePlaceholderPage
            actions={[{ label: 'New Voucher', path: '/vouchers/new', variant: 'primary' }]}
            description="Voucher types, vouchers, lines, allocations, approval workflow, posting previews, and reversals. Backend owns debit/credit validation, approval transitions, postings, and reversal effects."
            sections={[
                { description: 'Voucher drafts, posted vouchers, and source references.', label: 'Vouchers', path: '/vouchers', status: 'Ready' },
                { description: 'Type setup and permitted line behavior.', label: 'Voucher Types', path: '/vouchers', status: 'Mocked' },
                { description: 'Approval workflow and backend posting preview.', label: 'Approvals / Posting Preview', path: '/vouchers/new', status: 'Mocked' },
            ]}
            title="Vouchers"
        />
    );
}
