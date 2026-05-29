export function NotificationButton() {
    return (
        <button className="flex h-10 w-10 items-center justify-center rounded-full text-slate-500 transition hover:bg-slate-100" type="button">
            <svg className="h-5 w-5" fill="none" stroke="currentColor" strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.8" viewBox="0 0 24 24">
                <path d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.17V11a6 6 0 1 0-12 0v3.17a2 2 0 0 1-.6 1.42L4 17h5" />
                <path d="M10 20a2 2 0 0 0 4 0" />
            </svg>
        </button>
    );
}
