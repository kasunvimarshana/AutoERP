import { Textarea } from '../ui/Textarea';

export function CommentPanel() {
    return (
        <div className="rounded-lg border border-slate-200 bg-white p-4">
            <p className="text-sm font-semibold text-slate-950">Comments</p>
            <Textarea className="mt-3" placeholder="Write a comment..." />
        </div>
    );
}
