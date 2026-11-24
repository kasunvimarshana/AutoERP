/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

export const hrPermissions = {
    employeesView: 'hr.employees.view',
    employeesCreate: 'hr.employees.create',
    employeesUpdate: 'hr.employees.update',
    employeesDelete: 'hr.employees.delete',
    masterDataView: 'hr.master-data.view',
    masterDataManage: 'hr.master-data.manage',
    shiftsView: 'hr.shifts.view',
    shiftsManage: 'hr.shifts.manage',
    attendanceView: 'hr.attendance.view',
    attendanceManage: 'hr.attendance.manage',
    attendancePunch: 'hr.attendance.punch',
    attendanceRegularize: 'hr.attendance.regularize',
    leavesView: 'hr.leaves.view',
    leavesApply: 'hr.leaves.apply',
    leavesManage: 'hr.leaves.manage',
    biometricView: 'hr.biometric.view',
    biometricManage: 'hr.biometric.manage',
    biometricSync: 'hr.biometric.sync',
} as const;
