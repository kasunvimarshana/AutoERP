export function LoadingState({ label = 'Loading...', fullPage = false }: { label?: string; fullPage?: boolean }) {
    return (
        <div className={`flex items-center justify-center gap-3 rounded-xl border border-slate-200 bg-white text-sm text-slate-500 ${fullPage ? 'min-h-screen border-0' : 'min-h-40'}`} role="status" aria-live="polite">
            <span className="h-5 w-5 animate-spin rounded-full border-2 border-slate-300 border-t-blue-600" aria-hidden="true" />
            {label}
        </div>
    );
}
