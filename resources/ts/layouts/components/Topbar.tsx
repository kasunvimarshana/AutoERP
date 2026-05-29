import { useSidebarContext } from '../../contexts/SidebarContext';
import { GlobalSearch } from './GlobalSearch';
import { NotificationButton } from './NotificationButton';
import { UserMenu } from './UserMenu';

export function Topbar() {
    const { toggleSidebar } = useSidebarContext();

    return (
        <header className="sticky top-0 z-20 flex h-16 items-center justify-between gap-3 border-b border-slate-100 bg-white/95 px-4 backdrop-blur md:px-6">
            <div className="flex min-w-0 flex-1 items-center gap-3">
                <button
                    aria-label="Open navigation"
                    className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-700 transition hover:bg-slate-50 lg:hidden"
                    onClick={toggleSidebar}
                    type="button"
                >
                    <span className="block h-0.5 w-4 rounded bg-current shadow-[0_5px_0_current,0_-5px_0_current]" />
                </button>
                <GlobalSearch />
            </div>
            <div className="flex shrink-0 items-center gap-2 md:gap-3">
                <NotificationButton />
                <button className="flex h-10 w-10 items-center justify-center rounded-full text-slate-500 transition hover:bg-slate-100" type="button">
                    <span className="text-lg leading-none">...</span>
                </button>
                <UserMenu />
            </div>
        </header>
    );
}
