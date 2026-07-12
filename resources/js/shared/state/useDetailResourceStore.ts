import { useEffect, useState } from 'react';
import { createStore } from 'zustand/vanilla';
import { useStore } from 'zustand';

interface DetailResourceState<T> {
    data: T | null;
    setData: (data: T) => void;
    replace: (data: T | null) => void;
    patch: (updater: (current: T) => T) => void;
}

export function useDetailResourceStore<T>(value: T | null) {
    const [store] = useState(() => createStore<DetailResourceState<T>>((set) => ({
        data: value,
        setData: (data) => set({ data }),
        replace: (data) => set({ data }),
        patch: (updater) => set((state) => ({
            data: state.data === null ? null : updater(state.data),
        })),
    })));

    useEffect(() => {
        if (value !== null) {
            store.getState().replace(value);
        }
    }, [store, value]);

    const data = useStore(store, (state) => state.data);
    const setData = useStore(store, (state) => state.setData);
    const patch = useStore(store, (state) => state.patch);

    return {
        data,
        setData,
        patch,
    };
}
