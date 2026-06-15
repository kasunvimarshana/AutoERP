import { useState } from 'react';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it } from 'vitest';
import { Modal } from './Modal';

describe('Modal', () => {
    it('locks scrolling, closes on Escape, and restores focus', async () => {
        const user = userEvent.setup();
        render(<ModalHarness />);

        const opener = screen.getByRole('button', { name: 'Open modal' });
        await user.click(opener);

        expect(document.body.style.overflow).toBe('hidden');
        await waitFor(() => expect(screen.getByRole('button', { name: 'Close modal' })).toHaveFocus());

        await user.keyboard('{Escape}');

        expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
        expect(document.body.style.overflow).toBe('');
        expect(opener).toHaveFocus();
    });
});

function ModalHarness() {
    const [open, setOpen] = useState(false);
    return (
        <>
            <button type="button" onClick={() => setOpen(true)}>Open modal</button>
            <Modal open={open} title="Example modal" onClose={() => setOpen(false)}>
                <button type="button">Focusable content</button>
            </Modal>
        </>
    );
}
