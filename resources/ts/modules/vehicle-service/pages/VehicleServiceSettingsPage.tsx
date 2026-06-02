import { useEffect, useMemo, useState } from 'react';
import { SettingsEditor, type SettingsField, type SettingsOption } from '../../../shared/components/settings/SettingsEditor';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import { documentApi } from '../../document/services/documentApi';
import { financeApi } from '../../finance/services/financeApi';
import { inventoryApi } from '../../inventory/services/inventoryApi';
import { VehicleServicePageHeader } from '../components/VehicleServiceComponents';
import { vehicleServiceApi } from '../services/vehicleServiceApi';
import type { VehicleServiceSettings } from '../types/vehicleService.types';

async function taxGroupOptions(): Promise<SettingsOption[]> {
    const response = await financeApi.listTaxGroups({ is_active: true, per_page: 25 });
    return response.data.map((taxGroup) => ({ label: `${taxGroup.code} - ${taxGroup.name}`, value: taxGroup.id }));
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

export function VehicleServiceSettingsPage() {
    const [settings, setSettings] = useState<VehicleServiceSettings>();
    const [error, setError] = useState<Error | null>(null);
    const [isLoading, setIsLoading] = useState(true);

    async function reload() {
        setIsLoading(true);
        try {
            const response = await vehicleServiceApi.settings.get();
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
        vehicleServiceApi.settings.get()
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
            { currentLabel: settings.defaultWarehouse, key: 'default_warehouse_id', label: 'Default warehouse', loadOptions: warehouseOptions, section: 'Default mappings', type: 'select' },
            { currentLabel: settings.defaultTaxGroup, key: 'default_tax_group_id', label: 'Default tax group', loadOptions: taxGroupOptions, section: 'Default mappings', type: 'select' },
            { key: 'default_service_due_days', label: 'Default service due days', section: 'Default mappings', type: 'number' },
            { key: 'default_priority', label: 'Default priority', section: 'Default mappings', type: 'text' },
            { currentLabel: settings.documentDefinition, key: 'service_invoice_document_definition_id', label: 'Service invoice document', loadOptions: documentDefinitionOptions, section: 'Documents and sequences', type: 'select' },
            { key: 'service_refund_document_definition_id', label: 'Service refund document', loadOptions: documentDefinitionOptions, section: 'Documents and sequences', type: 'select' },
            { currentLabel: settings.jobCardSequence, key: 'job_card_sequence_code', label: 'Job card sequence', loadOptions: sequenceOptions, section: 'Documents and sequences', type: 'select' },
            { currentLabel: settings.invoiceSequence, key: 'invoice_sequence_code', label: 'Invoice sequence', loadOptions: sequenceOptions, section: 'Documents and sequences', type: 'select' },
            { key: 'service_number_prefix', label: 'Service number prefix', section: 'Documents and sequences', type: 'text' },
            { key: 'auto_invoice_trigger_status', label: 'Auto invoice trigger status', section: 'Workflow controls', type: 'text' },
            { key: 'inventory_posting_trigger_status', label: 'Inventory posting trigger status', section: 'Workflow controls', type: 'text' },
            { key: 'enable_inventory_reservation', label: 'Enable inventory reservation', section: 'Workflow controls', type: 'boolean' },
            { key: 'enable_invoice_generation', label: 'Enable invoice generation', section: 'Workflow controls', type: 'boolean' },
            { key: 'enable_payment_allocation', label: 'Enable payment allocation', section: 'Workflow controls', type: 'boolean' },
            { key: 'enable_finance_posting', label: 'Enable finance posting', section: 'Workflow controls', type: 'boolean' },
            { key: 'allow_negative_stock_for_service', label: 'Allow negative stock for service', section: 'Workflow controls', type: 'boolean' },
            { key: 'is_active', label: 'Settings active', section: 'Workflow controls', type: 'boolean' },
        ];
    }, [settings]);

    if (isLoading && !settings) {
        return <EmptyState description="Loading vehicle service settings from backend..." title="Loading settings" />;
    }

    return (
        <div className="space-y-6">
            <VehicleServicePageHeader
                subtitle="Module settings for workshop defaults, sequences, stock timing, document definitions, and integration behavior."
                title="Vehicle Service Settings"
            />
            {error ? <EmptyState description={error.message} title="Vehicle service settings failed" /> : null}
            {settings ? (
                <SettingsEditor
                    fields={fields}
                    initialValues={settings._raw ?? {}}
                    onInitialize={async () => {
                        await vehicleServiceApi.settings.initialize();
                        await reload();
                    }}
                    onSave={async (payload) => {
                        await vehicleServiceApi.settings.update(payload);
                        await reload();
                    }}
                    title="Vehicle service configuration"
                />
            ) : null}
        </div>
    );
}
