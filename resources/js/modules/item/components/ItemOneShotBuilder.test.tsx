import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { useState } from 'react';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { emptyOneShotDraft, ItemOneShotBuilder, type OneShotDraft } from './ItemOneShotBuilder';

const uomState = vi.hoisted(() => ({
    index: 0,
    uoms: [
        { id: 1, code: 'PCS', name: 'Pieces' },
        { id: 2, code: 'BOX', name: 'Box' },
    ],
}));

vi.mock('./ItemUomSelect', () => ({
    ItemUomSelect: ({ onChange }: { onChange: (value: { id: number; code: string; name: string }) => void }) => (
        <button
            type="button"
            onClick={() => {
                onChange(uomState.uoms[uomState.index]);
                uomState.index = Math.min(uomState.index + 1, uomState.uoms.length - 1);
            }}
        >
            Select UOM
        </button>
    ),
}));

describe('ItemOneShotBuilder', () => {
    beforeEach(() => {
        uomState.index = 0;
    });

    it('keeps only one draft Default Unit selected', async () => {
        const user = userEvent.setup();
        render(<Harness />);

        await user.click(screen.getByRole('button', { name: 'Select UOM' }));
        await user.click(screen.getByRole('button', { name: 'Add unit' }));
        await user.click(screen.getByRole('button', { name: 'Select UOM' }));
        await user.click(screen.getByRole('button', { name: 'Add unit' }));

        expect(screen.getByTestId('defaults')).toHaveTextContent('false,true');
    });
});

function Harness() {
    const [draft, setDraft] = useState<OneShotDraft>(emptyOneShotDraft);

    return (
        <>
            <ItemOneShotBuilder section="units" value={draft} onChange={setDraft} />
            <div data-testid="defaults">{draft.units.map((unit) => String(unit.is_default)).join(',')}</div>
        </>
    );
}
