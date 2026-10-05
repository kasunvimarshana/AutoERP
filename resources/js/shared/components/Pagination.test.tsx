import { fireEvent, render, screen } from '@testing-library/react';
import { expect, it, vi } from 'vitest';
import { Pagination } from './Pagination';
import type { PaginationMeta } from '@/shared/types/pagination';

const meta: PaginationMeta = {
    current_page: 2,
    from: 11,
    last_page: 4,
    per_page: 10,
    to: 20,
    total: 37,
};

it('exposes the current range and navigates through the shared controls', () => {
    const onPageChange = vi.fn();
    render(<Pagination meta={meta} onPageChange={onPageChange} />);

    expect(screen.getByRole('navigation', { name: 'Pagination' })).toHaveTextContent('11-20 of 37');
    expect(screen.getByText('2 / 4')).toHaveAttribute('aria-current', 'page');

    fireEvent.click(screen.getByRole('button', { name: 'Go to page 1' }));
    fireEvent.click(screen.getByRole('button', { name: 'Go to page 3' }));

    expect(onPageChange).toHaveBeenNthCalledWith(1, 1);
    expect(onPageChange).toHaveBeenNthCalledWith(2, 3);
});

it('disables both pagination actions while the owning request is in flight', () => {
    render(<Pagination meta={meta} disabled onPageChange={vi.fn()} />);

    expect(screen.getByRole('button', { name: 'Go to page 1' })).toBeDisabled();
    expect(screen.getByRole('button', { name: 'Go to page 3' })).toBeDisabled();
});
