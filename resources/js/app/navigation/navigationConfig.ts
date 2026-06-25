import type { NavigationSection } from "./navigationTypes";
import { DASHBOARD_PATH, PLATFORM_HOME_PATH } from "../routePaths";
import { PLATFORM_PERMISSION } from '@/app/access/platformPermissions';
import {
    accessPermissions,
    protectedAccessRoles,
} from "@/modules/access/accessPermissions";
import { itemPermissions } from "@/modules/item/itemPermissions";
import { auditPermissions } from "@/modules/audit/auditPermissions";
import { referenceDataPermissions } from "@/modules/reference-data/referenceDataPermissions";
import { tenantPermissions } from "@/modules/tenant/tenantPermissions";
import { paymentPermissions } from "@/modules/payment/paymentPermissions";
import { purchasePermissions } from "@/modules/purchase/purchasePermissions";
import { reportingPermissions } from "@/modules/reporting/reportingPermissions";
import { warehousePermissions } from "@/modules/warehouse/warehousePermissions";
import {
    vehicleRentalNavigationPermissions,
    vehicleRentalPermissions,
} from "@/modules/vehicle-rental/vehicleRentalPermissions";

const tenantAccess = (
    modules: NonNullable<
        NavigationSection["items"][number]["access"]
    >["modules"],
) => ({
    requiresTenant: true,
    modules,
});
const operationalAccess = (
    modules: NonNullable<
        NavigationSection["items"][number]["access"]
    >["modules"],
) => ({
    ...tenantAccess(modules),
    requiresOrganizationUnit: true,
});

const accessControlPermissions = [
    accessPermissions.usersView,
    accessPermissions.usersCreate,
    accessPermissions.usersUpdate,
    accessPermissions.usersAssignRoles,
    accessPermissions.usersManageOrganizationAccess,
    accessPermissions.rolesView,
    accessPermissions.rolesCreate,
    accessPermissions.rolesUpdate,
    accessPermissions.rolesAssignPermissions,
    accessPermissions.permissionsView,
];

const purchaseNavigationPermissions = [
    purchasePermissions.ordersView,
    purchasePermissions.ordersCreate,
    purchasePermissions.goodsReceiptsView,
    purchasePermissions.goodsReceiptsCreate,
    purchasePermissions.supplierInvoicesView,
    purchasePermissions.supplierInvoicesCreate,
    purchasePermissions.paymentsView,
    purchasePermissions.paymentsExecute,
    purchasePermissions.returnsView,
    purchasePermissions.returnsCreate,
    purchasePermissions.debitNotesView,
    purchasePermissions.debitNotesCreate,
    purchasePermissions.fastPurchasesView,
    purchasePermissions.fastPurchasesExecute,
];

const warehouseNavigationPermissions = [
    warehousePermissions.warehousesView,
    warehousePermissions.warehousesCreate,
    warehousePermissions.locationsView,
    warehousePermissions.locationsCreate,
];

