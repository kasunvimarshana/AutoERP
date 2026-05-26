import type { ReactNode } from 'react';

type FullPageStateProps = {
    title: string;
    description: string;
    action?: ReactNode;
};

export function FullPageState({ action, description, title }: FullPageStateProps) {
    return (
        <div className="flex min-h-screen items-center justify-center px-6 py-10">
            <div className="w-full max-w-md rounded-3xl border border-stone-200/80 bg-white/90 p-8 shadow-sm backdrop-blur">
                <div className="space-y-3">
                    <p className="text-sm font-medium uppercase tracking-[0.18em] text-stone-500">AutoERP</p>
                    <h1 className="text-2xl font-semibold text-stone-950">{title}</h1>
                    <p className="text-sm leading-6 text-stone-600">{description}</p>
                </div>
                {action ? <div className="mt-6">{action}</div> : null}
            </div>
        </div>
    );
}
