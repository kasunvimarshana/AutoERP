interface StatCardProps {
    label: string;
    value: string;
    tone?: 'brand' | 'slate' | 'amber';
}

const toneStyles = {
    brand: 'from-brand-500/15 to-brand-700/10 border-brand-500/20 text-brand-900',
    slate: 'from-slate-100 to-slate-50 border-slate-200 text-slate-900',
    amber: 'from-amber-100 to-orange-50 border-amber-200 text-amber-900',
} as const;

export function StatCard({ label, value, tone = 'brand' }: StatCardProps) {
    return (
        <article className={`rounded-[1.5rem] border bg-gradient-to-br p-5 shadow-sm ${toneStyles[tone]}`}>
            <div className="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">{label}</div>
            <div className="mt-3 text-sm leading-6">{value}</div>
        </article>
    );
}
