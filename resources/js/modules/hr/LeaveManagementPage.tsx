/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

import React, { useId, useState } from 'react';
import { AccessDeniedPage } from '@/app/errors/AccessDeniedPage';
import { hasPermission } from '@/modules/auth/accessControl';
import { useAuth } from '@/modules/auth/AuthProvider';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { TabPanel, Tabs, type TabItem } from '@/shared/components/Tabs';
import { CompOffClaimsTab } from './components/leave/CompOffClaimsTab';
import { LeavePoliciesTab } from './components/leave/LeavePoliciesTab';
import { LeaveRequestsTab } from './components/leave/LeaveRequestsTab';
import { LeaveTypesTab } from './components/leave/LeaveTypesTab';
import { hrPermissions } from './hrPermissions';

type LeaveTabId = 'requests' | 'comp-off' | 'types' | 'policies';

const LEAVE_TABS: TabItem<LeaveTabId>[] = [
    { id: 'requests', label: 'Balances & Requests' },
    { id: 'comp-off', label: 'Comp-Off Claims' },
    { id: 'types', label: 'Leave Types' },
    { id: 'policies', label: 'Leave Policies' },
];

export default function LeaveManagementPage() {
    const auth = useAuth();
    const canView = hasPermission(auth, hrPermissions.leavesView);
    const canApply = hasPermission(auth, hrPermissions.leavesApply);
    const canManage = hasPermission(auth, hrPermissions.leavesManage);

    const tabsId = useId();
    const [activeTab, setActiveTab] = useState<LeaveTabId>('requests');

    if (!canView) return <AccessDeniedPage />;

    return (
        <div className="space-y-6 p-6">
            <ContentHeader
                title="Leave & Time-Off Management"
                description="Manage leave balances, applications, compensatory off claims, leave types, and leave policies."
            />

            <Tabs
                id={tabsId}
                tabs={LEAVE_TABS}
                active={activeTab}
                onChange={setActiveTab}
                ariaLabel="Leave management sections"
            />

            <TabPanel tabsId={tabsId} tabId="requests" active={activeTab}>
                <LeaveRequestsTab canApply={canApply} canManage={canManage} />
            </TabPanel>

            <TabPanel tabsId={tabsId} tabId="comp-off" active={activeTab}>
                <CompOffClaimsTab canApply={canApply} canManage={canManage} />
            </TabPanel>

            <TabPanel tabsId={tabsId} tabId="types" active={activeTab}>
                <LeaveTypesTab canManage={canManage} />
            </TabPanel>

            <TabPanel tabsId={tabsId} tabId="policies" active={activeTab}>
                <LeavePoliciesTab canManage={canManage} />
            </TabPanel>
        </div>
    );
}
