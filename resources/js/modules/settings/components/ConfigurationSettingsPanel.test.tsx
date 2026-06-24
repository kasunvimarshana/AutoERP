import { render, screen, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { TestRouter } from '@/test/TestRouter';
import type { ConfigurationDefinition, ConfigurationEntry } from '../settingsTypes';
import { ConfigurationSettingsPanel } from './ConfigurationSettingsPanel';

const apiMocks = vi.hoisted(() => ({
    createConfigurationEntry: vi.fn(),
    deleteConfigurationEntry: vi.fn(),
    listConfigurationDefinitions: vi.fn(),
    listConfigurationEntries: vi.fn(),
    updateConfigurationEntry: vi.fn(),
}));

vi.mock('../settingsApi', () => apiMocks);
vi.mock('@/modules/reference-data/referenceDataApi', () => ({
    listActiveReferenceRecords: vi.fn().mockResolvedValue([]),
}));

describe('ConfigurationSettingsPanel', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        apiMocks.listConfigurationDefinitions.mockResolvedValue(definitions());
        apiMocks.listConfigurationEntries.mockResolvedValue({
            data: entries(),
            meta: { current_page: 1, from: 1, last_page: 1, per_page: 25, to: 2, total: 2 },
            existing_keys: ['branding.display_name', 'mail.password'],
        });
    });

    it('keeps protected values read-only without the secret-management permission', async () => {
        const user = userEvent.setup();
        renderPanel({ canManageGlobal: true, canManageSensitive: false });

        const row = await tableRowFor('SMTP password');
        expect(within(row).getByRole('button', { name: 'Replace' })).toBeDisabled();
        expect(within(row).getByRole('button', { name: 'Remove' })).toBeDisabled();

        await user.click(screen.getByRole('button', { name: 'Add override' }));
        const setting = await screen.findByLabelText('Setting');
        expect(within(setting).queryByRole('option', { name: /Webhook signing secret/i })).not.toBeInTheDocument();
        expect(within(setting).getByRole('option', { name: /Business timezone/i })).toBeInTheDocument();
    });

    it('enables protected replacement only when the separate secret permission is present', async () => {
        renderPanel({ canManageGlobal: true, canManageSensitive: true });

        const row = await tableRowFor('SMTP password');
        expect(within(row).getByRole('button', { name: 'Replace' })).toBeEnabled();
        expect(within(row).getByRole('button', { name: 'Remove' })).toBeEnabled();
    });

    it('renders a stable read-only catalogue for platform viewers', async () => {
        renderPanel({ canManageGlobal: false, canManageSensitive: false });

        await waitFor(() => expect(apiMocks.listConfigurationEntries).toHaveBeenCalled());
        expect((await screen.findAllByText('Business display name')).length).toBeGreaterThan(0);
        expect(screen.queryByRole('button', { name: 'Add override' })).not.toBeInTheDocument();
        expect(screen.queryByRole('button', { name: 'Replace' })).not.toBeInTheDocument();
        expect(screen.queryByRole('button', { name: 'Remove' })).not.toBeInTheDocument();
    });
});

function renderPanel({ canManageGlobal, canManageSensitive }: { canManageGlobal: boolean; canManageSensitive: boolean }) {
    return render(
        <TestRouter initialEntries={['/administration/platform-configuration']}>
            <ConfigurationSettingsPanel
                permissions={[]}
                hasOrganizationUnit={false}
                mode="platform"
                canManageGlobal={canManageGlobal}
                canManageSensitive={canManageSensitive}
            />
        </TestRouter>,
    );
}

async function tableRowFor(label: string): Promise<HTMLElement> {
    const candidate = (await screen.findAllByText(label))
        .find((element) => element.closest('tr'));
    const row = candidate?.closest('tr');
    if (!(row instanceof HTMLElement)) throw new Error(`Table row for ${label} was not found.`);
    return row;
}

function definitions(): ConfigurationDefinition[] {
    return [
        definition('branding.display_name', 'Business display name'),
        definition('localization.timezone', 'Business timezone'),
        definition('mail.password', 'SMTP password', true),
        definition('webhooks.signing_secret', 'Webhook signing secret', true),
    ];
}

function definition(key: string, label: string, sensitive = false): ConfigurationDefinition {
    return {
        key,
        label,
        description: `${label} description`,
        owner: key.split('.')[0],
        value_type: 'string',
        allowed_scopes: ['global'],
        default_value: '',
        nullable: false,
        sensitive,
        runtime_mutable: true,
        options: [],
        minimum: null,
        maximum: null,
        lookup: null,
    };
}

function entries(): ConfigurationEntry[] {
    return [
        entry('branding.display_name', 'Business display name', false, 'AutoERP'),
        entry('mail.password', 'SMTP password', true, null),
    ];
}

function entry(key: string, label: string, sensitive: boolean, value: unknown): ConfigurationEntry {
    return {
        key,
        label,
        description: `${label} description`,
        owner: key.split('.')[0],
        value_type: 'string',
        scope: 'global',
        value,
        display_value: sensitive ? 'Configured (protected)' : null,
        effective_value: value,
        effective_display_value: sensitive ? 'Configured (protected)' : null,
        source_scope: 'global',
        inherited_value: sensitive ? null : 'Default value',
        inherited_display_value: sensitive ? 'Not configured' : null,
        inherited_configured: false,
        inherited_source_scope: 'default',
        inherited_uses_default: true,
        sensitive,
        runtime_mutable: true,
        row_version: 1,
        updated_at: '2026-06-24T00:00:00Z',
    };
}
