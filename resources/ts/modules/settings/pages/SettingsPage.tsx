import { ModulePlaceholderPage } from '../../../shared/components/business/ModulePlaceholderPage';

export function SettingsPage() {
    return (
        <ModulePlaceholderPage
            description="Users, roles, permissions, organization units, application preferences, and module settings. Backend owns permission enforcement and tenant isolation."
            sections={[
                { description: 'Application and module preferences.', label: 'Settings', path: '/settings', status: 'Ready' },
                { description: 'Users, roles, and permission assignment.', label: 'Users & Permissions', path: '/settings/users', status: 'Mocked' },
                { description: 'Organization units and active context setup.', label: 'Organization Units', path: '/settings/organization-units', status: 'Mocked' },
                { description: 'Tenant and feature configuration consoles.', label: 'Configuration', path: '/configuration', status: 'Mocked' },
            ]}
            title="Settings"
        />
    );
}
