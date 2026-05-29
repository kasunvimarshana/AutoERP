interface ModuleHeaderProps {
    eyebrow: string;
    title: string;
    description: string;
    accent: string;
}

export function ModuleHeader({ eyebrow, title, description, accent }: ModuleHeaderProps) {
    return (
        <header className="overflow-hidden rounded-[2rem] border border-slate-200/80 bg-white/85 p-6 shadow-[0_18px_50px_-36px_rgba(15,23,42,0.45)] backdrop-blur">
            <div className="flex flex-wrap items-start gap-5">
                <div className={`h-16 w-16 rounded-2xl bg-gradient-to-br ${accent} shadow-lg`} />
                <div className="min-w-0 flex-1">
                    <p className="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">{eyebrow}</p>
                    <h1 className="mt-2 font-display text-3xl font-semibold tracking-tight text-slate-950">{title}</h1>
                    <p className="mt-3 max-w-3xl text-sm leading-7 text-slate-600">{description}</p>
                </div>
            </div>
        </header>
    );
}
