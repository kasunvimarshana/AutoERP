import { FormSection } from '../../../shared/components/forms/FormSection';
import { Input } from '../../../shared/components/ui/Input';
import type { CustomerFinanceDefaults } from '../types/customer.types';

export function CustomerFinanceDefaultsForm({ defaults }: { defaults: CustomerFinanceDefaults }) {
    return (
        <FormSection description="Finance defaults are selected as inputs. Backend validates account mappings, currency, and payment terms." title="Finance Defaults">
            <div className="grid gap-4 md:grid-cols-3">
                <Input defaultValue={defaults.arAccount} placeholder="AR account" />
                <Input defaultValue={defaults.revenueAccount} placeholder="Revenue account" />
                <Input defaultValue={defaults.costCenter} placeholder="Cost center" />
                <Input defaultValue={defaults.currency} placeholder="Currency" />
                <Input defaultValue={defaults.paymentTerm} placeholder="Payment term" />
            </div>
        </FormSection>
    );
}
