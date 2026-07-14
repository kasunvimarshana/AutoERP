import { tenantWorkspaceNavigationSections } from '@/app/navigation/tenantWorkspaceNavigation';
import { DASHBOARD_PATH } from '@/app/routePaths';
import { WorkspaceLayout } from './WorkspaceLayout';

export function AppLayout() {
    return (
        <WorkspaceLayout
            sections={tenantWorkspaceNavigationSections}
            homePath={DASHBOARD_PATH}
            workspaceLabel="Business workspace"
            mode="tenant"
        />
    );
}
