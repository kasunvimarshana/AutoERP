import { Link } from 'react-router-dom';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { Panel } from '@/shared/components/Panel';

const workspaces = [
    { title: 'User List', description: 'Maintain tenant users and account status.', to: '/access/users' },
    { title: 'Roles', description: 'Define reusable access roles for the tenant.', to: '/access/roles' },
    { title: 'Permissions', description: 'Review module permission keys and descriptions.', to: '/access/permissions' },
];

export default function AccessOverviewPage() {
    return (
        <>
            <ContentHeader title="Users & Access" description="Tenant-scoped identity, roles, and permission administration." />
            <div className="grid gap-5 md:grid-cols-3">
                {workspaces.map((workspace) => (
                    <Link key={workspace.to} to={workspace.to} className="group rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        <Panel className="h-full transition-colors group-hover:border-blue-300 group-hover:bg-blue-50/30">
                            <h2 className="font-semibold text-slate-900">{workspace.title}</h2>
                            <p className="mt-2 text-sm leading-6 text-slate-500">{workspace.description}</p>
                            <p className="mt-5 text-sm font-semibold text-blue-700">Open workspace</p>
                        </Panel>
                    </Link>
                ))}
            </div>
        </>
    );
}
