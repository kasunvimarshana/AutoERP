import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { cwd } from 'node:process';
import { describe, expect, it } from 'vitest';

const source = (path: string) => readFileSync(resolve(cwd(), path), 'utf8');

const organizationApi = source('resources/js/modules/organization-unit/organizationUnitApi.ts');
const legalProfilePanel = source('resources/js/modules/organization-unit/components/OrganizationUnitLegalProfilePanel.tsx');
const organizationDrawer = source('resources/js/modules/organization-unit/components/OrganizationUnitDetailDrawer.tsx');
const invoicePrint = source('resources/views/invoice/print.blade.php');
const rentalFactory = source('app/Modules/VehicleRental/Services/RentalFinancialDocumentDataFactory.php');
const snapshotService = source('app/Modules/Invoice/Services/InvoiceDocumentSnapshotService.php');

describe('invoice legal print foundation', () => {
    it('owns legal identity in the organization unit workspace', () => {
        expect(organizationApi).toContain('/legal-profile');
        expect(legalProfilePanel).toContain('Legal and tax profile');
        expect(legalProfilePanel).toContain('VAT registration number');
        expect(legalProfilePanel).toContain('Registered address line 1');
        expect(organizationDrawer).toContain('<OrganizationUnitLegalProfilePanel');
    });

    it('prints every required legal field from prepared document data', () => {
        for (const label of [
            'Date of Invoice:',
            "Supplier's TIN:",
            "Purchaser's TIN:",
            'Address:',
            'Telephone No:',
            'Date of Delivery / Supply:',
            'Place of Supply:',
            'Total Amount in words:',
            'Mode of Payment:',
        ]) {
            expect(invoicePrint).toContain(label);
        }
        expect(invoicePrint).toContain("$document['number_label']");
        expect(invoicePrint).toContain("$document['amount_in_words']");
        expect(invoicePrint).toContain("$document['place_of_supply']");
    });

    it('keeps rental supply and agreed payment terms with the Vehicle Rental owner', () => {
        expect(rentalFactory).toContain('supplyPeriodStart: $calculation->period_start?->toDateString()');
        expect(rentalFactory).toContain('supplyPeriodEnd: $calculation->period_end?->toDateString()');
        expect(rentalFactory).toContain('paymentMode: $this->paymentMode($paymentTermsDays)');
        expect(rentalFactory).toContain('paymentTerms: $this->paymentTerms($paymentTermsDays)');
    });

    it('fails closed rather than freezing an incomplete organization identity', () => {
        expect(snapshotService).toContain('Configure the organization unit legal and tax profile');
        expect(snapshotService).toContain('InvoiceDocumentKind::OwnerPayableVoucher');
        expect(snapshotService).toContain("'place_of_supply' => $this->nullableString($data->placeOfSupply)");
    });
});
