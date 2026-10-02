import { render, screen } from '@testing-library/react';
import { expect, it } from 'vitest';
import { StatusBadge } from './StatusBadge';

it('supports a business-specific display label without changing status styling input', () => {
    render(<StatusBadge status="in_custody" label="With customer" />);
    expect(screen.getByText('With customer')).toBeInTheDocument();
    expect(screen.queryByText('In Custody')).not.toBeInTheDocument();
});
