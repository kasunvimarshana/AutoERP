import { NavLink } from "react-router-dom";
import { useAuth } from "@/modules/auth/AuthProvider";
import { hasPermission } from "@/modules/auth/accessControl";
import { vehicleRentalPermissions } from "../vehicleRentalPermissions";

const items = [
    {
        to: "/vehicle-rental",
        label: "Overview",
        permissions: [vehicleRentalPermissions.view],
    },
    {
        to: "/vehicle-rental/reservations",
        label: "Reservations",
        permissions: [
            vehicleRentalPermissions.view,
            vehicleRentalPermissions.reservationsManage,
        ],
    },
    {
        to: "/vehicle-rental/agreements",
        label: "Agreements",
        permissions: [
            vehicleRentalPermissions.view,
            vehicleRentalPermissions.agreementsManage,
        ],
    },
    {
        to: "/vehicle-rental/allocations",
        label: "Allocations",
        permissions: [
            vehicleRentalPermissions.view,
            vehicleRentalPermissions.allocationsManage,
        ],
    },
    {
        to: "/vehicle-rental/custody",
        label: "Custody",
        permissions: [
            vehicleRentalPermissions.view,
            vehicleRentalPermissions.custodyManage,
        ],
    },
    {
        to: "/vehicle-rental/running-chart",
        label: "Running Chart",
        permissions: [
            vehicleRentalPermissions.view,
            vehicleRentalPermissions.usageRecord,
            vehicleRentalPermissions.usageApprove,
        ],
    },
    {
        to: "/vehicle-rental/expenses",
        label: "Expenses",
        permissions: [
            vehicleRentalPermissions.financialView,
            vehicleRentalPermissions.expensesRecord,
            vehicleRentalPermissions.expensesApprove,
        ],
    },
    {
        to: "/vehicle-rental/billing",
        label: "Billing",
        permissions: [
            vehicleRentalPermissions.financialView,
            vehicleRentalPermissions.calculationsManage,
            vehicleRentalPermissions.calculationsApprove,
        ],
    },
    {
        to: "/vehicle-rental/deposits",
        label: "Deposits",
        permissions: [
            vehicleRentalPermissions.financialView,
            vehicleRentalPermissions.depositsManage,
        ],
    },
    {
        to: "/vehicle-rental/finance-agreements",
        label: "Vehicle Finance",
        permissions: [
            vehicleRentalPermissions.financialView,
            vehicleRentalPermissions.financeAgreementsManage,
        ],
    },
    {
        to: "/vehicle-rental/reports",
        label: "Reports",
        permissions: [vehicleRentalPermissions.view],
    },
] as const;

export function RentalModuleNav() {
    const auth = useAuth();
    const visible = items.filter((item) =>
        item.permissions.some((permission) => hasPermission(auth, permission)),
    );

    return (
        <nav
            className="mb-6 flex gap-2 overflow-x-auto rounded-xl border border-slate-200 bg-white p-2"
            aria-label="Vehicle Rental"
        >
            {visible.map((item) => (
                <NavLink
                    key={item.to}
                    to={item.to}
                    end={item.to === "/vehicle-rental"}
                    className={({ isActive }) =>
                        `whitespace-nowrap rounded-lg px-3 py-2 text-sm font-medium ${isActive ? "bg-blue-600 text-white" : "text-slate-600 hover:bg-slate-100"}`
                    }
                >
                    {item.label}
                </NavLink>
            ))}
        </nav>
    );
}
