import { useMemo, useState } from 'react';
import { useAuth } from '@/modules/auth/AuthProvider';
import { hasPermission } from '@/modules/auth/accessControl';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { TabPanel, Tabs, type TabItem } from '@/shared/components/Tabs';
import { TenantDocumentsPanel } from './components/TenantDocumentsPanel';
import { TenantDomainsPanel } from './components/TenantDomainsPanel';
import { TenantProfilePanel } from './components/TenantProfilePanel';
import { tenantPermissions } from './tenantPermissions';

type TenantTab = 'profile' | 'domains' | 'documents';

export default function TenantWorkspacePage() {
    const auth = useAuth();
    const availableTabs = useMemo<TabItem<TenantTab>[]>(() => {
        const tabs: TabItem<TenantTab>[] = [];
        if (hasPermission(auth, tenantPermissions.profileView)) tabs.push({ id: 'profile', label: 'Profile' });
        if (hasPermission(auth, tenantPermissions.domainsView)) tabs.push({ id: 'domains', label: 'Verified domains' });
        if (hasPermission(auth, tenantPermissions.documentsView)) tabs.push({ id: 'documents', label: 'Private documents' });
        return tabs;
    }, [auth.permissions]);
    const [selectedTab, setSelectedTab] = useState<TenantTab>('profile');
    const activeTab = availableTabs.some((tab) => tab.id === selectedTab) ? selectedTab : availableTabs[0]?.id ?? 'profile';

    return (
        <>
            <ContentHeader
                title="Tenant administration"
                description="Manage the active tenant identity, verified domains, and private documents. Runtime settings remain in Configuration and inherit global defaults safely."
            />
            <div className="space-y-5">
                <Tabs id="tenant-workspace" tabs={availableTabs} active={activeTab} onChange={setSelectedTab} />
                <TabPanel tabsId="tenant-workspace" tabId="profile" active={activeTab}>
                    <TenantProfilePanel
                        canManage={hasPermission(auth, tenantPermissions.profileManage)}
                        canManageCrossOrg={hasPermission(auth, tenantPermissions.crossOrgPolicyManage)}
                    />
                </TabPanel>
                <TabPanel tabsId="tenant-workspace" tabId="domains" active={activeTab}>
                    <TenantDomainsPanel canManage={hasPermission(auth, tenantPermissions.domainsManage)} />
                </TabPanel>
                <TabPanel tabsId="tenant-workspace" tabId="documents" active={activeTab}>
                    <TenantDocumentsPanel canManage={hasPermission(auth, tenantPermissions.documentsManage)} />
                </TabPanel>
            </div>
        </>
    );
}
