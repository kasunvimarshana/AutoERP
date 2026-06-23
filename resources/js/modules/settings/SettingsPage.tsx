import { useAuth } from '@/modules/auth/AuthProvider';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { ConfigurationSettingsPanel } from './components/ConfigurationSettingsPanel';

export default function SettingsPage({ mode = 'tenant' }: { mode?: 'tenant' | 'platform' }) {
    const auth = useAuth();

    return (
        <>
            <ContentHeader
                title={mode === 'platform' ? 'Platform defaults' : 'Configuration'}
                description={mode === 'platform'
                    ? 'Manage global defaults used only when a tenant or organization unit has no override.'
                    : 'Manage approved tenant and organization-unit settings through guided, validated overrides.'}
            />
            <ConfigurationSettingsPanel
                permissions={auth.permissions}
                hasOrganizationUnit={mode === 'tenant' && Boolean(auth.organizationUnit?.id)}
                mode={mode}
            />
        </>
    );
}
