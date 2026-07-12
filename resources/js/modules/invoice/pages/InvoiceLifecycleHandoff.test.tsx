import { readFileSync } from 'node:fs';
import { describe, expect, it } from 'vitest';

describe('invoice lifecycle handoff', () => {
    it('exposes row-version-aware invoice lifecycle commands from the detail workspace', () => {
        const api = readFileSync(new URL('../invoiceApi.ts', import.meta.url), 'utf8');
        const detail = readFileSync(new URL('./InvoiceDetailPage.tsx', import.meta.url), 'utf8');

        expect(api).toContain('expected_version: expectedVersion');
        expect(api).toContain('approveInvoice');
        expect(api).toContain('postInvoice');
        expect(api).toContain('cancelInvoice');
        expect(detail).toContain('hasInvoicePermission(auth, invoicePermissions.approve)');
        expect(detail).toContain('hasInvoicePermission(auth, invoicePermissions.post)');
        expect(detail).toContain('hasInvoicePermission(auth, invoicePermissions.cancel)');
        expect(detail).toContain('await approveInvoice(id, value.row_version)');
        expect(detail).toContain('await postInvoice(id, value.row_version)');
        expect(detail).toContain('await cancelInvoice(id, value.row_version, reason)');
    });

    it('opens the authoritative invoice immediately after rental document creation', () => {
        const billing = readFileSync(
            new URL('../../vehicle-rental/pages/RentalBillingPage.tsx', import.meta.url),
            'utf8',
        );

        expect(billing).toContain('const invoice = await createRentalInvoice(');
        expect(billing).toContain('navigate(`/invoices/${invoice.id}?from=vehicle-rental`)');
    });
});
