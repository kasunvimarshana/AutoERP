import { useRef, useState, type FormEvent, type ReactNode } from 'react';
import { DataTable } from '@/shared/components/DataTable';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { Panel } from '@/shared/components/Panel';
import { QuantityDisplay } from '@/shared/components/QuantityDisplay';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { readableRelation } from '@/shared/utils/object';
import type { InventoryRecord } from '../inventoryTypes';

export const zeroDecimal = '0.000000';

export interface ApiResult<T> {
    data: T | null;
    error: ApiError | null;
    loading: boolean;
    reload: () => void;
    setData: (data: T) => void;
}

export interface WorkflowProps {
    data: InventoryRecord[];
    loading: boolean;
    error: ApiError | null;
    reload: () => void;
}

export interface InventoryColumn {
    key: string;
    header: string;
    render: (row: InventoryRecord) => ReactNode;
}

export function WorkflowPanel({
    title,
    children,
    loading,
    error,
    actionError,
}: {
    title: string;
    children: ReactNode;
    loading: boolean;
    error: ApiError | null;
    actionError: ApiError | null;
}) {
    return (
        <Panel title={title}>
            <div className="space-y-4">
                <ErrorAlert error={error ?? actionError} />
                {children}
                {loading && <LoadingState />}
            </div>
        </Panel>
    );
}

export function RecordList({ rows, columns }: { rows: InventoryRecord[]; columns: InventoryColumn[] }) {
    return (
        <div className="mt-4">
            <DataTable
                rows={rows}
                rowKey={(row) => row.id}
                columns={columns}
                rowBadge={(row) => row.status ? <StatusBadge status={String(row.status)} /> : null}
            />
        </div>
    );
}

export function relation(value: unknown) {
    return readableRelation(value);
}

export function label(row: InventoryRecord, key: string) {
    return String(row[key] ?? '-');
}

export function quantity(value: unknown) {
    return <QuantityDisplay value={String(value ?? zeroDecimal)} />;
}

export function localToday() {
    const date = new Date();
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
}

export function sumDecimals(values: string[]) {
    return values.reduce((total, value) => {
        const next = decimalToScaledInt(total) + decimalToScaledInt(value);
        const sign = next < 0n ? '-' : '';
        const absolute = next < 0n ? -next : next;

        return `${sign}${absolute / 1_000_000n}.${String(absolute % 1_000_000n).padStart(6, '0')}`;
    }, zeroDecimal);
}

export async function runFormAction(
    event: FormEvent,
    setBusy: (value: boolean) => void,
    setError: (error: ApiError | null) => void,
    callback: () => Promise<void>,
) {
    event.preventDefault();
    setBusy(true);
    setError(null);

    try {
        await callback();
    } catch (requestError) {
        setError(toApiError(requestError));
    } finally {
        setBusy(false);
    }
}

export function useRecordAction(reload: () => void, setError: (error: ApiError | null) => void) {
    const running = useRef(false);
    const [pendingKey, setPendingKey] = useState<string | null>(null);

    const run = async (key: string, callback: () => Promise<unknown>) => {
        if (running.current) {
            return;
        }

        running.current = true;
        setPendingKey(key);
        setError(null);
        try {
            await callback();
            reload();
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            running.current = false;
            setPendingKey(null);
        }
    };

    return { pendingKey, run };
}

function decimalToScaledInt(value: string) {
    const normalized = value.trim() || zeroDecimal;
    const sign = normalized.startsWith('-') ? -1n : 1n;
    const unsigned = normalized.replace(/^-/, '');
    const [whole = '0', fraction = ''] = unsigned.split('.');

    return sign * (BigInt(whole || '0') * 1_000_000n + BigInt(fraction.padEnd(6, '0').slice(0, 6) || '0'));
}
