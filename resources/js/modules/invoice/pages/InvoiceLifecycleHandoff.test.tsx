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
        expect(detail).toContain("await cancelInvoice(id, value.row_version, reason ?? '')");
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

    it('hands posted vehicle service invoices back to the vehicle-service payment workflow', () => {
        const detail = sourceFile('resources/js/modules/invoice/pages/InvoiceDetailPage.tsx');
        const invoiceCreate = sourceFile('resources/js/modules/vehicle-service/pages/VehicleServiceInvoiceCreatePage.tsx');
        const controller = sourceFile('app/Modules/Invoice/Http/Controllers/InvoiceController.php');
        const types = sourceFile('resources/js/modules/invoice/invoiceTypes.ts');

        expect(invoiceCreate).toContain('`/invoices/${invoiceId}?from=vehicle-service&job_id=${jobId}`');
        expect(controller).toContain("->with(['lines', 'sources', 'postingPlan'])");
        expect(types).toContain('source_id: number;');
        expect(types).toContain('sources?: InvoiceSource[];');
        expect(detail).toContain("const vehicleServiceSource = (value.sources ?? []).find((source) => source.source_type === 'vehicle_service_job');");
        expect(detail).toContain("hasPermission(auth, vehicleServicePermissions.paymentsCreate)");
        expect(detail).toContain("const isServiceInvoice = value.invoice_type === 'service' && value.direction === 'outbound';");
        expect(detail).toContain('const canSettleServiceInvoice = hasVehicleServiceJobContext');
        expect(detail).toContain('Pay this invoice');
        expect(detail).toContain('to={`/vehicle-service/jobs/${vehicleServiceJobId}/payment`}');
    });
});
