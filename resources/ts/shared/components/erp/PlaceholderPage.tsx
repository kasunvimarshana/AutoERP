import { PageHeader, SecondaryLink } from './ErpUi';

export function PlaceholderPage({ area, title }: { area: string; title: string }) {
    return (
        <div className="mx-auto max-w-3xl space-y-5">
            <PageHeader
                actions={<SecondaryLink to="/dashboard">Back to dashboard</SecondaryLink>}
                eyebrow={area}
                subtitle="This section is part of the ERP information architecture. The backend module exists, but this frontend workspace is not wired yet."
                title={title}
            />
            <section className="rounded-xl border border-dashed border-slate-300 bg-white p-8 text-sm leading-6 text-slate-600">
                <p className="font-semibold text-slate-900">No CRUD screen was added here.</p>
                <p className="mt-2">This placeholder keeps navigation consistent without pretending the workflow is complete. It avoids broken links and keeps the current implemented modules intact.</p>
            </section>
        </div>
    );
}
