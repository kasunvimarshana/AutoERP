import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { useSearchParams } from 'react-router-dom';
import { TestRouter } from '@/test/TestRouter';
import { describe, expect, it, vi } from 'vitest';
import { PurchaseTabs, type PurchaseTabItem } from './PurchaseTabs';
const tabs: PurchaseTabItem[] = [
    { id: 'details', label: 'Details' },
    { id: 'lines', label: 'Lines' },
    { id: 'attachments', label: 'Attachments' },
];
describe('PurchaseTabs', () => {
    it('falls back to the first tab when the URL tab is invalid', () => {
        render(
            <TestRouter initialEntries={['/purchase/orders/create?tab=missing']}>
                <TabsHarness />
            </TestRouter>,
        );
        expect(screen.getByRole('tab', { name: 'Details' })).toHaveAttribute('aria-selected', 'true');
        expect(screen.getByRole('tab', { name: 'Lines' })).toHaveAttribute('aria-selected', 'false');
    });
    it('updates URL-backed tab state without rendering links', async () => {
        const onChange = vi.fn();
        render(
            <TestRouter initialEntries={['/purchase/orders/create?tab=details']}>
                <TabsHarness onChange={onChange} />
            </TestRouter>,
        );
        await userEvent.click(screen.getByRole('tab', { name: 'Lines' }));
        expect(onChange).toHaveBeenCalledWith('lines');
        expect(screen.getByRole('tab', { name: 'Lines' })).toHaveAttribute('aria-selected', 'true');
        expect(screen.queryByRole('link', { name: 'Lines' })).not.toBeInTheDocument();
    });
    it('supports keyboard navigation across tabs', async () => {
        render(
            <TestRouter initialEntries={['/purchase/orders/create?tab=details']}>
                <TabsHarness />
            </TestRouter>,
        );
        screen.getByRole('tab', { name: 'Details' }).focus();
        await userEvent.keyboard('{ArrowRight}');
        expect(screen.getByRole('tab', { name: 'Lines' })).toHaveAttribute('aria-selected', 'true');
        await waitFor(() => expect(screen.getByRole('tab', { name: 'Lines' })).toHaveFocus());
    });
});
function TabsHarness({ onChange }: { onChange?: (tab: string) => void }) {
    const [searchParams] = useSearchParams();
    return <PurchaseTabs tabs={tabs} activeTab={searchParams.get('tab') ?? ''} onChange={onChange} />;
}
