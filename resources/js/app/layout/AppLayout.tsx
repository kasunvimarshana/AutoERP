import { tenantNavigationSections } from '@/app/navigation/navigationConfig';
import { DASHBOARD_PATH } from '@/app/routePaths';
import { WorkspaceLayout } from './WorkspaceLayout';

export function AppLayout() {
    return (
        <WorkspaceLayout
            sections={tenantNavigationSections}
            homePath={DASHBOARD_PATH}
            workspaceLabel="Business workspace"
            mode="tenant"
        />
    );
}
