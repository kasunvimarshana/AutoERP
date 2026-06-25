import { useAuth } from '@/modules/auth/AuthProvider';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { ConfigurationSettingsPanel } from './components/ConfigurationSettingsPanel';

export default function PlatformDefaultsPage() {
    const auth = useAuth();

    return (
        <>
            <ContentHeader
                title="Platform defaults"
                description="Govern platform-wide fallback settings with protected values, optimistic concurrency, and immutable history."
            />
            <ConfigurationSettingsPanel
                mode="platform"
                permissions={auth.permissions}
                hasOrganizationUnit={false}
            />
        </>
    );
}
