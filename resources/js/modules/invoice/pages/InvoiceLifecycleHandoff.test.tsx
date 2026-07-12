import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { describe, expect, it } from 'vitest';

function sourceFile(path: string): string {
    return readFileSync(resolve(process.cwd(), path), 'utf8');
}

describe('invoice lifecycle handoff', () => {
    it('exposes row-version-aware invoice lifecycle commands from the detail workspace', () => {
        const api = sourceFile('resources/js/modules/invoice/invoiceApi.ts');
        const detail = sourceFile('resources/js/modules/invoice/pages/InvoiceDetailPage.tsx');

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
        const billing = sourceFile('resources/js/modules/vehicle-rental/pages/RentalBillingPage.tsx');

        expect(billing).toContain('const invoice = await createRentalInvoice(');
        expect(billing).toContain('navigate(`/invoices/${invoice.id}?from=vehicle-rental`)');
    });

    it('hands posted rental invoices to the Payment-owned settlement workflow', () => {
        const detail = sourceFile('resources/js/modules/invoice/pages/InvoiceDetailPage.tsx');
        const paymentEntry = sourceFile('resources/js/modules/payment/pages/PaymentEntryPage.tsx');

        expect(detail).toContain('canCreatePayment = hasPermission(auth, paymentPermissions.create)');
        expect(detail).toContain('to={`/payments/create?invoice_id=${id}`}');
        expect(detail).toContain("value.direction === 'outbound' ? 'Receive lessee payment' : 'Pay vehicle owner'");
        expect(paymentEntry).toContain('allocations: settlementInvoice.data ? [{');
        expect(paymentEntry).toContain('allocation_method: ALLOCATION_METHOD_SPECIFIC_INVOICE');
        expect(paymentEntry).toContain('currency_id: settlementInvoice.data?.currency?.id ?? undefined');
    });
});
