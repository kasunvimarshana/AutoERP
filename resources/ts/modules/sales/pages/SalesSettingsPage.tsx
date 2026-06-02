import { useEffect, useMemo, useState } from 'react';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { SettingsEditor, type SettingsField, type SettingsOption } from '../../../shared/components/settings/SettingsEditor';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import { documentApi } from '../../document/services/documentApi';
import { financeApi } from '../../finance/services/financeApi';
import { inventoryApi } from '../../inventory/services/inventoryApi';
import { salesApi } from '../services/salesApi';
import type { SalesSettings } from '../types/sales.types';

const booleanSection = 'Workflow controls';
const documentSection = 'Documents and sequences';
const defaultSection = 'Default mappings';

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

async function warehouseOptions(): Promise<SettingsOption[]> {
    const response = await inventoryApi.listWarehouses();
    return response.data.map((warehouse) => ({ label: warehouse.label, value: warehouse.id }));
}

async function documentDefinitionOptions(): Promise<SettingsOption[]> {
    const response = await documentApi.listDefinitions();
    return response.data.map((definition) => ({ label: `${definition.code} - ${definition.name}`, value: definition.id }));
}

async function sequenceOptions(): Promise<SettingsOption[]> {
    const response = await documentApi.listSequences();
    return response.data.map((sequence) => ({ label: `${sequence.code} - ${sequence.nextNumberPreview}`, value: sequence.code || sequence.id }));
}

const taxLevels = [
    { label: 'Header', value: 'header' },
    { label: 'Line', value: 'line' },
];

const allocationMethods = [
    { label: 'Proportional', value: 'proportional' },
    { label: 'Manual', value: 'manual' },
];

export function SalesSettingsPage() {
    const [settings, setSettings] = useState<SalesSettings>();
    const [error, setError] = useState<Error | null>(null);
    const [isLoading, setIsLoading] = useState(true);

    async function loadSettings() {
        setIsLoading(true);
        try {
            const response = await salesApi.settings.get();
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
        salesApi.settings.get()
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
            { currentLabel: settings.defaultReceivableAccount, key: 'default_receivable_account_id', label: 'Receivable account', loadOptions: accountOptions, section: defaultSection, type: 'select' },
            { currentLabel: settings.defaultIncomeAccount, key: 'default_income_account_id', label: 'Income account', loadOptions: accountOptions, section: defaultSection, type: 'select' },
            { currentLabel: settings.defaultInventoryAccount, key: 'default_inventory_account_id', label: 'Inventory account', loadOptions: accountOptions, section: defaultSection, type: 'select' },
            { currentLabel: settings.defaultCogsAccount, key: 'default_cogs_account_id', label: 'COGS account', loadOptions: accountOptions, section: defaultSection, type: 'select' },
            { key: 'default_sales_tax_account_id', label: 'Sales tax account', loadOptions: accountOptions, section: defaultSection, type: 'select' },
            { key: 'default_sales_discount_account_id', label: 'Sales discount account', loadOptions: accountOptions, section: defaultSection, type: 'select' },
            { currentLabel: settings.defaultPaymentTerm, key: 'default_payment_term_id', label: 'Payment term', loadOptions: paymentTermOptions, section: defaultSection, type: 'select' },
            { currentLabel: settings.defaultWarehouse, key: 'default_warehouse_id', label: 'Default warehouse', loadOptions: warehouseOptions, section: defaultSection, type: 'select' },
            { currentLabel: settings.defaultTaxGroup, key: 'default_tax_group_id', label: 'Default tax group', loadOptions: taxGroupOptions, section: defaultSection, type: 'select' },
            { key: 'tax_calculation_level', label: 'Tax calculation level', options: taxLevels, section: defaultSection, type: 'select' },
            { key: 'header_discount_allocation_method', label: 'Header discount allocation', options: allocationMethods, section: defaultSection, type: 'select' },
            { key: 'sales_order_document_definition_id', label: 'Sales order document', loadOptions: documentDefinitionOptions, section: documentSection, type: 'select' },
            { key: 'gdn_document_definition_id', label: 'GDN document', loadOptions: documentDefinitionOptions, section: documentSection, type: 'select' },
            { currentLabel: settings.invoiceDocumentDefinition, key: 'sales_invoice_document_definition_id', label: 'Sales invoice document', loadOptions: documentDefinitionOptions, section: documentSection, type: 'select' },
            { key: 'sales_return_document_definition_id', label: 'Sales return document', loadOptions: documentDefinitionOptions, section: documentSection, type: 'select' },
            { currentLabel: settings.salesOrderSequence, key: 'numbering_sequence_code', label: 'Numbering sequence', loadOptions: sequenceOptions, section: documentSection, type: 'select' },
            { key: 'default_sales_order_status', label: 'Default sales order status', section: documentSection, type: 'text' },
            { key: 'default_gdn_status', label: 'Default GDN status', section: documentSection, type: 'text' },
            { key: 'default_sales_invoice_status', label: 'Default invoice status', section: documentSection, type: 'text' },
            { key: 'default_sales_return_status', label: 'Default return status', section: documentSection, type: 'text' },
            { key: 'require_sales_order_before_gdn', label: 'Require order before GDN', section: booleanSection, type: 'boolean' },
            { key: 'require_gdn_before_invoice', label: 'Require GDN before invoice', section: booleanSection, type: 'boolean' },
            { key: 'allow_direct_gdn', label: 'Allow direct GDN', section: booleanSection, type: 'boolean' },
            { key: 'allow_direct_sales_invoice', label: 'Allow direct sales invoice', section: booleanSection, type: 'boolean' },
            { key: 'allow_return_without_original', label: 'Allow return without original', section: booleanSection, type: 'boolean' },
            { key: 'reserve_stock_on_order', label: 'Reserve stock on order', section: booleanSection, type: 'boolean' },
            { key: 'issue_stock_on_gdn', label: 'Issue stock on GDN', section: booleanSection, type: 'boolean' },
            { key: 'issue_stock_on_invoice', label: 'Issue stock on invoice', section: booleanSection, type: 'boolean' },
            { key: 'allow_header_discount', label: 'Allow header discount', section: booleanSection, type: 'boolean' },
            { key: 'allow_line_discount', label: 'Allow line discount', section: booleanSection, type: 'boolean' },
            { key: 'is_active', label: 'Settings active', section: booleanSection, type: 'boolean' },
        ];
    }, [settings]);

    if (isLoading && !settings) {
        return <EmptyState description="Loading sales settings from backend..." title="Loading settings" />;
    }

    return (
        <div className="space-y-6">
            <PageHeader
                eyebrow="Sales"
                subtitle="Module settings for defaults, sequences, document definitions, credit behavior, workflow flexibility, stock timing, and invoice matching."
                title="Sales Settings"
            />
            {error ? <EmptyState description={error.message} title="Sales settings failed" /> : null}
            {settings ? (
                <SettingsEditor
                    fields={fields}
                    initialValues={settings._raw ?? {}}
                    onInitialize={async () => {
                        await salesApi.settings.initialize();
                        await loadSettings();
                    }}
                    onSave={async (payload) => {
                        await salesApi.settings.update(payload);
                        await loadSettings();
                    }}
                    title="Sales configuration"
                />
            ) : null}
        </div>
    );
}
