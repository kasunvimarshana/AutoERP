/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

import { useState, type FormEvent } from 'react';
import { hasPermission } from '@/modules/auth/accessControl';
import { useAuth } from '@/modules/auth/AuthProvider';
import { listInvoiceLayouts } from '@/modules/invoice/invoiceLayoutApi';
import type { InvoiceLayout } from '@/modules/invoice/invoiceLayoutTypes';
import {
    createConfigurationEntry,
    getResolvedConfiguration,
    listConfigurationEntries,
    updateConfigurationEntry,
} from '@/modules/settings/settingsApi';
import { settingsPermissions } from '@/modules/settings/settingsPermissions';
import type { ConfigurationEntry, ResolvedConfiguration } from '@/modules/settings/settingsTypes';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { Panel } from '@/shared/components/Panel';
import { Select } from '@/shared/components/Select';
import { useApi } from '@/shared/hooks/useApi';
import type { OrganizationUnitSummary } from '../organizationUnitApi';

const INVOICE_DEFAULT_LAYOUT_KEY = 'invoice.default_layout';
const INVOICE_PRINT_LAYOUT_KEY = 'invoice.default_print_layout';
const STANDARD_A4 = 'standard_a4';
const COMPACT_A5 = 'compact_a5';
const CONTINUOUS_85_BY_55 = 'continuous_8_5_by_5_5';

interface DocumentSettingsData {
    resolved: ResolvedConfiguration;
    entry: ConfigurationEntry | null;
    layouts: InvoiceLayout[];
}

export function OrganizationUnitDocumentSettingsPanel({ unit }: { unit: OrganizationUnitSummary }) {
    const auth = useAuth();
    const isCurrentUnit = auth.organizationUnit?.id === unit.id;
    const canView = hasPermission(auth, settingsPermissions.view);
    const canManage = hasPermission(auth, settingsPermissions.manageOrganization);
    const settings = useApi<DocumentSettingsData>(async (signal) => {
        const [resolvedNew, page, availableLayouts] = await Promise.all([
            getResolvedConfiguration(INVOICE_DEFAULT_LAYOUT_KEY, signal).catch(() => null),
            listConfigurationEntries('organization_unit', { owner: 'Invoice', page: 1, per_page: 20 }, signal),
            listInvoiceLayouts({ is_active: true }, signal).catch(() => [] as InvoiceLayout[]),
        ]);

        const entry = page.data.find((e) => e.key === INVOICE_DEFAULT_LAYOUT_KEY)
            ?? page.data.find((e) => e.key === INVOICE_PRINT_LAYOUT_KEY)
            ?? null;

        const resolved = resolvedNew ?? (await getResolvedConfiguration(INVOICE_PRINT_LAYOUT_KEY, signal));

        return {
            resolved,
            entry,
            layouts: availableLayouts,
        };
    }, [unit.id], canView && isCurrentUnit);

    if (!canView) return null;

    return (
        <Panel>
            <h3 className="font-semibold text-slate-900">Document settings</h3>
            <p className="mt-1 text-sm text-slate-600">
                Choose the default invoice layout template applied when invoices are printed or generated for this organization unit.
            </p>
            {!isCurrentUnit ? (
                <p className="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900">
                    Switch the active workspace to {unit.name} before viewing or changing its invoice layout.
                </p>
            ) : settings.loading && settings.data === null ? (
                <LoadingState label="Loading document settings…" />
            ) : (
                <DocumentSettingsEditor
                    key={`${unit.id}:${settings.data?.entry?.row_version ?? 'inherited'}`}
                    data={settings.data}
                    canManage={canManage && unit.lifecycle_status !== 'retired'}
                    error={settings.error}
                    onSaved={settings.setData}
                />
            )}
        </Panel>
    );
}

function DocumentSettingsEditor({
    data,
    canManage,
    error,
    onSaved,
}: {
    data: DocumentSettingsData | null;
    canManage: boolean;
    error: ApiError | null;
    onSaved: (data: DocumentSettingsData) => void;
}) {
    const effectiveValue = resolveLayoutValue(data?.resolved.value);
    const [value, setValue] = useState(effectiveValue);
    const [saving, setSaving] = useState(false);
    const [actionError, setActionError] = useState<ApiError | null>(null);

    const save = async (event: FormEvent) => {
        event.preventDefault();
        if (!data) return;
        setSaving(true);
        setActionError(null);
        try {
            const saved = data.entry
                ? await updateConfigurationEntry('organization_unit', data.entry, value)
                : await createConfigurationEntry('organization_unit', INVOICE_DEFAULT_LAYOUT_KEY, value);
            onSaved({
                ...data,
                entry: saved,
                resolved: {
                    ...data.resolved,
                    value: saved.value,
                    source_scope: 'organization_unit',
                    uses_default: false,
                    row_version: saved.row_version,
                },
            });
        } catch (caught: unknown) {
            setActionError(toApiError(caught));
        } finally {
            setSaving(false);
        }
    };

    const layoutOptions = data?.layouts && data.layouts.length > 0
        ? data.layouts.map((l) => ({
            value: l.code,
            label: `${l.name} (${l.paper_size_label} - ${l.orientation})`,
        }))
        : [
            { value: STANDARD_A4, label: 'Classic Standard (A4 Portrait)' },
            { value: COMPACT_A5, label: 'Compact Slip (A5 Portrait)' },
            { value: CONTINUOUS_85_BY_55, label: 'Continuous Paper (8.5 × 5.5 in)' },
        ];

    // Ensure the current effective value is in the options list
    if (!layoutOptions.some((opt) => opt.value === value)) {
        layoutOptions.unshift({
            value,
            label: `${value} (Current)`,
        });
    }

    return (
        <form className="mt-4 space-y-4" onSubmit={(event) => void save(event)}>
            <ErrorAlert error={actionError ?? error} />
            <Select
                label="Default invoice layout template"
                value={value}
                options={layoutOptions}
                hint="Invoices printed or downloaded in this organization unit will use this layout unless explicitly overridden."
                onChange={(event) => setValue(event.target.value)}
                disabled={!canManage || data === null}
            />
            {canManage && data && (
                <div className="flex justify-end">
                    <Button type="submit" loading={saving} disabled={value === effectiveValue}>
                        Save document settings
                    </Button>
                </div>
            )}
        </form>
    );
}

function resolveLayoutValue(value: unknown): string {
    if (typeof value === 'string' && value.trim() !== '') {
        return value.trim();
    }
    return STANDARD_A4;
}
