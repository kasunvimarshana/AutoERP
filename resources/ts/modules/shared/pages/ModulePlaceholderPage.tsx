import { Link } from 'react-router-dom';
import { Button } from '../../../components/ui/Button';
import { Card } from '../../../components/ui/Card';

export default function ModulePlaceholderPage({ description, title }: { title: string; description: string }) {
    return (
        <div className="mx-auto flex w-full max-w-5xl flex-col gap-6">
            <Card>
                <div className="border-b border-slate-200/80 px-6 py-6">
                    <p className="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Workspace</p>
                    <h2 className="mt-2 text-2xl font-semibold text-slate-950">{title}</h2>
                    <p className="mt-3 max-w-3xl text-sm leading-6 text-slate-600">{description}</p>
                </div>
                <div className="px-6 py-6">
                    <p className="rounded-2xl border border-dashed border-slate-200 bg-slate-50 p-5 text-sm leading-6 text-slate-600">
                        This route is a placeholder while the new module-based frontend is being rebuilt.
                    </p>
                    <div className="mt-5">
                        <Link to="/dashboard">
                            <Button variant="secondary">Return to dashboard</Button>
                        </Link>
                    </div>
                </div>
            </Card>
        </div>
    );
}
