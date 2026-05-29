import { PreviewPanel } from '../../../shared/components/business/PreviewPanel';
import type { CustomerCreditProfile } from '../types/customer.types';

export function CustomerCreditProfilePanel({ profile }: { profile: CustomerCreditProfile }) {
    return (
        <PreviewPanel
            rows={[
                { label: 'Credit limit', value: profile.creditLimit },
                { label: 'Outstanding balance', value: profile.outstandingBalance },
                { label: 'Payment terms', value: profile.paymentTerms },
                { label: 'Credit status', value: profile.creditStatus },
                { label: 'Aging summary', value: profile.agingSummary },
            ]}
            status="Mocked"
            subtitle="Readonly mock backend preview. The frontend does not calculate balances or aging."
            title="Credit Profile"
        />
    );
}
