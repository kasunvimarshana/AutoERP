import { Button } from '../ui/Button';

export function Pagination() {
    return (
        <div className="flex items-center justify-between rounded-b-lg border border-t-0 border-slate-200 bg-white px-4 py-3 text-sm text-slate-500">
            <span>Showing 1-10 of 48</span>
            <div className="flex gap-2">
                <Button variant="secondary">Previous</Button>
                <Button variant="secondary">Next</Button>
            </div>
        </div>
    );
}