export const tenantNavigationSections: NavigationSection[] = [
    {
        id: "primary",
        items: [
            {
                id: "dashboard",
                type: "link",
                label: "Dashboard",
                to: DASHBOARD_PATH,
                icon: "dashboard",
                match: [DASHBOARD_PATH],
            },
        ],
    },
    {
        id: "master-data",
        label: "Master Data",
        items: [
            {
                id: "suppliers",
                type: "module",
                label: "Suppliers",
                icon: "supplier",
                access: tenantAccess(["supplier"]),
                children: [
                    {
                        id: "supplier-list",
                        type: "link",
                        label: "Supplier List",
                        to: "/suppliers",
                        match: ["/suppliers"],
                        access: {
                            ...tenantAccess(["supplier"]),
                            permissions: ["suppliers.view"],
                        },
                    },
                    {
                        id: "supplier-create",
                        type: "link",
                        label: "Create Supplier",
                        to: "/suppliers/create",
                        match: ["/suppliers/create"],
                        access: {
                            ...tenantAccess(["supplier"]),
                            permissions: ["suppliers.create"],
                        },
                    },
                    {
                        id: "supplier-vehicles",
                        type: "link",
                        label: "Supplier Vehicles",
                        to: "/supplier-vehicles",
                        match: ["/supplier-vehicles"],
                        access: {
                            ...tenantAccess(["vehicle", "supplier"]),
                            permissions: ["supplier-vehicles.view"],
                        },
                    },
                    {
                        id: "supplier-vehicle-create",
                        type: "link",
                        label: "Create Supplier Vehicle",
                        to: "/supplier-vehicles/create",
                        match: ["/supplier-vehicles/create"],
                        access: {
                            ...tenantAccess(["vehicle", "supplier"]),
                            permissions: ["supplier-vehicles.create"],
                        },
                    },
                ],
            },
            {
                id: "customers",
                type: "module",
                label: "Customers",
                icon: "customer",
                access: tenantAccess(["customer"]),
                children: [
                    {
                        id: "customer-list",
                        type: "link",
                        label: "Customer List",
                        to: "/customers",
                        match: ["/customers"],
                        access: {
                            ...tenantAccess(["customer"]),
                            permissions: ["customers.view"],
                        },
                    },
                    {
                        id: "customer-create",
                        type: "link",
                        label: "Create Customer",
                        to: "/customers/create",
                        match: ["/customers/create"],
                        access: {
                            ...tenantAccess(["customer"]),
                            permissions: ["customers.create"],
                        },
                    },
                    {
                        id: "customer-vehicles",
                        type: "link",
                        label: "Customer Vehicles",
                        to: "/customer-vehicles",
                        match: ["/customer-vehicles"],
                        access: {
                            ...tenantAccess(["vehicle", "customer"]),
                            permissions: ["customer-vehicles.view"],
                        },
                    },
                    {
                        id: "customer-vehicle-create",
                        type: "link",
                        label: "Create Customer Vehicle",
                        to: "/customer-vehicles/create",
                        match: ["/customer-vehicles/create"],
                        access: {
                            ...tenantAccess(["vehicle", "customer"]),
                            permissions: ["customer-vehicles.create"],
                        },
                    },
                ],
            },
            {
                id: "vehicle",
                type: "module",
                label: "Vehicle",
                icon: "vehicle",
                access: tenantAccess(["vehicle"]),
                children: [
                    {
                        id: "vehicle-makes",
                        type: "link",
                        label: "Makes",
                        to: "/vehicles/makes",
                        match: ["/vehicles/makes"],
                        access: tenantAccess(["vehicle"]),
                    },
                    {
                        id: "vehicle-types",
                        type: "link",
                        label: "Types",
                        to: "/vehicles/types",
                        match: ["/vehicles/types"],
                        access: tenantAccess(["vehicle"]),
                    },
                    {
                        id: "vehicle-categories",
                        type: "link",
                        label: "Categories",
                        to: "/vehicles/categories",
                        match: ["/vehicles/categories"],
                        access: tenantAccess(["vehicle"]),
                    },
                    {
                        id: "vehicle-models",
                        type: "link",
                        label: "Models",
                        to: "/vehicles/models",
                        match: ["/vehicles/models"],
                        access: tenantAccess(["vehicle"]),
                    },
                    {
                        id: "vehicle-list",
                        type: "link",
                        label: "Vehicles",
                        to: "/vehicles",
                        match: ["/vehicles"],
                        access: tenantAccess(["vehicle"]),
                    },
                ],
            },
            {
                id: "items",
                type: "module",
                label: "Items",
                icon: "item",
                access: {
                    ...tenantAccess(["item"]),
                    permissions: [
                        itemPermissions.view,
                        itemPermissions.create,
                        itemPermissions.manageCategories,
                        itemPermissions.manageBrands,
                    ],
                },
                children: [
                    {
                        id: "item-categories",
                        type: "link",
                        label: "Categories",
                        to: "/item-categories",
                        match: ["/item-categories"],
                        access: {
                            ...tenantAccess(["item"]),
                            permissions: [
                                itemPermissions.view,
                                itemPermissions.manageCategories,
                            ],
                        },
                    },
                    {
                        id: "item-category-create",
                        type: "link",
                        label: "Create Category",
                        to: "/item-categories/create",
                        match: ["/item-categories/create"],
                        access: {
                            ...tenantAccess(["item"]),
                            permissions: [itemPermissions.manageCategories],
                        },
                    },
                    {
                        id: "item-brands",
                        type: "link",
                        label: "Brands",
                        to: "/item-brands",
                        match: ["/item-brands"],
                        access: {
                            ...tenantAccess(["item"]),
                            permissions: [
                                itemPermissions.view,
                                itemPermissions.manageBrands,
                            ],
                        },
                    },
                    {
                        id: "item-brand-create",
                        type: "link",
                        label: "Create Brand",
                        to: "/item-brands/create",
                        match: ["/item-brands/create"],
                        access: {
                            ...tenantAccess(["item"]),
                            permissions: [itemPermissions.manageBrands],
                        },
                    },
                    {
                        id: "item-list",
                        type: "link",
                        label: "Items",
                        to: "/items",
                        match: ["/items"],
                        access: {
                            ...tenantAccess(["item"]),
                            permissions: [itemPermissions.view],
                        },
                    },
                    {
                        id: "item-create",
                        type: "link",
                        label: "Create Item",
                        to: "/items/create",
                        match: ["/items/create"],
                        access: {
                            ...tenantAccess(["item"]),
                            permissions: [itemPermissions.create],
                        },
                    },
                ],
            },
        ],
    },
    {
        id: "access-control",
        label: "Access Control",
        items: [
            {
                id: "users",
                type: "module",
                label: "Users",
                icon: "users",
                access: {
                    ...tenantAccess([]),
                    roles: [protectedAccessRoles.superAdmin],
                    permissions: accessControlPermissions,
                },
                children: [
                    {
                        id: "user-list",
                        type: "link",
                        label: "User List",
                        to: "/access/users",
                        access: {
                            ...tenantAccess([]),
                            permissions: [accessPermissions.usersView],
                        },
                    },
                    {
                        id: "roles",
                        type: "link",
                        label: "Roles",
                        to: "/access/roles",
                        icon: "role",
                        access: {
                            ...tenantAccess([]),
                            permissions: [accessPermissions.rolesView],
                        },
                    },
                    {
                        id: "permissions",
                        type: "link",
                        label: "Permissions",
                        to: "/access/permissions",
                        icon: "permission",
                        access: {
                            ...tenantAccess([]),
                            permissions: [accessPermissions.permissionsView],
                        },
                    },
                ],
            },
        ],
    },
    {
        id: "operations",
        label: "Operations",
        items: [
            {
                id: "warehouses",
                type: "module",
                label: "Warehouses",
                icon: "list",
                access: {
                    ...operationalAccess(["warehouse"]),
                    permissions: warehouseNavigationPermissions,
                },
                children: [
                    {
                        id: "warehouse-list",
                        type: "link",
                        label: "Warehouses",
                        to: "/warehouses",
                        match: ["/warehouses"],
                        access: {
                            ...operationalAccess(["warehouse"]),
                            permissions: [warehousePermissions.warehousesView],
                        },
                    },
                    {
                        id: "warehouse-create",
                        type: "link",
                        label: "Create Warehouse",
                        to: "/warehouses/create",
                        match: ["/warehouses/create"],
                        access: {
                            ...operationalAccess(["warehouse"]),
                            permissions: [
                                warehousePermissions.warehousesCreate,
                            ],
                        },
                    },
                    {
                        id: "warehouse-location-list",
                        type: "link",
                        label: "Warehouse Locations",
                        to: "/warehouse-locations",
                        match: ["/warehouse-locations"],
                        access: {
                            ...operationalAccess(["warehouse"]),
                            permissions: [warehousePermissions.locationsView],
                        },
                    },
                    {
                        id: "warehouse-location-create",
                        type: "link",
                        label: "Create Warehouse Location",
                        to: "/warehouse-locations/create",
                        match: ["/warehouse-locations/create"],
                        access: {
                            ...operationalAccess(["warehouse"]),
                            permissions: [warehousePermissions.locationsCreate],
                        },
                    },
                ],
            },
            {
                id: "purchase",
                type: "module",
                label: "Purchase",
                icon: "purchase",
                access: {
                    ...operationalAccess(["purchase"]),
                    permissions: purchaseNavigationPermissions,
                },
                children: [
                    {
                        id: "purchase-orders",
                        type: "link",
                        label: "Purchase Orders",
                        to: "/purchase/orders",
                        match: ["/purchase/orders"],
                        access: {
                            ...operationalAccess(["purchase"]),
                            permissions: [
                                purchasePermissions.ordersView,
                                purchasePermissions.ordersCreate,
                            ],
                        },
                    },
                    {
                        id: "goods-receipts",
                        type: "link",
                        label: "Goods Receipts",
                        to: "/purchase/goods-receipts",
                        match: ["/purchase/goods-receipts"],
                        access: {
                            ...operationalAccess(["purchase"]),
                            permissions: [
                                purchasePermissions.goodsReceiptsView,
                                purchasePermissions.goodsReceiptsCreate,
                            ],
                        },
                    },
                    {
                        id: "supplier-invoices",
                        type: "link",
                        label: "Supplier Invoices",
                        to: "/purchase/invoices",
                        match: ["/purchase/invoices"],
                        access: {
                            ...operationalAccess(["purchase"]),
                            permissions: [
                                purchasePermissions.supplierInvoicesView,
                                purchasePermissions.supplierInvoicesCreate,
                            ],
                        },
                    },
                    {
                        id: "supplier-payments",
                        type: "link",
                        label: "Supplier Payments",
                        to: "/purchase/payments",
                        match: ["/purchase/payments"],
                        access: {
                            ...operationalAccess(["payment", "purchase"]),
                            permissions: [
                                purchasePermissions.paymentsView,
                                purchasePermissions.paymentsExecute,
                            ],
                        },
                    },
                    {
                        id: "purchase-returns",
                        type: "link",
                        label: "Purchase Returns",
                        to: "/purchase/returns",
                        match: [
                            "/purchase/returns",
                            "/purchase/manual-supplier-returns",
                        ],
                        access: {
                            ...operationalAccess(["purchase"]),
                            permissions: [
                                purchasePermissions.returnsView,
                                purchasePermissions.returnsCreate,
                            ],
                        },
                    },
                    {
                        id: "purchase-debit-notes",
                        type: "link",
                        label: "Debit Notes",
                        to: "/purchase/debit-notes",
                        match: ["/purchase/debit-notes"],
                        access: {
                            ...operationalAccess(["purchase"]),
                            permissions: [
                                purchasePermissions.debitNotesView,
                                purchasePermissions.debitNotesCreate,
                            ],
                        },
                    },
                    {
                        id: "fast-purchase",
                        type: "link",
                        label: "Fast Purchase",
                        to: "/purchase/fast-purchase",
                        match: ["/purchase/fast-purchase"],
                        access: {
                            ...operationalAccess(["purchase"]),
                            permissions: [
                                purchasePermissions.fastPurchasesView,
                                purchasePermissions.fastPurchasesExecute,
                            ],
                        },
                    },
                ],
            },
            {
                id: "sales",
                type: "module",
                label: "Sales",
                icon: "sales",
                access: operationalAccess(["sales"]),
                children: [
                    {
                        id: "fast-sales",
                        type: "link",
                        label: "Fast Sales",
                        to: "/sales/fast-sales",
                        match: ["/sales/fast-sales"],
                        access: operationalAccess(["sales"]),
                    },
                    {
                        id: "sales-orders",
                        type: "link",
                        label: "Sales Orders",
                        to: "/sales/orders",
                        match: ["/sales/orders"],
                        access: operationalAccess(["sales"]),
                    },
                    {
                        id: "sales-allocations",
                        type: "link",
                        label: "Stock Allocations",
                        to: "/sales/allocations",
                        match: ["/sales/allocations"],
                        access: operationalAccess(["sales"]),
                    },
                    {
                        id: "sales-deliveries",
                        type: "link",
                        label: "Sales Deliveries",
                        to: "/sales/deliveries",
                        match: ["/sales/deliveries"],
                        access: operationalAccess(["sales"]),
                    },
                    {
                        id: "sales-returns",
                        type: "link",
                        label: "Sales Returns",
                        to: "/sales/returns",
                        match: ["/sales/returns"],
                        access: operationalAccess(["sales"]),
                    },
                    {
                        id: "customer-invoices",
                        type: "link",
                        label: "Customer Invoices",
                        to: "/invoices?view=customer",
                        match: ["/invoices"],
                        access: operationalAccess(["invoice", "sales"]),
                    },
                    {
                        id: "customer-receipts",
                        type: "link",
                        label: "Customer Receipts",
                        to: "/payments?view=customer",
                        match: ["/payments"],
                        exclude: [
                            "/payments/create",
                            "/payments/cheque-templates",
                        ],
                        access: operationalAccess(["payment", "sales"]),
                    },
                ],
            },
            {
                id: "vehicle-service",
                type: "module",
                label: "Vehicle Service",
                icon: "service",
                access: operationalAccess(["vehicle-service"]),
                children: [
                    {
                        id: "service-jobs",
                        type: "link",
                        label: "Service Jobs",
                        to: "/vehicle-service/jobs",
                        match: ["/vehicle-service/jobs"],
                        access: operationalAccess(["vehicle-service"]),
                    },
                    {
                        id: "service-invoices",
                        type: "link",
                        label: "Service Invoices",
                        to: "/invoices?view=service",
                        match: ["/invoices"],
                        access: operationalAccess([
                            "invoice",
                            "vehicle-service",
                        ]),
                    },
                    {
                        id: "service-receipts",
                        type: "link",
                        label: "Customer Receipts",
                        to: "/payments?view=service",
                        match: ["/payments"],
                        exclude: [
                            "/payments/create",
                            "/payments/cheque-templates",
                        ],
                        access: operationalAccess([
                            "payment",
                            "vehicle-service",
                        ]),
                    },
                ],
            },
            {
                id: "vehicle-rental",
                type: "module",
                label: "Vehicle Rental",
                icon: "rental",
                access: {
                    ...operationalAccess(["vehicle-rental"]),
                    permissions: vehicleRentalNavigationPermissions,
                },
                children: [
                    {
                        id: "rental-overview",
                        type: "link",
                        label: "Overview",
                        to: "/vehicle-rental",
                        match: ["/vehicle-rental"],
                        exclude: [
                            "/vehicle-rental/reservations",
                            "/vehicle-rental/agreements",
                            "/vehicle-rental/allocations",
                            "/vehicle-rental/custody",
                            "/vehicle-rental/running-chart",
                            "/vehicle-rental/expenses",
                            "/vehicle-rental/billing",
                            "/vehicle-rental/deposits",
                            "/vehicle-rental/finance-agreements",
                            "/vehicle-rental/availability",
                            "/vehicle-rental/reports",
                        ],
                        access: {
                            ...operationalAccess(["vehicle-rental"]),
                            permissions: [vehicleRentalPermissions.view],
                        },
                    },
                    {
                        id: "rental-reservations",
                        type: "link",
                        label: "Reservations",
                        to: "/vehicle-rental/reservations",
                        match: ["/vehicle-rental/reservations"],
                        access: {
                            ...operationalAccess(["vehicle-rental"]),
                            permissions: [
                                vehicleRentalPermissions.view,
                                vehicleRentalPermissions.reservationsManage,
                            ],
                        },
                    },
                    {
                        id: "rental-agreements",
                        type: "link",
                        label: "Agreements",
                        to: "/vehicle-rental/agreements",
                        match: ["/vehicle-rental/agreements"],
                        access: {
                            ...operationalAccess(["vehicle-rental"]),
                            permissions: [
                                vehicleRentalPermissions.view,
                                vehicleRentalPermissions.agreementsManage,
                            ],
                        },
                    },
                    {
                        id: "rental-allocations",
                        type: "link",
                        label: "Vehicle Allocations",
                        to: "/vehicle-rental/allocations",
                        match: ["/vehicle-rental/allocations"],
                        access: {
                            ...operationalAccess(["vehicle-rental"]),
                            permissions: [
                                vehicleRentalPermissions.view,
                                vehicleRentalPermissions.allocationsManage,
                            ],
                        },
                    },
                    {
                        id: "rental-custody",
                        type: "link",
                        label: "Handover & Return",
                        to: "/vehicle-rental/custody",
                        match: ["/vehicle-rental/custody"],
                        access: {
                            ...operationalAccess(["vehicle-rental"]),
                            permissions: [
                                vehicleRentalPermissions.view,
                                vehicleRentalPermissions.custodyManage,
                            ],
                        },
                    },
                    {
                        id: "rental-running-chart",
                        type: "link",
                        label: "Daily Running Chart",
                        to: "/vehicle-rental/running-chart",
                        match: ["/vehicle-rental/running-chart"],
                        access: {
                            ...operationalAccess(["vehicle-rental"]),
                            permissions: [
                                vehicleRentalPermissions.view,
                                vehicleRentalPermissions.usageRecord,
                                vehicleRentalPermissions.usageApprove,
                            ],
                        },
                    },
                    {
                        id: "rental-expenses",
                        type: "link",
                        label: "Expenses & Deductions",
                        to: "/vehicle-rental/expenses",
                        match: ["/vehicle-rental/expenses"],
                        access: {
                            ...operationalAccess(["vehicle-rental"]),
                            permissions: [
                                vehicleRentalPermissions.financialView,
                                vehicleRentalPermissions.expensesRecord,
                                vehicleRentalPermissions.expensesApprove,
                            ],
                        },
                    },
                    {
                        id: "rental-billing",
                        type: "link",
                        label: "Billing & Owner Cost",
                        to: "/vehicle-rental/billing",
                        match: ["/vehicle-rental/billing"],
                        access: {
                            ...operationalAccess(["vehicle-rental"]),
                            permissions: [
                                vehicleRentalPermissions.financialView,
                                vehicleRentalPermissions.calculationsManage,
                                vehicleRentalPermissions.calculationsApprove,
                            ],
                        },
                    },
                    {
                        id: "rental-deposits",
                        type: "link",
                        label: "Security Deposits",
                        to: "/vehicle-rental/deposits",
                        match: ["/vehicle-rental/deposits"],
                        access: {
                            ...operationalAccess(["vehicle-rental"]),
                            permissions: [
                                vehicleRentalPermissions.financialView,
                                vehicleRentalPermissions.depositsManage,
                            ],
                        },
                    },
                    {
                        id: "rental-finance",
                        type: "link",
                        label: "Vehicle Finance",
                        to: "/vehicle-rental/finance-agreements",
                        match: ["/vehicle-rental/finance-agreements"],
                        access: {
                            ...operationalAccess(["vehicle-rental"]),
                            permissions: [
                                vehicleRentalPermissions.financialView,
                                vehicleRentalPermissions.financeAgreementsManage,
                            ],
                        },
                    },
                    {
                        id: "rental-availability",
                        type: "link",
                        label: "Vehicle Availability",
                        to: "/vehicle-rental/availability",
                        match: ["/vehicle-rental/availability"],
                        access: {
                            ...operationalAccess(["vehicle-rental"]),
                            permissions: [vehicleRentalPermissions.view],
                        },
                    },
                    {
                        id: "rental-reports",
                        type: "link",
                        label: "Rental Reports",
                        to: "/vehicle-rental/reports",
                        match: ["/vehicle-rental/reports"],
                        access: {
                            ...operationalAccess(["vehicle-rental"]),
                            permissions: [vehicleRentalPermissions.view],
                        },
                    },
                    {
                        id: "owner-payables",
                        type: "link",
                        label: "Owner Payables",
                        to: "/invoices?view=rental-payable",
                        match: ["/invoices"],
                        access: operationalAccess([
                            "invoice",
                            "vehicle-rental",
                        ]),
                    },
                    {
                        id: "rental-invoices",
                        type: "link",
                        label: "Customer Invoices",
                        to: "/invoices?view=rental-customer",
                        match: ["/invoices"],
                        access: operationalAccess([
                            "invoice",
                            "vehicle-rental",
                        ]),
                    },
                    {
                        id: "rental-settlements",
                        type: "link",
                        label: "Settlements",
                        to: "/payments?view=rental",
                        match: ["/payments"],
                        exclude: [
                            "/payments/create",
                            "/payments/cheque-templates",
                        ],
                        access: operationalAccess([
                            "payment",
                            "vehicle-rental",
                        ]),
                    },
                ],
            },
        ],
    },
    {
        id: "finance",
        label: "Finance",
        items: [
            {
                id: "invoices",
                type: "link",
                label: "Invoices",
                to: "/invoices",
                icon: "invoice",
                access: tenantAccess(["invoice"]),
            },
            {
                id: "reports",
                type: "link",
                label: "Reports",
                to: "/reports",
                icon: "list",
                access: {
                    ...operationalAccess([]),
                    permissions: [reportingPermissions.view],
                },
            },
            {
                id: "payments",
                type: "module",
                label: "Payments",
                icon: "payment",
                access: {
                    ...tenantAccess(["payment"]),
                    permissions: [
                        paymentPermissions.view,
                        paymentPermissions.create,
                        paymentPermissions.methodsView,
                        paymentPermissions.templatesView,
                    ],
                },
                children: [
                    {
                        id: "payments-list",
                        type: "link",
                        label: "Payments",
                        to: "/payments",
                        match: ["/payments"],
                        exclude: [
                            "/payments/create",
                            "/payments/methods",
                            "/payments/cheque-templates",
                        ],
                        access: {
                            ...tenantAccess(["payment"]),
                            permissions: [paymentPermissions.view],
                        },
                    },
                    {
                        id: "payments-create",
                        type: "link",
                        label: "Create Payment",
                        to: "/payments/create",
                        match: ["/payments/create"],
                        access: {
                            ...tenantAccess(["payment"]),
                            permissions: [paymentPermissions.create],
                        },
                    },
                    {
                        id: "payment-methods",
                        type: "link",
                        label: "Payment Methods",
                        to: "/payments/methods",
                        match: ["/payments/methods"],
                        exclude: ["/payments/methods/create"],
                        access: {
                            ...tenantAccess(["payment"]),
                            permissions: [paymentPermissions.methodsView],
                        },
                    },
                    {
                        id: "payment-methods-create",
                        type: "link",
                        label: "Create Payment Method",
                        to: "/payments/methods/create",
                        match: ["/payments/methods/create"],
                        access: {
                            ...tenantAccess(["payment"]),
                            permissions: [paymentPermissions.methodsCreate],
                        },
                    },
                    {
                        id: "cheque-templates",
                        type: "link",
                        label: "Cheque Templates",
                        to: "/payments/cheque-templates",
                        match: ["/payments/cheque-templates"],
                        exclude: ["/payments/cheque-templates/create"],
                        access: {
                            ...tenantAccess(["payment"]),
                            permissions: [paymentPermissions.templatesView],
                        },
                    },
                    {
                        id: "cheque-templates-create",
                        type: "link",
                        label: "Create Cheque Template",
                        to: "/payments/cheque-templates/create",
                        match: ["/payments/cheque-templates/create"],
                        access: {
                            ...tenantAccess(["payment"]),
                            permissions: [paymentPermissions.templatesCreate],
                        },
                    },
                ],
            },
            {
                id: "vouchers",
                type: "link",
                label: "Vouchers",
                to: "/vouchers",
                icon: "voucher",
                access: tenantAccess([]),
            },
        ],
    },
    {
        id: "administration",
        label: "Administration",
        items: [
            {
                id: "users-access",
                type: "link",
                label: "Users & Access",
                to: "/administration/access",
                icon: "users",
                access: {
                    ...tenantAccess([]),
                    roles: [protectedAccessRoles.superAdmin],
                    permissions: accessControlPermissions,
                },
            },
            {
                id: "tenant-administration",
                type: "link",
                label: "Tenant Administration",
                to: "/administration/tenant",
                icon: "settings",
                access: {
                    requiresTenant: true,
                    permissions: [
                        tenantPermissions.profileView,
                        tenantPermissions.domainsView,
                        tenantPermissions.documentsView,
                    ],
                },
            },
            {
                id: "audit-logs",
                type: "link",
                label: "Audit Logs",
                to: "/administration/audit-logs",
                icon: "list",
                access: {
                    requiresTenant: true,
                    permissions: [auditPermissions.view],
                },
            },
            {
                id: "reference-data",
                type: "link",
                label: "Reference Data",
                to: "/reference-data",
                icon: "list",
                access: {
                    requiresTenant: true,
                    permissions: [referenceDataPermissions.view],
                },
            },
            {
                id: "settings",
                type: "link",
                label: "Settings",
                to: "/settings",
                icon: "settings",
                access: {
                    ...tenantAccess([]),
                    permissions: ["configuration.entries.view"],
                },
            },
        ],
    },
];


