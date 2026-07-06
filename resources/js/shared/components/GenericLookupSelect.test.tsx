import { act, fireEvent, render, screen, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it, vi } from 'vitest';
import type { NamedResource } from '@/shared/types/common';
import type { LookupLoadParams, LookupResult } from '@/shared/types/lookup';
import { GenericLookupSelect } from './GenericLookupSelect';

describe('GenericLookupSelect', () => {
    it('applies multiple exclusions across preload and pagination and deduplicates options', async () => {
        const search = vi.fn(async ({ page }: LookupLoadParams): Promise<LookupResult<NamedResource>> => ({
            data: page === 1
                ? [resource(1), resource(2), resource(3), resource(3)]
                : [resource(3), resource(4), resource(5)],
            links: {},
            meta: { current_page: page, from: 1, last_page: 2, path: '/', per_page: 20, to: 3, total: 5 },
        }));

        renderLookup({ search, excludeId: 1, excludeIds: [2, '4'], loadOnOpen: true, minSearchLength: 0 });
        fireEvent.focus(screen.getByRole('combobox', { name: 'Source' }));

        const listbox = await screen.findByRole('listbox');
        expect(within(listbox).queryByText('Option 1')).not.toBeInTheDocument();
        expect(within(listbox).queryByText('Option 2')).not.toBeInTheDocument();
        expect(within(listbox).getAllByText('Option 3')).toHaveLength(1);

        fireEvent.click(screen.getByRole('button', { name: 'Load more' }));
        await waitFor(() => expect(within(listbox).getByText('Option 5')).toBeInTheDocument());
        expect(within(listbox).queryByText('Option 4')).not.toBeInTheDocument();
        expect(within(listbox).getAllByText('Option 3')).toHaveLength(1);
    });

    it('preserves preload and configurable minimum search length behavior', async () => {
        const search = vi.fn(async ({ search: term }: LookupLoadParams): Promise<LookupResult<NamedResource>> => ({
            data: [resource(10, `Matched ${term || 'preload'}`)],
            links: {},
            meta: { current_page: 1, from: 1, last_page: 1, path: '/', per_page: 20, to: 1, total: 1 },
        }));

        renderLookup({ search, loadOnOpen: true, minSearchLength: 0 });
        fireEvent.focus(screen.getByRole('combobox', { name: 'Source' }));
        expect(await screen.findByText('Matched preload')).toBeInTheDocument();

        search.mockClear();
        renderLookup({ search, minSearchLength: 3, debounceMs: 0 });
        const inputs = screen.getAllByRole('combobox', { name: 'Source' });
        const input = inputs[inputs.length - 1];
        await userEvent.type(input, 'ab');
        expect(await screen.findByText('Enter 1 more character to search.')).toBeInTheDocument();
        expect(search).not.toHaveBeenCalled();

        await userEvent.type(input, 'c');
        await waitFor(() => expect(search).toHaveBeenCalledWith(expect.objectContaining({ search: 'abc' })));
    });

    it('keeps stale searches from replacing newer results', async () => {
        const slow = deferred<LookupResult<NamedResource>>();
        const search = vi.fn(({ search: term }: LookupLoadParams): Promise<LookupResult<NamedResource>> => {
            if (term === 'old') return slow.promise;

            return Promise.resolve({
                data: [resource(2, 'Fresh result')],
                links: {},
                meta: { current_page: 1, from: 1, last_page: 1, path: '/', per_page: 20, to: 1, total: 1 },
            });
        });

        renderLookup({ search, minSearchLength: 1, debounceMs: 0 });
        const input = screen.getByRole('combobox', { name: 'Source' });
        await userEvent.type(input, 'old');
        await waitFor(() => expect(search).toHaveBeenCalledWith(expect.objectContaining({ search: 'old' })));

        fireEvent.change(input, { target: { value: 'new' } });
        await waitFor(() => expect(screen.getByText('Fresh result')).toBeInTheDocument());

        await act(async () => {
            slow.resolve({
                data: [resource(1, 'Stale result')],
                links: {},
                meta: { current_page: 1, from: 1, last_page: 1, path: '/', per_page: 20, to: 1, total: 1 },
            });
            await slow.promise;
        });

        expect(screen.queryByText('Stale result')).not.toBeInTheDocument();
        expect(screen.getByText('Fresh result')).toBeInTheDocument();
    });

    it('renders a custom empty state action when no matches are found', async () => {
        const search = vi.fn(async (): Promise<LookupResult<NamedResource>> => ({
            data: [],
            links: {},
            meta: { current_page: 1, from: null, last_page: 1, path: '/', per_page: 20, to: null, total: 0 },
        }));

        render(
            <GenericLookupSelect
                label="Source"
                value={null}
                onChange={() => undefined}
                search={search}
                formatLabel={(item) => item.name}
                minSearchLength={2}
                debounceMs={0}
                renderEmptyState={({ searchText }) => (
                    <button type="button">Create {searchText}</button>
                )}
            />,
        );

        await userEvent.type(screen.getByRole('combobox', { name: 'Source' }), 'AB');

        expect(await screen.findByRole('button', { name: 'Create AB' })).toBeInTheDocument();
    });
});

function renderLookup(props: Partial<{
    search: (params: LookupLoadParams) => Promise<LookupResult<NamedResource>>;
    excludeId: number | null;
    excludeIds: Array<number | string>;
    loadOnOpen: boolean;
    minSearchLength: number;
    debounceMs: number;
}>) {
    return render(
        <GenericLookupSelect
            label="Source"
            value={null}
            onChange={() => undefined}
            search={props.search ?? (async () => ({ data: [], links: {}, meta: undefined }))}
            formatLabel={(item) => item.name}
            excludeId={props.excludeId}
            excludeIds={props.excludeIds}
            loadOnOpen={props.loadOnOpen}
            minSearchLength={props.minSearchLength}
            debounceMs={props.debounceMs}
        />,
    );
}

function resource(id: number, name = `Option ${id}`): NamedResource {
    return { id, name };
}

function deferred<T>() {
    let resolve!: (value: T) => void;
    let reject!: (reason?: unknown) => void;
    const promise = new Promise<T>((promiseResolve, promiseReject) => {
        resolve = promiseResolve;
        reject = promiseReject;
    });

    return { promise, resolve, reject };
}
