import type { SVGProps } from 'react';
import type { NavigationIconName } from './navigationTypes';

const paths: Record<NavigationIconName, string[]> = {
    dashboard: ['M3 12l9-9 9 9', 'M5 10v10h14V10', 'M9 20v-6h6v6'],
    supplier: ['M4 20h16', 'M6 20v-9h12v9', 'M8 11V7h8v4', 'M9 15h2', 'M13 15h2'],
    customer: ['M16 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2', 'M9.5 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8', 'M17 11l2 2 4-4'],
    item: ['M4 7l8-4 8 4-8 4-8-4Z', 'M4 7v10l8 4 8-4V7', 'M12 11v10'],
    users: ['M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2', 'M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8', 'M22 21v-2a4 4 0 0 0-3-3.87', 'M16 3.13a4 4 0 0 1 0 7.75'],
    purchase: ['M3 3h2l2.4 11.2a2 2 0 0 0 2 1.6h7.8a2 2 0 0 0 2-1.6L21 7H6', 'M10 21a1 1 0 1 0 0-2 1 1 0 0 0 0 2', 'M18 21a1 1 0 1 0 0-2 1 1 0 0 0 0 2'],
    service: ['M14.7 6.3a4 4 0 0 0-5-5L7 4l3 3 2.7-2.7a4 4 0 0 0 2 5L7 17l-2 4-2-2 4-2 7.7-7.7Z'],
    rental: ['M5 17h14l-1-5-2-3H8l-2 3-1 5Z', 'M7 17v2', 'M17 17v2', 'M8 9l1-4h6l1 4', 'M5 13h2', 'M17 13h2'],
    invoice: ['M6 2h9l4 4v16H6z', 'M14 2v5h5', 'M9 12h6', 'M9 16h6'],
    payment: ['M3 6h18v12H3z', 'M3 10h18', 'M7 15h3'],
    voucher: ['M4 4h16v16H4z', 'M8 9h8', 'M8 13h5', 'M8 17h3'],
    settings: ['M12 15.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Z', 'M19.4 15a1.7 1.7 0 0 0 .34 1.88l.06.06-2 3.46-.09-.03a1.7 1.7 0 0 0-1.9.24l-.48.28a1.7 1.7 0 0 0-.82 1.73V22h-4v-.38a1.7 1.7 0 0 0-.82-1.73l-.48-.28a1.7 1.7 0 0 0-1.9-.24l-.09.03-2-3.46.06-.06A1.7 1.7 0 0 0 4.6 15v-.56a1.7 1.7 0 0 0-1.08-1.64L3 12.76v-4l.52-.16A1.7 1.7 0 0 0 4.6 7v-.56a1.7 1.7 0 0 0-.34-1.88L4.2 4.5l2-3.46.09.03a1.7 1.7 0 0 0 1.9-.24l.48-.28A1.7 1.7 0 0 0 9.49 0H14.5v.38a1.7 1.7 0 0 0 .82 1.73l.48.28a1.7 1.7 0 0 0 1.9.24l.09-.03 2 3.46-.06.06A1.7 1.7 0 0 0 19.4 8v.56a1.7 1.7 0 0 0 1.08 1.64l.52.16v4l-.52.16A1.7 1.7 0 0 0 19.4 15Z'],
    list: ['M8 6h13', 'M8 12h13', 'M8 18h13', 'M3 6h.01', 'M3 12h.01', 'M3 18h.01'],
    vehicle: ['M5 17h14l-1-5-2-3H8l-2 3-1 5Z', 'M7 17v2', 'M17 17v2', 'M7 13h10'],
    role: ['M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20Z', 'M8 12l2.5 2.5L16 9'],
    permission: ['M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z', 'M9 12l2 2 4-4'],
};

export function NavigationIcon({
    name,
    className = 'h-5 w-5',
    ...props
}: SVGProps<SVGSVGElement> & { name: NavigationIconName }) {
    return (
        <svg
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="1.8"
            strokeLinecap="round"
            strokeLinejoin="round"
            className={className}
            aria-hidden="true"
            {...props}
        >
            {paths[name].map((path) => <path key={path} d={path} />)}
        </svg>
    );
}
