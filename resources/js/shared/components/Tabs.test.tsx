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

    it('keeps mounted tab content hidden instead of unmounting when requested', async () => {
        const user = userEvent.setup();
        render(<TabsHarness keepMounted />);

        expect(screen.getByText('Summary content')).toBeVisible();
        expect(screen.getByText('History content')).not.toBeVisible();

        await user.click(screen.getByRole('tab', { name: 'History' }));

        expect(screen.getByText('Summary content')).not.toBeVisible();
        expect(screen.getByText('History content')).toBeVisible();
    });

    it('renders segmented pills variant with proper styling and ARIA attributes', () => {
        render(
            <Tabs<Tab>
                tabs={[
                    { id: 'summary', label: 'Summary', badge: 3 },
                    { id: 'history', label: 'History' },
                ]}
                active="summary"
                onChange={() => undefined}
                variant="pills"
            />,
        );

        const tablist = screen.getByRole('tablist');
        expect(tablist).toHaveClass('inline-flex', 'p-1', 'bg-slate-100', 'rounded-lg');

        const summaryTab = screen.getByRole('tab', { name: /Summary/ });
        expect(summaryTab).toHaveAttribute('aria-selected', 'true');
        expect(summaryTab).toHaveClass('bg-white', 'text-slate-900', 'shadow-xs', 'font-semibold');

        const historyTab = screen.getByRole('tab', { name: 'History' });
        expect(historyTab).toHaveAttribute('aria-selected', 'false');
        expect(historyTab).toHaveClass('text-slate-600');
    });
});

function TabsHarness({ keepMounted = false }: { keepMounted?: boolean }) {
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
            <TabPanel tabsId="record-tabs" tabId="summary" active={active} keepMounted={keepMounted}>Summary content</TabPanel>
            <TabPanel tabsId="record-tabs" tabId="history" active={active} keepMounted={keepMounted}>History content</TabPanel>
        </>
    );
}
