import { useAuth } from '@/modules/auth/AuthProvider';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { ConfigurationSettingsPanel } from './components/ConfigurationSettingsPanel';

export default function SettingsPage() {
    const auth = useAuth();

    return (
        <>
            <ContentHeader
                title="Configuration"
                description="Manage approved runtime settings through guided, validated overrides."
            />
            <ConfigurationSettingsPanel
                permissions={auth.permissions}
                hasOrganizationUnit={Boolean(auth.organizationUnit?.id)}
            />
        </>
    );
}
