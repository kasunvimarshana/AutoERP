export const vehicleRentalPermissions = {
    view: "vehicle-rental.view",
    financialView: "vehicle-rental.financial.view",
    profitabilityView: "vehicle-rental.profitability.view",
    reservationsManage: "vehicle-rental.reservations.manage",
    agreementsManage: "vehicle-rental.agreements.manage",
    ratesManage: "vehicle-rental.rates.manage",
    allocationsManage: "vehicle-rental.allocations.manage",
    custodyManage: "vehicle-rental.custody.manage",
    replacementsManage: "vehicle-rental.replacements.manage",
    usageRecord: "vehicle-rental.usage.record",
    usageApprove: "vehicle-rental.usage.approve",
    expensesRecord: "vehicle-rental.expenses.record",
    expensesApprove: "vehicle-rental.expenses.approve",
    calculationsManage: "vehicle-rental.calculations.manage",
    calculationsApprove: "vehicle-rental.calculations.approve",
    financialCreate: "vehicle-rental.financial.create",
    depositsManage: "vehicle-rental.deposits.manage",
    financeAgreementsManage: "vehicle-rental.finance-agreements.manage",
} as const;

export const vehicleRentalNavigationPermissions = Object.values(
    vehicleRentalPermissions,
);
