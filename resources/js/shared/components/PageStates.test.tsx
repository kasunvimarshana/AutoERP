import { render, screen } from '@testing-library/react';
import { TestRouter } from '@/test/TestRouter';
import { describe, expect, it } from 'vitest';
import { ApiError } from '@/shared/api/apiError';
import { DataTable } from './DataTable';
import { ErrorAlert } from './ErrorAlert';
import { LoadingState } from './LoadingState';
describe('shared page states', () => {
    it('renders accessible loading, empty, and error states', () => {
        const { rerender } = render(<LoadingState label="Loading suppliers..." />);
        expect(screen.getByRole('status')).toHaveTextContent('Loading suppliers...');
        rerender(
            <TestRouter>
                <DataTable
                    rows={[]}
                    rowKey={(row: { id: number }) => row.id}
                    columns={[{ key: 'name', header: 'Name', render: (row) => row.id }]}
                    emptyMessage="No suppliers match these filters."
                />
            </TestRouter>,
        );
        expect(screen.getByText('No suppliers match these filters.')).toBeInTheDocument();
        rerender(<ErrorAlert error={new ApiError(
            'The network is unavailable.',
            500,
            'UNEXPECTED_ERROR',
            'infrastructure',
            {},
            { correlation_id: '01JSUPPORTREFERENCE', guidance: 'Retry after checking platform health.' },
        )} />);
        expect(screen.getByRole('alert')).toHaveTextContent('The network is unavailable.');
        expect(screen.getByRole('alert')).toHaveTextContent('Retry after checking platform health.');
        expect(screen.getByRole('alert')).toHaveTextContent('01JSUPPORTREFERENCE');
        expect(screen.getByRole('alert')).toHaveTextContent('UNEXPECTED_ERROR');
    });
});
