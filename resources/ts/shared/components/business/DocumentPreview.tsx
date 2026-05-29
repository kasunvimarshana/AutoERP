import { Card } from '../ui/Card';

export function DocumentPreview({ title = 'Document Preview' }: { title?: string }) {
    return (
        <Card className="p-5">
            <h3 className="text-sm font-semibold text-slate-950">{title}</h3>
            <p className="mt-2 text-sm text-slate-500">Backend-rendered previews will appear here during integration.</p>
        </Card>
    );
}
