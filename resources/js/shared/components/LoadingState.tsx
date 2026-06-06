export function LoadingState({ label = 'Loading...', fullPage = false }: { label?: string; fullPage?: boolean }) {
    return (
        <div className={`flex items-center justify-center gap-3 text-sm text-slate-500 ${fullPage ? 'min-h-screen' : 'min-h-40'}`}>
            <span className="h-5 w-5 animate-spin rounded-full border-2 border-slate-300 border-t-sky-600" />
            {label}
        </div>
    );
}
