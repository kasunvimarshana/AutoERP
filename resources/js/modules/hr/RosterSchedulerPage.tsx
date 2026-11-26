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
import { HolidayCalendarsTab } from './components/roster/HolidayCalendarsTab';
import { RosterGridTab } from './components/roster/RosterGridTab';
import { ShiftAssignmentsTab } from './components/roster/ShiftAssignmentsTab';
import { ShiftReallocationsTab } from './components/roster/ShiftReallocationsTab';
import { ShiftRotationsTab } from './components/roster/ShiftRotationsTab';
import { ShiftsAndBreaksTab } from './components/roster/ShiftsAndBreaksTab';
import { ShiftSwapsTab } from './components/roster/ShiftSwapsTab';
import { hrPermissions } from './hrPermissions';

type RosterTabId =
    | 'roster'
    | 'shifts'
    | 'assignments'
    | 'swaps'
    | 'reallocations'
    | 'rotations'
    | 'holidays';

const ROSTER_TABS: TabItem<RosterTabId>[] = [
    { id: 'roster', label: 'Roster Grid' },
    { id: 'shifts', label: 'Shifts & Breaks' },
    { id: 'assignments', label: 'Shift Assignments' },
    { id: 'swaps', label: 'Shift Swaps' },
    { id: 'reallocations', label: 'Shift Reallocations' },
    { id: 'rotations', label: 'Rotations & Assignments' },
    { id: 'holidays', label: 'Holiday Calendars' },
];

export default function RosterSchedulerPage() {
    const auth = useAuth();
    const canView = hasPermission(auth, hrPermissions.shiftsView);
    const canManage = hasPermission(auth, hrPermissions.shiftsManage);

    const tabsId = useId();
    const [activeTab, setActiveTab] = useState<RosterTabId>('roster');

    if (!canView) return <AccessDeniedPage />;

    return (
        <div className="space-y-6 p-6">
            <ContentHeader
                title="Shift Roster & Scheduling"
                description="Manage shift definitions, breaks, assignments, peer swaps, cover reallocations, rotations, and holiday calendars."
            />

            <Tabs
                id={tabsId}
                tabs={ROSTER_TABS}
                active={activeTab}
                onChange={setActiveTab}
                ariaLabel="Shift management sections"
            />

            <TabPanel tabsId={tabsId} tabId="roster" active={activeTab}>
                <RosterGridTab canManage={canManage} />
            </TabPanel>

            <TabPanel tabsId={tabsId} tabId="shifts" active={activeTab}>
                <ShiftsAndBreaksTab canManage={canManage} />
            </TabPanel>

            <TabPanel tabsId={tabsId} tabId="assignments" active={activeTab}>
                <ShiftAssignmentsTab canManage={canManage} />
            </TabPanel>

            <TabPanel tabsId={tabsId} tabId="swaps" active={activeTab}>
                <ShiftSwapsTab canManage={canManage} />
            </TabPanel>

            <TabPanel tabsId={tabsId} tabId="reallocations" active={activeTab}>
                <ShiftReallocationsTab canManage={canManage} />
            </TabPanel>

            <TabPanel tabsId={tabsId} tabId="rotations" active={activeTab}>
                <ShiftRotationsTab canManage={canManage} />
            </TabPanel>

            <TabPanel tabsId={tabsId} tabId="holidays" active={activeTab}>
                <HolidayCalendarsTab canManage={canManage} />
            </TabPanel>
        </div>
    );
}
