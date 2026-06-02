import { useEffect, useMemo, useState } from 'react';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { SettingsEditor, type SettingsField, type SettingsOption } from '../../../shared/components/settings/SettingsEditor';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import { documentApi } from '../../document/services/documentApi';
import { financeApi } from '../../finance/services/financeApi';
import { inventoryApi } from '../../inventory/services/inventoryApi';
import { purchaseApi } from '../services/purchaseApi';
import type { PurchaseSettings } from '../types/purchase.types';

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

export function PurchaseSettingsPage() {
    const [settings, setSettings] = useState<PurchaseSettings>();
    const [error, setError] = useState<Error | null>(null);
    const [isLoading, setIsLoading] = useState(true);

    async function loadSettings() {
        setIsLoading(true);
        try {
            const response = await purchaseApi.settings.get();
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
        purchaseApi.settings.get()
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
            { currentLabel: settings.defaultPayableAccount, key: 'default_supplier_payable_account_id', label: 'Supplier payable account', loadOptions: accountOptions, section: defaultSection, type: 'select' },
            { key: 'default_purchase_account_id', label: 'Purchase account', loadOptions: accountOptions, section: defaultSection, type: 'select' },
            { key: 'default_inventory_account_id', label: 'Inventory account', loadOptions: accountOptions, section: defaultSection, type: 'select' },
            { key: 'default_purchase_tax_account_id', label: 'Purchase tax account', loadOptions: accountOptions, section: defaultSection, type: 'select' },
            { key: 'default_purchase_discount_account_id', label: 'Purchase discount account', loadOptions: accountOptions, section: defaultSection, type: 'select' },
            { currentLabel: settings.defaultPaymentTerm, key: 'default_payment_term_id', label: 'Payment term', loadOptions: paymentTermOptions, section: defaultSection, type: 'select' },
            { currentLabel: settings.defaultWarehouse, key: 'default_warehouse_id', label: 'Default warehouse', loadOptions: warehouseOptions, section: defaultSection, type: 'select' },
            { currentLabel: settings.defaultTaxGroup, key: 'default_tax_group_id', label: 'Default tax group', loadOptions: taxGroupOptions, section: defaultSection, type: 'select' },
            { key: 'tax_calculation_level', label: 'Tax calculation level', options: taxLevels, section: defaultSection, type: 'select' },
            { key: 'header_discount_allocation_method', label: 'Header discount allocation', options: allocationMethods, section: defaultSection, type: 'select' },
            { key: 'purchase_order_document_definition_id', label: 'Purchase order document', loadOptions: documentDefinitionOptions, section: documentSection, type: 'select' },
            { key: 'grn_document_definition_id', label: 'GRN document', loadOptions: documentDefinitionOptions, section: documentSection, type: 'select' },
            { currentLabel: settings.invoiceDocumentDefinition, key: 'purchase_invoice_document_definition_id', label: 'Purchase invoice document', loadOptions: documentDefinitionOptions, section: documentSection, type: 'select' },
            { key: 'purchase_return_document_definition_id', label: 'Purchase return document', loadOptions: documentDefinitionOptions, section: documentSection, type: 'select' },
            { currentLabel: settings.poSequence, key: 'numbering_sequence_code', label: 'Numbering sequence', loadOptions: sequenceOptions, section: documentSection, type: 'select' },
            { key: 'default_po_status', label: 'Default PO status', section: documentSection, type: 'text' },
            { key: 'default_grn_status', label: 'Default GRN status', section: documentSection, type: 'text' },
            { key: 'default_document_status', label: 'Default invoice status', section: documentSection, type: 'text' },
            { key: 'default_return_status', label: 'Default return status', section: documentSection, type: 'text' },
            { key: 'require_po_before_grn', label: 'Require PO before GRN', section: booleanSection, type: 'boolean' },
            { key: 'require_grn_before_invoice', label: 'Require GRN before invoice', section: booleanSection, type: 'boolean' },
            { key: 'allow_direct_grn', label: 'Allow direct GRN', section: booleanSection, type: 'boolean' },
            { key: 'allow_direct_purchase_document', label: 'Allow direct purchase invoice', section: booleanSection, type: 'boolean' },
            { key: 'allow_return_without_original', label: 'Allow return without original', section: booleanSection, type: 'boolean' },
            { key: 'allow_negative_stock_on_return', label: 'Allow negative stock on return', section: booleanSection, type: 'boolean' },
            { key: 'allow_header_discount', label: 'Allow header discount', section: booleanSection, type: 'boolean' },
            { key: 'allow_line_discount', label: 'Allow line discount', section: booleanSection, type: 'boolean' },
            { key: 'is_active', label: 'Settings active', section: booleanSection, type: 'boolean' },
        ];
    }, [settings]);

    if (isLoading && !settings) {
        return <EmptyState description="Loading purchase settings from backend..." title="Loading settings" />;
    }

    return (
        <div className="space-y-6">
            <PageHeader
                eyebrow="Purchase"
                subtitle="Module settings for defaults, sequences, document definitions, workflow flexibility, stock timing, and invoice matching."
                title="Purchase Settings"
            />
            {error ? <EmptyState description={error.message} title="Purchase settings failed" /> : null}
            {settings ? (
                <SettingsEditor
                    fields={fields}
                    initialValues={settings._raw ?? {}}
                    onInitialize={async () => {
                        await purchaseApi.settings.initialize();
                        await loadSettings();
                    }}
                    onSave={async (payload) => {
                        await purchaseApi.settings.update(payload);
                        await loadSettings();
                    }}
                    title="Purchase configuration"
                />
            ) : null}
        </div>
    );
}
