import { hrNavigationItem } from './hrNavigation';
import { tenantNavigationSections as baseTenantNavigationSections } from './navigationConfig';
import type { NavigationSection } from './navigationTypes';

const OPERATIONS_SECTION_ID = 'operations';

export const tenantWorkspaceNavigationSections: NavigationSection[] = baseTenantNavigationSections.map((section) => (
    section.id === OPERATIONS_SECTION_ID
        ? { ...section, items: [...section.items, hrNavigationItem] }
        : section
));