export const platformNavigationSections: NavigationSection[] = [
    {
        id: "platform",
        label: "Platform Administration",
        items: [
            {
                id: "saas-tenants",
                type: "link",
                label: "SaaS Tenants",
                to: PLATFORM_HOME_PATH,
                icon: "users",
                access: { requiresPlatformOperator: true, permissions: [PLATFORM_PERMISSION.tenantsView] },
            },
            {
                id: "tenant-plans",
                type: "link",
                label: "Tenant Plans",
                to: "/administration/tenant-plans",
                icon: "list",
                access: { requiresPlatformOperator: true, permissions: [PLATFORM_PERMISSION.plansView] },
            },
            {
                id: "platform-configuration",
                type: "link",
                label: "Platform Defaults",
                to: "/administration/platform-configuration",
                icon: "settings",
                access: { requiresPlatformOperator: true, permissions: [PLATFORM_PERMISSION.configurationView] },
            },
            {
                id: "platform-operators",
                type: "link",
                label: "Operators & Permissions",
                to: "/administration/platform-operators",
                icon: "users",
                access: { requiresPlatformOperator: true, permissions: [PLATFORM_PERMISSION.operatorsView] },
            },
            {
                id: "platform-security",
                type: "link",
                label: "Sessions & MFA",
                to: "/administration/platform-security",
                icon: "permission",
                access: { requiresPlatformOperator: true, permissions: [PLATFORM_PERMISSION.sessionsView] },
            },
            {
                id: "platform-audit",
                type: "link",
                label: "Platform Audit",
                to: "/administration/platform-audit",
                icon: "list",
                access: { requiresPlatformOperator: true, permissions: [PLATFORM_PERMISSION.auditView] },
            },
            {
                id: "platform-health",
                type: "link",
                label: "Platform Health",
                to: "/administration/platform-health",
                icon: "dashboard",
                access: { requiresPlatformOperator: true, permissions: [PLATFORM_PERMISSION.healthView] },
            },
        ],
    },
];
