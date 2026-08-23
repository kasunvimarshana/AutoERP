import { render, screen, waitFor } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';
import { FormDrawer } from './Drawer';

describe('FormDrawer focus management', () => {
    it('preserves intentional focus inside the drawer', async () => {
        render(
            <FormDrawer open title="Add line" onClose={vi.fn()}>
                <input aria-label="Item" autoFocus />
            </FormDrawer>,
        );

        await waitFor(() => expect(screen.getByRole('textbox', { name: 'Item' })).toHaveFocus());
        expect(screen.getByRole('button', { name: 'Close drawer' })).not.toHaveFocus();
    });
});
