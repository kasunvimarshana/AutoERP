import { useState } from 'react';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it } from 'vitest';
import { TabPanel, Tabs } from './Tabs';

type Tab = 'summary' | 'history';

describe('Tabs', () => {
    it('links tabs to panels and supports arrow-key navigation', async () => {
        const user = userEvent.setup();
        render(<TabsHarness />);

        const summary = screen.getByRole('tab', { name: 'Summary' });
        expect(summary).toHaveAttribute('aria-controls', 'record-tabs-summary-panel');
        expect(screen.getByRole('tabpanel')).toHaveAttribute('aria-labelledby', 'record-tabs-summary-tab');

        summary.focus();
        await user.keyboard('{ArrowRight}');

        await waitFor(() => expect(screen.getByRole('tab', { name: 'History' })).toHaveFocus());
        expect(screen.getByRole('tabpanel')).toHaveTextContent('History content');
    });
});

function TabsHarness() {
    const [active, setActive] = useState<Tab>('summary');
    return (
        <>
            <Tabs<Tab>
                id="record-tabs"
                tabs={[
                    { id: 'summary', label: 'Summary' },
                    { id: 'history', label: 'History' },
                ]}
                active={active}
                onChange={setActive}
            />
            <TabPanel tabsId="record-tabs" tabId="summary" active={active}>Summary content</TabPanel>
            <TabPanel tabsId="record-tabs" tabId="history" active={active}>History content</TabPanel>
        </>
    );
}
