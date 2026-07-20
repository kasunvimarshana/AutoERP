export const vehicleRentalPermissions = {
    agreementsView: 'vehicle_rental.agreements.view',
    agreementsManage: 'vehicle_rental.agreements.manage',
    assignmentsView: 'vehicle_rental.assignments.view',
    assignmentsManage: 'vehicle_rental.assignments.manage',
    runningChartsView: 'vehicle_rental.running_charts.view',
    runningChartsManage: 'vehicle_rental.running_charts.manage',
    calculationsView: 'vehicle_rental.calculations.view',
    calculationsManage: 'vehicle_rental.calculations.manage',
    financialDocumentsManage: 'vehicle_rental.financial_documents.manage',
    reportsView: 'vehicle_rental.reports.view',
} as const;

export const vehicleRentalViewPermissions = [
    vehicleRentalPermissions.agreementsView,
    vehicleRentalPermissions.assignmentsView,
    vehicleRentalPermissions.runningChartsView,
    vehicleRentalPermissions.calculationsView,
    vehicleRentalPermissions.reportsView,
] as const;
