import { act, fireEvent, render, screen } from '@testing-library/react';
import { useRef } from 'react';
import { afterEach, expect, it, vi } from 'vitest';
import { useDialogAccessibility } from './useDialogAccessibility';

function Dialog({ open = true }: { open?: boolean }) {
    const ref = useRef<HTMLDivElement>(null);
    useDialogAccessibility(open, ref, vi.fn());
    return open ? <div ref={ref}><button>Close</button><input aria-label="Name" /></div> : null;
}
afterEach(() => vi.restoreAllMocks());
it('does not steal focus when input begins before the scheduled initial focus', () => {
    let callback!: FrameRequestCallback;
    vi.spyOn(window, 'requestAnimationFrame').mockImplementation(fn => { callback = fn; return 1; });
    render(<Dialog />);
    const input = screen.getByRole('textbox', { name: 'Name' });
    act(() => input.focus()); fireEvent.change(input, { target: { value: 'First character' } });
    act(() => callback(0));
    expect(input).toHaveFocus();
    expect(input).toHaveValue('First character');
});
it('cancels pending initial focus when a dialog closes', () => {
    vi.spyOn(window, 'requestAnimationFrame').mockReturnValue(17);
    const cancel = vi.spyOn(window, 'cancelAnimationFrame');
    const view = render(<Dialog />);
    view.rerender(<Dialog open={false} />);
    expect(cancel).toHaveBeenCalledWith(17);
});
