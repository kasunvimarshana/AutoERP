import { PreviewPanel } from '../../../shared/components/business/PreviewPanel';
import type { SupplierTaxProfile } from '../types/supplier.types';

export function SupplierTaxProfileForm({ profile }: { profile: SupplierTaxProfile }) {
    return (
        <PreviewPanel
            rows={[
                { label: 'Tax identifier', value: profile.taxIdentifier || 'Not provided' },
                { label: 'VAT identifier', value: profile.vatIdentifier || 'Not provided' },
                { label: 'Tax type', value: profile.taxType },
                { label: 'Withholding rate', value: profile.withholdingRate },
                { label: 'Tax exempt', value: profile.isTaxExempt ? 'Yes' : 'No' },
            ]}
            status="Readonly"
            subtitle="Backend owns tax treatment and withholding behavior. This panel displays returned values only."
            title="Supplier Tax Profile"
        />
    );
}
