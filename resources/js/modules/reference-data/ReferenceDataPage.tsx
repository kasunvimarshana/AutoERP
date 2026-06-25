import { useAuth } from '@/modules/auth/AuthProvider';
import { hasPermission } from '@/modules/auth/accessControl';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { ReferenceDataPanel } from './components/ReferenceDataPanel';
import { referenceDataPermissions } from './referenceDataPermissions';

export default function ReferenceDataPage() {
    const auth = useAuth();

    return (
        <>
            <ContentHeader
                title="Reference Data"
                description="Manage stable currencies, countries, languages, and timezones used across the application."
            />
            <ReferenceDataPanel
                canManage={hasPermission(auth, referenceDataPermissions.manage)}
            />
        </>
    );
}
