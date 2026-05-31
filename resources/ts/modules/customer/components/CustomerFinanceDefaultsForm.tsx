import { FormSection } from '../../../shared/components/forms/FormSection';
import { Input } from '../../../shared/components/ui/Input';
import type { CustomerFinanceDefaults } from '../types/customer.types';

export function CustomerFinanceDefaultsForm({ defaults }: { defaults: CustomerFinanceDefaults }) {
    return (
        <FormSection description="Readonly backend response. Account mappings, currency, and payment terms are validated by backend services." title="Finance Defaults">
            <div className="grid gap-4 md:grid-cols-3">
                <Input readOnly value={defaults.arAccount || 'Not configured'} />
                <Input readOnly value={defaults.revenueAccount || 'Not configured'} />
                <Input readOnly value={defaults.costCenter || 'Not configured'} />
                <Input readOnly value={defaults.currency || 'Not configured'} />
                <Input readOnly value={defaults.paymentTerm || 'Not configured'} />
            </div>
        </FormSection>
    );
}
