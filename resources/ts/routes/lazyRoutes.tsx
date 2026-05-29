import { createElement, lazy, Suspense, type ComponentType } from 'react';
import { Spinner } from '../shared/components/ui/Spinner';

type LazyModule = Record<string, ComponentType<unknown>>;

function RouteFallback() {
    return (
        <div className="flex min-h-[360px] items-center justify-center">
            <div className="flex items-center gap-3 rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-600 shadow-sm">
                <Spinner />
                Loading workspace
            </div>
        </div>
    );
}

export function lazyNamed<TModule extends LazyModule, TKey extends keyof TModule>(
    importer: () => Promise<TModule>,
    exportName: TKey,
) {
    const Component = lazy(() => importer().then((module) => ({ default: module[exportName] as ComponentType })));

    return (
        <Suspense fallback={<RouteFallback />}>
            {createElement(Component)}
        </Suspense>
    );
}
