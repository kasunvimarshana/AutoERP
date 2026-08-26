import { createStore, type StoreApi } from 'zustand/vanilla';
import type { NamedResource } from '@/shared/types/common';
import type { VehicleServiceJobLine } from '../vehicleServiceTypes';

export interface WorkforceSnapshot {
    lines: VehicleServiceJobLine[];
    rowVersion: number;
    supervisor: NamedResource | null;
}

export interface WorkforceLineSnapshot {
    lines: VehicleServiceJobLine[];
    rowVersion: number;
}

interface VehicleServiceJobState {
    jobId: number;
    workforce: WorkforceSnapshot | null;
    replaceWorkforce: (snapshot: WorkforceSnapshot) => void;
    replaceWorkforceLines: (snapshot: WorkforceLineSnapshot) => void;
}

export type VehicleServiceJobStore = StoreApi<VehicleServiceJobState>;

export function createVehicleServiceJobStore(jobId: number): VehicleServiceJobStore {
    return createStore<VehicleServiceJobState>((set) => ({
        jobId,
        workforce: null,
        replaceWorkforce: (workforce) => set({ workforce }),
        replaceWorkforceLines: ({ lines, rowVersion }) => set((state) => ({
            workforce: {
                lines,
                rowVersion,
                supervisor: state.workforce?.supervisor ?? null,
            },
        })),
    }));
}
