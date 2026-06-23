import { platformNavigationSections } from '@/app/navigation/navigationConfig';
import { PLATFORM_HOME_PATH } from '@/app/routePaths';
import { WorkspaceLayout } from './WorkspaceLayout';

export function PlatformLayout() {
    return (
        <WorkspaceLayout
            sections={platformNavigationSections}
            homePath={PLATFORM_HOME_PATH}
            workspaceLabel="Platform control plane"
            mode="platform"
        />
    );
}
