import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { MemoryRouter, useSearchParams } from 'react-router-dom';
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
            <MemoryRouter initialEntries={['/purchase/orders/create?tab=missing']}>
                <TabsHarness />
            </MemoryRouter>,
        );

        expect(screen.getByRole('tab', { name: 'Details' })).toHaveAttribute('aria-selected', 'true');
        expect(screen.getByRole('tab', { name: 'Lines' })).toHaveAttribute('aria-selected', 'false');
    });

    it('updates URL-backed tab state without rendering links', async () => {
        const onChange = vi.fn();
        render(
            <MemoryRouter initialEntries={['/purchase/orders/create?tab=details']}>
                <TabsHarness onChange={onChange} />
            </MemoryRouter>,
        );

        await userEvent.click(screen.getByRole('tab', { name: 'Lines' }));

        expect(onChange).toHaveBeenCalledWith('lines');
        expect(screen.getByRole('tab', { name: 'Lines' })).toHaveAttribute('aria-selected', 'true');
        expect(screen.queryByRole('link', { name: 'Lines' })).not.toBeInTheDocument();
    });

    it('supports keyboard navigation across tabs', async () => {
        render(
            <MemoryRouter initialEntries={['/purchase/orders/create?tab=details']}>
                <TabsHarness />
            </MemoryRouter>,
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
