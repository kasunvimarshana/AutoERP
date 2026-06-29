import { Select } from '@/shared/components/Select';
import { humanize } from '@/shared/utils/object';
import type { PaymentLifecycleFilterParams } from '../reportingTypes';

const documentStatuses = ['draft', 'submitted', 'approved', 'rejected', 'voided', 'reversed'];
const postingStatuses = ['not_posted', 'posting', 'posted', 'failed', 'reversed'];
const allocationStatuses = ['unallocated', 'partially_allocated', 'fully_allocated'];
const instrumentStatuses = [
    'pending',
    'initiated',
    'authorized',
    'captured',
    'received',
    'issued',
    'deposited',
    'cleared',
    'settled',
    'refunded',
    'bounced',
    'returned',
    'cancelled',
    'failed',
    'reversed',
];

const options = (values: string[]) => values.map((value) => ({ value, label: humanize(value) }));

interface PaymentLifecycleFiltersProps {
    value: PaymentLifecycleFilterParams;
    onChange: (patch: Partial<PaymentLifecycleFilterParams>) => void;
}

export function PaymentLifecycleFilters({ value, onChange }: PaymentLifecycleFiltersProps) {
    return (
        <>
            <Select
                label="Payment document"
                value={value.payment_document_status ?? ''}
                options={options(documentStatuses)}
                onChange={(event) => onChange({ payment_document_status: event.target.value })}
            />
            <Select
                label="Finance posting"
                value={value.payment_posting_status ?? ''}
                options={options(postingStatuses)}
                onChange={(event) => onChange({ payment_posting_status: event.target.value })}
            />
            <Select
                label="Invoice allocation"
                value={value.payment_allocation_status ?? ''}
                options={options(allocationStatuses)}
                onChange={(event) => onChange({ payment_allocation_status: event.target.value })}
            />
            <Select
                label="Instrument settlement"
                value={value.payment_instrument_status ?? ''}
                options={options(instrumentStatuses)}
                onChange={(event) => onChange({ payment_instrument_status: event.target.value })}
            />
        </>
    );
}
