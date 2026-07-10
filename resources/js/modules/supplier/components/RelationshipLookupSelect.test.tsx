import { render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';
import { CustomerLookupSelect } from '@/modules/customer/components/CustomerLookupSelect';
import type { CustomerSummary } from '@/modules/customer/customerTypes';
import type { SupplierSummary } from '../supplierTypes';
import { SupplierLookupSelect } from './SupplierLookupSelect';

vi.mock('@/shared/components/GenericLookupSelect', () => ({
    GenericLookupSelect: ({
        label,
        value,
        formatLabel,
    }: {
        label: string;
        value: CustomerSummary | SupplierSummary | null;
        formatLabel: (value: CustomerSummary | SupplierSummary) => string;
    }) => (
        <div>
            <span>{label}</span>
            <output>{value ? formatLabel(value) : ''}</output>
        </div>
    ),
}));

describe('relationship lookup labels', () => {
    it('formats suppliers without a leading separator when code is missing', () => {
        render(
            <SupplierLookupSelect
                value={supplierWithoutCode()}
                onChange={() => undefined}
            />,
        );

        expect(screen.getByText('No Code Supplier')).toBeInTheDocument();
        expect(screen.queryByText(' - No Code Supplier')).not.toBeInTheDocument();
    });

    it('formats customers without a leading separator when code is missing', () => {
        render(
            <CustomerLookupSelect
                value={customerWithoutCode()}
                onChange={() => undefined}
            />,
        );

        expect(screen.getByText('No Code Customer')).toBeInTheDocument();
        expect(screen.queryByText(' - No Code Customer')).not.toBeInTheDocument();
    });
});

function supplierWithoutCode(): SupplierSummary {
    return {
        id: 1,
        row_version: 1,
        supplier_number: 'SUP-1',
        code: '',
        name: 'No Code Supplier',
        supplier_type: 'company',
        status: 'active',
        is_credit_allowed: true,
        is_advance_allowed: false,
    };
}

function customerWithoutCode(): CustomerSummary {
    return {
        id: 2,
        row_version: 1,
        customer_number: 'CUS-2',
        code: '',
        name: 'No Code Customer',
        customer_type: 'company',
        status: 'active',
        is_credit_allowed: true,
        is_advance_allowed: false,
        is_tax_exempt: false,
        marketing_consent: false,
    };
}
