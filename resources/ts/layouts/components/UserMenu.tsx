import { useAuthContext } from '../../contexts/AuthContext';

export function UserMenu() {
    const { user } = useAuthContext();

    return (
        <div className="flex items-center gap-3 border-l border-slate-100 pl-6">
            <div className="text-right">
                <p className="text-sm font-bold text-slate-950">{user.name}</p>
                <p className="text-[11px] font-semibold uppercase text-slate-400">{user.role}</p>
            </div>
            <div className="h-9 w-9 overflow-hidden rounded-full bg-gradient-to-br from-slate-900 to-blue-700">
                <div className="flex h-full w-full items-center justify-center text-xs font-bold text-white">JW</div>
            </div>
        </div>
    );
}
