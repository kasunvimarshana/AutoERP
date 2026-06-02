import { useEffect, useMemo, useState } from 'react';
import { SettingsEditor, type SettingsField, type SettingsOption } from '../../../shared/components/settings/SettingsEditor';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import { documentApi } from '../../document/services/documentApi';
import { financeApi } from '../../finance/services/financeApi';
import { pricingApi } from '../../pricing/services/pricingApi';
import { VehicleRentalPageHeader } from '../components/VehicleRentalComponents';
import { vehicleRentalApi } from '../services/vehicleRentalApi';
import type { VehicleRentalSettings } from '../types/vehicleRental.types';

async function accountOptions(): Promise<SettingsOption[]> {
    const response = await financeApi.listAccounts({ is_active: true, per_page: 25 });
    return response.data.map((account) => ({ label: `${account.accountCode} - ${account.accountName}`, value: account.id }));
}

async function taxGroupOptions(): Promise<SettingsOption[]> {
    const response = await financeApi.listTaxGroups({ is_active: true, per_page: 25 });
    return response.data.map((taxGroup) => ({ label: `${taxGroup.code} - ${taxGroup.name}`, value: taxGroup.id }));
}

async function paymentTermOptions(): Promise<SettingsOption[]> {
    const response = await financeApi.listPaymentTerms({ is_active: true, per_page: 25 });
    return response.data.map((term) => ({ label: `${term.code} - ${term.name}`, value: term.id }));
}

async function documentDefinitionOptions(): Promise<SettingsOption[]> {
    const response = await documentApi.listDefinitions();
    return response.data.map((definition) => ({ label: `${definition.code} - ${definition.name}`, value: definition.id }));
}

async function priceListOptions(): Promise<SettingsOption[]> {
    const response = await pricingApi.listPriceLists();
    return response.data.map((priceList) => ({ label: `${priceList.code} - ${priceList.name}`, value: priceList.id }));
}

async function currencyOptions(): Promise<SettingsOption[]> {
    const response = await pricingApi.listCurrencies();
    return response.data.map((currency) => ({ label: currency.label, value: currency.id }));
}

export function VehicleRentalSettingsPage() {
    const [settings, setSettings] = useState<VehicleRentalSettings>();
    const [error, setError] = useState<Error | null>(null);
    const [isLoading, setIsLoading] = useState(true);

    async function reload() {
        setIsLoading(true);
        try {
            const response = await vehicleRentalApi.settings.get();
            setSettings(response.data);
            setError(null);
        } catch (caught) {
            setError(caught as Error);
        } finally {
            setIsLoading(false);
        }
    }

    useEffect(() => {
        let active = true;
        setIsLoading(true);
        vehicleRentalApi.settings.get()
            .then((response) => {
                if (active) {
                    setSettings(response.data);
                    setError(null);
                }
            })
            .catch((caught: Error) => {
                if (active) {
                    setError(caught);
                }
            })
            .finally(() => {
                if (active) {
                    setIsLoading(false);
                }
            });
        return () => {
            active = false;
        };
    }, []);

    const fields = useMemo<SettingsField[]>(() => {
        if (!settings) {
            return [];
        }

        return [
            { currentLabel: settings.defaultProviderPayableAccount, key: 'default_provider_payable_account_id', label: 'Provider payable account', loadOptions: accountOptions, section: 'Account mappings', type: 'select' },
            { key: 'default_rental_income_account_id', label: 'Rental income account', loadOptions: accountOptions, section: 'Account mappings', type: 'select' },
            { key: 'default_customer_receivable_account_id', label: 'Customer receivable account', loadOptions: accountOptions, section: 'Account mappings', type: 'select' },
            { key: 'default_provider_expense_account_id', label: 'Provider expense account', loadOptions: accountOptions, section: 'Account mappings', type: 'select' },
            { key: 'default_tax_account_id', label: 'Tax account', loadOptions: accountOptions, section: 'Account mappings', type: 'select' },
            { key: 'default_discount_account_id', label: 'Discount account', loadOptions: accountOptions, section: 'Account mappings', type: 'select' },
            { currentLabel: settings.defaultTaxGroup, key: 'default_tax_group_id', label: 'Default tax group', loadOptions: taxGroupOptions, section: 'Commercial defaults', type: 'select' },
            { currentLabel: settings.defaultRatePlan, key: 'default_price_list_id', label: 'Default price list', loadOptions: priceListOptions, section: 'Commercial defaults', type: 'select' },
            { key: 'default_currency_id', label: 'Default currency', loadOptions: currencyOptions, section: 'Commercial defaults', type: 'select' },
            { key: 'default_payment_term_id', label: 'Default payment term', loadOptions: paymentTermOptions, section: 'Commercial defaults', type: 'select' },
            { key: 'default_daily_hours', label: 'Default daily hours', section: 'Commercial defaults', type: 'number' },
            { key: 'default_monthly_km_limit', label: 'Monthly KM limit', section: 'Commercial defaults', type: 'number' },
            { key: 'default_extra_km_rate', label: 'Extra KM rate', section: 'Commercial defaults', type: 'number' },
            { key: 'default_extra_hour_rate', label: 'Extra hour rate', section: 'Commercial defaults', type: 'number' },
            { currentLabel: settings.invoiceDocumentDefinition, key: 'rental_invoice_document_definition_id', label: 'Rental invoice document', loadOptions: documentDefinitionOptions, section: 'Documents', type: 'select' },
            { currentLabel: settings.agreementSequence, key: 'rental_agreement_document_definition_id', label: 'Rental agreement document', loadOptions: documentDefinitionOptions, section: 'Documents', type: 'select' },
            { currentLabel: settings.runningChartSequence, key: 'running_chart_document_definition_id', label: 'Running chart document', loadOptions: documentDefinitionOptions, section: 'Documents', type: 'select' },
            { key: 'rental_replacement_document_definition_id', label: 'Replacement document', loadOptions: documentDefinitionOptions, section: 'Documents', type: 'select' },
            { key: 'allow_external_provider_vehicle', label: 'Allow external provider vehicles', section: 'Workflow controls', type: 'boolean' },
            { key: 'allow_replacement_vehicle', label: 'Allow replacement vehicles', section: 'Workflow controls', type: 'boolean' },
            { key: 'allow_without_driver', label: 'Allow without-driver rentals', section: 'Workflow controls', type: 'boolean' },
            { key: 'allow_with_driver', label: 'Allow with-driver rentals', section: 'Workflow controls', type: 'boolean' },
            { key: 'is_active', label: 'Settings active', section: 'Workflow controls', type: 'boolean' },
        ];
    }, [settings]);

    if (isLoading && !settings) {
        return <EmptyState description="Loading vehicle rental settings from backend..." title="Loading settings" />;
    }

    return (
        <div className="space-y-6">
            <VehicleRentalPageHeader
                subtitle="Rental module settings for document definitions, rate defaults, provider payable behavior, and workflow flags."
                title="Vehicle Rental Settings"
            />
            {error ? <EmptyState description={error.message} title="Vehicle rental settings failed" /> : null}
            {settings ? (
                <SettingsEditor
                    fields={fields}
                    initialValues={settings._raw ?? {}}
                    onInitialize={async () => {
                        await vehicleRentalApi.settings.initialize();
                        await reload();
                    }}
                    onSave={async (payload) => {
                        await vehicleRentalApi.settings.update(payload);
                        await reload();
                    }}
                    title="Vehicle rental configuration"
                />
            ) : null}
        </div>
    );
}
