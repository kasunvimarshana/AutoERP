import { AgreementKind } from './agreements';

export interface RentalBillingPresentation {
    documentName: string;
    documentNameLower: string;
    documentDateLabel: string;
    createDraftLabel: string;
    noDocumentDueLabel: string;
    reviewDraftText: string;
}

const CUSTOMER_BILLING_PRESENTATION: RentalBillingPresentation = {
    documentName: 'Customer Invoice',
    documentNameLower: 'customer invoice',
    documentDateLabel: 'Invoice date',
    createDraftLabel: 'Create customer invoice draft',
    noDocumentDueLabel: 'No customer invoice is due.',
    reviewDraftText: 'Review, approve and post the draft in Invoice.',
};

const OWNER_BILLING_PRESENTATION: RentalBillingPresentation = {
    documentName: 'Owner Payable Voucher',
    documentNameLower: 'owner payable voucher',
    documentDateLabel: 'Owner Payable Voucher date',
    createDraftLabel: 'Create Owner Payable Voucher draft',
    noDocumentDueLabel: 'No Owner Payable Voucher is due.',
    reviewDraftText: 'Review, approve and post the draft in Invoice as an Owner Payable Voucher.',
};

export function rentalBillingPresentation(kind: AgreementKind): RentalBillingPresentation {
    return kind === AgreementKind.Customer
        ? CUSTOMER_BILLING_PRESENTATION
        : OWNER_BILLING_PRESENTATION;
}
