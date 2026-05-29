import { Link, useLocation } from 'react-router-dom';

export function Breadcrumbs() {
    const location = useLocation();
    const parts = location.pathname.split('/').filter(Boolean);

    return (
        <div className="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-slate-400">
            <Link to="/dashboard">Home</Link>
            {parts.map((part, index) => (
                <span className="flex items-center gap-2" key={`${part}-${index}`}>
                    <span>/</span>
                    <span>{part.replaceAll('-', ' ')}</span>
                </span>
            ))}
        </div>
    );
}
