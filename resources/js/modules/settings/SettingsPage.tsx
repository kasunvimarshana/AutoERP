import { PLATFORM_PERMISSION } from '@/app/access/platformPermissions';
import { useAuth } from '@/modules/auth/AuthProvider';
import { hasPermission } from '@/modules/auth/accessControl';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { ConfigurationSettingsPanel } from './components/ConfigurationSettingsPanel';
import { settingsPermissions } from './settingsPermissions';

export default function SettingsPage({ mode = 'tenant' }: { mode?: 'tenant' | 'platform' }) {
    const auth = useAuth();
    const platformMode = mode === 'platform';
    const canManage = platformMode
        ? hasPermission(auth, PLATFORM_PERMISSION.configurationManage)
        : auth.permissions.includes(settingsPermissions.manageTenant)
            || auth.permissions.includes(settingsPermissions.manageOrganization);
    const canManageSensitive = platformMode
        ? hasPermission(auth, PLATFORM_PERMISSION.secretsManage)
        : auth.permissions.includes(settingsPermissions.manageSensitive);

    return (
        <>
            <ContentHeader
                title={platformMode ? 'Platform defaults' : 'Configuration'}
                description={platformMode
                    ? 'Review global defaults, understand inheritance, and manage approved overrides without exposing protected values.'
                    : 'Manage approved tenant and organization-unit settings through guided, validated overrides.'}
            />
            <ConfigurationSettingsPanel
                permissions={auth.permissions}
                hasOrganizationUnit={!platformMode && Boolean(auth.organizationUnit?.id)}
                mode={mode}
                canManageGlobal={platformMode && canManage}
                canManageSensitive={canManageSensitive}
            />
        </>
    );
}
