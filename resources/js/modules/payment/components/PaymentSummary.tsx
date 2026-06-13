export function PaymentSummary({ total, lineCount }: { total: string; lineCount: number }) {
    return (
        <div className="rounded-lg bg-slate-50 px-4 py-3 text-sm text-slate-700">
            <span className="font-semibold text-slate-900">Payment total / line sum:</span>
            <span className="ml-2 tabular-nums">{total}</span>
            <span className="ml-2 text-slate-500">from {lineCount} method line{lineCount === 1 ? '' : 's'}</span>
        </div>
    );
}
