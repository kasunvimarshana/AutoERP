<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\HR\Application\Contracts\UseCases\AttendanceLogs\CreateAttendanceLogServiceInterface;
use Modules\HR\Application\Contracts\UseCases\AttendanceLogs\DeleteAttendanceLogServiceInterface;
use Modules\HR\Application\Contracts\UseCases\AttendanceLogs\GetAttendanceLogServiceInterface;
use Modules\HR\Application\Contracts\UseCases\AttendanceLogs\ListAttendanceLogsServiceInterface;
use Modules\HR\Application\Contracts\UseCases\AttendanceLogs\UpdateAttendanceLogServiceInterface;
use Modules\HR\Application\Contracts\UseCases\AttendanceRecords\CreateAttendanceRecordServiceInterface;
use Modules\HR\Application\Contracts\UseCases\AttendanceRecords\DeleteAttendanceRecordServiceInterface;
use Modules\HR\Application\Contracts\UseCases\AttendanceRecords\GetAttendanceRecordServiceInterface;
use Modules\HR\Application\Contracts\UseCases\AttendanceRecords\ListAttendanceRecordsServiceInterface;
use Modules\HR\Application\Contracts\UseCases\AttendanceRecords\UpdateAttendanceRecordServiceInterface;
use Modules\HR\Application\Contracts\UseCases\BiometricDevices\CreateBiometricDeviceServiceInterface;
use Modules\HR\Application\Contracts\UseCases\BiometricDevices\DeleteBiometricDeviceServiceInterface;
use Modules\HR\Application\Contracts\UseCases\BiometricDevices\GetBiometricDeviceServiceInterface;
use Modules\HR\Application\Contracts\UseCases\BiometricDevices\ListBiometricDevicesServiceInterface;
use Modules\HR\Application\Contracts\UseCases\BiometricDevices\UpdateBiometricDeviceServiceInterface;
use Modules\HR\Application\Contracts\UseCases\Departments\CreateDepartmentServiceInterface;
use Modules\HR\Application\Contracts\UseCases\Departments\DeleteDepartmentServiceInterface;
use Modules\HR\Application\Contracts\UseCases\Departments\GetDepartmentServiceInterface;
use Modules\HR\Application\Contracts\UseCases\Departments\ListDepartmentsServiceInterface;
use Modules\HR\Application\Contracts\UseCases\Departments\UpdateDepartmentServiceInterface;
use Modules\HR\Application\Contracts\UseCases\Designations\CreateDesignationServiceInterface;
use Modules\HR\Application\Contracts\UseCases\Designations\DeleteDesignationServiceInterface;
use Modules\HR\Application\Contracts\UseCases\Designations\GetDesignationServiceInterface;
use Modules\HR\Application\Contracts\UseCases\Designations\ListDesignationsServiceInterface;
use Modules\HR\Application\Contracts\UseCases\Designations\UpdateDesignationServiceInterface;
use Modules\HR\Application\Contracts\UseCases\EmployeeContacts\CreateEmployeeContactServiceInterface;
use Modules\HR\Application\Contracts\UseCases\EmployeeContacts\DeleteEmployeeContactServiceInterface;
use Modules\HR\Application\Contracts\UseCases\EmployeeContacts\GetEmployeeContactServiceInterface;
use Modules\HR\Application\Contracts\UseCases\EmployeeContacts\ListEmployeeContactsServiceInterface;
use Modules\HR\Application\Contracts\UseCases\EmployeeContacts\UpdateEmployeeContactServiceInterface;
use Modules\HR\Application\Contracts\UseCases\EmployeeContracts\CreateEmployeeContractServiceInterface;
use Modules\HR\Application\Contracts\UseCases\EmployeeContracts\DeleteEmployeeContractServiceInterface;
use Modules\HR\Application\Contracts\UseCases\EmployeeContracts\GetEmployeeContractServiceInterface;
use Modules\HR\Application\Contracts\UseCases\EmployeeContracts\ListEmployeeContractsServiceInterface;
use Modules\HR\Application\Contracts\UseCases\EmployeeContracts\UpdateEmployeeContractServiceInterface;
use Modules\HR\Application\Contracts\UseCases\EmployeeDocuments\CreateEmployeeDocumentServiceInterface;
use Modules\HR\Application\Contracts\UseCases\EmployeeDocuments\DeleteEmployeeDocumentServiceInterface;
use Modules\HR\Application\Contracts\UseCases\EmployeeDocuments\GetEmployeeDocumentServiceInterface;
use Modules\HR\Application\Contracts\UseCases\EmployeeDocuments\ListEmployeeDocumentsServiceInterface;
use Modules\HR\Application\Contracts\UseCases\EmployeeDocuments\UpdateEmployeeDocumentServiceInterface;
use Modules\HR\Application\Contracts\UseCases\Employees\CreateEmployeeServiceInterface;
use Modules\HR\Application\Contracts\UseCases\Employees\DeleteEmployeeServiceInterface;
use Modules\HR\Application\Contracts\UseCases\Employees\GetEmployeeServiceInterface;
use Modules\HR\Application\Contracts\UseCases\Employees\ListEmployeesServiceInterface;
use Modules\HR\Application\Contracts\UseCases\Employees\UpdateEmployeeServiceInterface;
use Modules\HR\Application\Contracts\UseCases\EmployeeSalaryAssignments\CreateEmployeeSalaryAssignmentServiceInterface;
use Modules\HR\Application\Contracts\UseCases\EmployeeSalaryAssignments\DeleteEmployeeSalaryAssignmentServiceInterface;
use Modules\HR\Application\Contracts\UseCases\EmployeeSalaryAssignments\GetEmployeeSalaryAssignmentServiceInterface;
use Modules\HR\Application\Contracts\UseCases\EmployeeSalaryAssignments\ListEmployeeSalaryAssignmentsServiceInterface;
use Modules\HR\Application\Contracts\UseCases\EmployeeSalaryAssignments\UpdateEmployeeSalaryAssignmentServiceInterface;
use Modules\HR\Application\Contracts\UseCases\EmploymentTypes\CreateEmploymentTypeServiceInterface;
use Modules\HR\Application\Contracts\UseCases\EmploymentTypes\DeleteEmploymentTypeServiceInterface;
use Modules\HR\Application\Contracts\UseCases\EmploymentTypes\GetEmploymentTypeServiceInterface;
use Modules\HR\Application\Contracts\UseCases\EmploymentTypes\ListEmploymentTypesServiceInterface;
use Modules\HR\Application\Contracts\UseCases\EmploymentTypes\UpdateEmploymentTypeServiceInterface;
use Modules\HR\Application\Contracts\UseCases\Holidays\CreateHolidayServiceInterface;
use Modules\HR\Application\Contracts\UseCases\Holidays\DeleteHolidayServiceInterface;
use Modules\HR\Application\Contracts\UseCases\Holidays\GetHolidayServiceInterface;
use Modules\HR\Application\Contracts\UseCases\Holidays\ListHolidaysServiceInterface;
use Modules\HR\Application\Contracts\UseCases\Holidays\UpdateHolidayServiceInterface;
use Modules\HR\Application\Contracts\UseCases\LeaveAllocations\CreateLeaveAllocationServiceInterface;
use Modules\HR\Application\Contracts\UseCases\LeaveAllocations\DeleteLeaveAllocationServiceInterface;
use Modules\HR\Application\Contracts\UseCases\LeaveAllocations\GetLeaveAllocationServiceInterface;
use Modules\HR\Application\Contracts\UseCases\LeaveAllocations\ListLeaveAllocationsServiceInterface;
use Modules\HR\Application\Contracts\UseCases\LeaveAllocations\UpdateLeaveAllocationServiceInterface;
use Modules\HR\Application\Contracts\UseCases\LeaveApplications\CreateLeaveApplicationServiceInterface;
use Modules\HR\Application\Contracts\UseCases\LeaveApplications\DeleteLeaveApplicationServiceInterface;
use Modules\HR\Application\Contracts\UseCases\LeaveApplications\GetLeaveApplicationServiceInterface;
use Modules\HR\Application\Contracts\UseCases\LeaveApplications\ListLeaveApplicationsServiceInterface;
use Modules\HR\Application\Contracts\UseCases\LeaveApplications\UpdateLeaveApplicationServiceInterface;
use Modules\HR\Application\Contracts\UseCases\LeavePolicies\CreateLeavePolicyServiceInterface;
use Modules\HR\Application\Contracts\UseCases\LeavePolicies\DeleteLeavePolicyServiceInterface;
use Modules\HR\Application\Contracts\UseCases\LeavePolicies\GetLeavePolicyServiceInterface;
use Modules\HR\Application\Contracts\UseCases\LeavePolicies\ListLeavePoliciesServiceInterface;
use Modules\HR\Application\Contracts\UseCases\LeavePolicies\UpdateLeavePolicyServiceInterface;
use Modules\HR\Application\Contracts\UseCases\LeavePolicyLines\CreateLeavePolicyLineServiceInterface;
use Modules\HR\Application\Contracts\UseCases\LeavePolicyLines\DeleteLeavePolicyLineServiceInterface;
use Modules\HR\Application\Contracts\UseCases\LeavePolicyLines\GetLeavePolicyLineServiceInterface;
use Modules\HR\Application\Contracts\UseCases\LeavePolicyLines\ListLeavePolicyLinesServiceInterface;
use Modules\HR\Application\Contracts\UseCases\LeavePolicyLines\UpdateLeavePolicyLineServiceInterface;
use Modules\HR\Application\Contracts\UseCases\LeaveTypes\CreateLeaveTypeServiceInterface;
use Modules\HR\Application\Contracts\UseCases\LeaveTypes\DeleteLeaveTypeServiceInterface;
use Modules\HR\Application\Contracts\UseCases\LeaveTypes\GetLeaveTypeServiceInterface;
use Modules\HR\Application\Contracts\UseCases\LeaveTypes\ListLeaveTypesServiceInterface;
use Modules\HR\Application\Contracts\UseCases\LeaveTypes\UpdateLeaveTypeServiceInterface;
use Modules\HR\Application\Contracts\UseCases\PayrollRuns\CreatePayrollRunServiceInterface;
use Modules\HR\Application\Contracts\UseCases\PayrollRuns\DeletePayrollRunServiceInterface;
use Modules\HR\Application\Contracts\UseCases\PayrollRuns\GetPayrollRunServiceInterface;
use Modules\HR\Application\Contracts\UseCases\PayrollRuns\ListPayrollRunsServiceInterface;
use Modules\HR\Application\Contracts\UseCases\PayrollRuns\UpdatePayrollRunServiceInterface;
use Modules\HR\Application\Contracts\UseCases\PayslipLines\CreatePayslipLineServiceInterface;
use Modules\HR\Application\Contracts\UseCases\PayslipLines\DeletePayslipLineServiceInterface;
use Modules\HR\Application\Contracts\UseCases\PayslipLines\GetPayslipLineServiceInterface;
use Modules\HR\Application\Contracts\UseCases\PayslipLines\ListPayslipLinesServiceInterface;
use Modules\HR\Application\Contracts\UseCases\PayslipLines\UpdatePayslipLineServiceInterface;
use Modules\HR\Application\Contracts\UseCases\Payslips\CreatePayslipServiceInterface;
use Modules\HR\Application\Contracts\UseCases\Payslips\DeletePayslipServiceInterface;
use Modules\HR\Application\Contracts\UseCases\Payslips\GetPayslipServiceInterface;
use Modules\HR\Application\Contracts\UseCases\Payslips\ListPayslipsServiceInterface;
use Modules\HR\Application\Contracts\UseCases\Payslips\UpdatePayslipServiceInterface;
use Modules\HR\Application\Contracts\UseCases\PerformanceCycles\CreatePerformanceCycleServiceInterface;
use Modules\HR\Application\Contracts\UseCases\PerformanceCycles\DeletePerformanceCycleServiceInterface;
use Modules\HR\Application\Contracts\UseCases\PerformanceCycles\GetPerformanceCycleServiceInterface;
use Modules\HR\Application\Contracts\UseCases\PerformanceCycles\ListPerformanceCyclesServiceInterface;
use Modules\HR\Application\Contracts\UseCases\PerformanceCycles\UpdatePerformanceCycleServiceInterface;
use Modules\HR\Application\Contracts\UseCases\PerformanceReviews\CreatePerformanceReviewServiceInterface;
use Modules\HR\Application\Contracts\UseCases\PerformanceReviews\DeletePerformanceReviewServiceInterface;
use Modules\HR\Application\Contracts\UseCases\PerformanceReviews\GetPerformanceReviewServiceInterface;
use Modules\HR\Application\Contracts\UseCases\PerformanceReviews\ListPerformanceReviewsServiceInterface;
use Modules\HR\Application\Contracts\UseCases\PerformanceReviews\UpdatePerformanceReviewServiceInterface;
use Modules\HR\Application\Contracts\UseCases\SalaryComponents\CreateSalaryComponentServiceInterface;
use Modules\HR\Application\Contracts\UseCases\SalaryComponents\DeleteSalaryComponentServiceInterface;
use Modules\HR\Application\Contracts\UseCases\SalaryComponents\GetSalaryComponentServiceInterface;
use Modules\HR\Application\Contracts\UseCases\SalaryComponents\ListSalaryComponentsServiceInterface;
use Modules\HR\Application\Contracts\UseCases\SalaryComponents\UpdateSalaryComponentServiceInterface;
use Modules\HR\Application\Contracts\UseCases\SalaryStructureLines\CreateSalaryStructureLineServiceInterface;
use Modules\HR\Application\Contracts\UseCases\SalaryStructureLines\DeleteSalaryStructureLineServiceInterface;
use Modules\HR\Application\Contracts\UseCases\SalaryStructureLines\GetSalaryStructureLineServiceInterface;
use Modules\HR\Application\Contracts\UseCases\SalaryStructureLines\ListSalaryStructureLinesServiceInterface;
use Modules\HR\Application\Contracts\UseCases\SalaryStructureLines\UpdateSalaryStructureLineServiceInterface;
use Modules\HR\Application\Contracts\UseCases\SalaryStructures\CreateSalaryStructureServiceInterface;
use Modules\HR\Application\Contracts\UseCases\SalaryStructures\DeleteSalaryStructureServiceInterface;
use Modules\HR\Application\Contracts\UseCases\SalaryStructures\GetSalaryStructureServiceInterface;
use Modules\HR\Application\Contracts\UseCases\SalaryStructures\ListSalaryStructuresServiceInterface;
use Modules\HR\Application\Contracts\UseCases\SalaryStructures\UpdateSalaryStructureServiceInterface;
use Modules\HR\Application\Contracts\UseCases\ShiftAssignments\CreateShiftAssignmentServiceInterface;
use Modules\HR\Application\Contracts\UseCases\ShiftAssignments\DeleteShiftAssignmentServiceInterface;
use Modules\HR\Application\Contracts\UseCases\ShiftAssignments\GetShiftAssignmentServiceInterface;
use Modules\HR\Application\Contracts\UseCases\ShiftAssignments\ListShiftAssignmentsServiceInterface;
use Modules\HR\Application\Contracts\UseCases\ShiftAssignments\UpdateShiftAssignmentServiceInterface;
use Modules\HR\Application\Contracts\UseCases\Shifts\CreateShiftServiceInterface;
use Modules\HR\Application\Contracts\UseCases\Shifts\DeleteShiftServiceInterface;
use Modules\HR\Application\Contracts\UseCases\Shifts\GetShiftServiceInterface;
use Modules\HR\Application\Contracts\UseCases\Shifts\ListShiftsServiceInterface;
use Modules\HR\Application\Contracts\UseCases\Shifts\UpdateShiftServiceInterface;
use Modules\HR\Application\Repositories\AttendanceLogRepositoryInterface;
use Modules\HR\Application\Repositories\AttendanceRecordRepositoryInterface;
use Modules\HR\Application\Repositories\BiometricDeviceRepositoryInterface;
use Modules\HR\Application\Repositories\DepartmentRepositoryInterface;
use Modules\HR\Application\Repositories\DesignationRepositoryInterface;
use Modules\HR\Application\Repositories\EmployeeContactRepositoryInterface;
use Modules\HR\Application\Repositories\EmployeeContractRepositoryInterface;
use Modules\HR\Application\Repositories\EmployeeDocumentRepositoryInterface;
use Modules\HR\Application\Repositories\EmployeeRepositoryInterface;
use Modules\HR\Application\Repositories\EmployeeSalaryAssignmentRepositoryInterface;
use Modules\HR\Application\Repositories\EmploymentTypeRepositoryInterface;
use Modules\HR\Application\Repositories\HolidayRepositoryInterface;
use Modules\HR\Application\Repositories\LeaveAllocationRepositoryInterface;
use Modules\HR\Application\Repositories\LeaveApplicationRepositoryInterface;
use Modules\HR\Application\Repositories\LeavePolicyLineRepositoryInterface;
use Modules\HR\Application\Repositories\LeavePolicyRepositoryInterface;
use Modules\HR\Application\Repositories\LeaveTypeRepositoryInterface;
use Modules\HR\Application\Repositories\PayrollRunRepositoryInterface;
use Modules\HR\Application\Repositories\PayslipLineRepositoryInterface;
use Modules\HR\Application\Repositories\PayslipRepositoryInterface;
use Modules\HR\Application\Repositories\PerformanceCycleRepositoryInterface;
use Modules\HR\Application\Repositories\PerformanceReviewRepositoryInterface;
use Modules\HR\Application\Repositories\SalaryComponentRepositoryInterface;
use Modules\HR\Application\Repositories\SalaryStructureLineRepositoryInterface;
use Modules\HR\Application\Repositories\SalaryStructureRepositoryInterface;
use Modules\HR\Application\Repositories\ShiftAssignmentRepositoryInterface;
use Modules\HR\Application\Repositories\ShiftRepositoryInterface;
use Modules\HR\Application\UseCases\AttendanceLogs\CreateAttendanceLogService;
use Modules\HR\Application\UseCases\AttendanceLogs\DeleteAttendanceLogService;
use Modules\HR\Application\UseCases\AttendanceLogs\GetAttendanceLogService;
use Modules\HR\Application\UseCases\AttendanceLogs\ListAttendanceLogsService;
use Modules\HR\Application\UseCases\AttendanceLogs\UpdateAttendanceLogService;
use Modules\HR\Application\UseCases\AttendanceRecords\CreateAttendanceRecordService;
use Modules\HR\Application\UseCases\AttendanceRecords\DeleteAttendanceRecordService;
use Modules\HR\Application\UseCases\AttendanceRecords\GetAttendanceRecordService;
use Modules\HR\Application\UseCases\AttendanceRecords\ListAttendanceRecordsService;
use Modules\HR\Application\UseCases\AttendanceRecords\UpdateAttendanceRecordService;
use Modules\HR\Application\UseCases\BiometricDevices\CreateBiometricDeviceService;
use Modules\HR\Application\UseCases\BiometricDevices\DeleteBiometricDeviceService;
use Modules\HR\Application\UseCases\BiometricDevices\GetBiometricDeviceService;
use Modules\HR\Application\UseCases\BiometricDevices\ListBiometricDevicesService;
use Modules\HR\Application\UseCases\BiometricDevices\UpdateBiometricDeviceService;
use Modules\HR\Application\UseCases\Departments\CreateDepartmentService;
use Modules\HR\Application\UseCases\Departments\DeleteDepartmentService;
use Modules\HR\Application\UseCases\Departments\GetDepartmentService;
use Modules\HR\Application\UseCases\Departments\ListDepartmentsService;
use Modules\HR\Application\UseCases\Departments\UpdateDepartmentService;
use Modules\HR\Application\UseCases\Designations\CreateDesignationService;
use Modules\HR\Application\UseCases\Designations\DeleteDesignationService;
use Modules\HR\Application\UseCases\Designations\GetDesignationService;
use Modules\HR\Application\UseCases\Designations\ListDesignationsService;
use Modules\HR\Application\UseCases\Designations\UpdateDesignationService;
use Modules\HR\Application\UseCases\EmployeeContacts\CreateEmployeeContactService;
use Modules\HR\Application\UseCases\EmployeeContacts\DeleteEmployeeContactService;
use Modules\HR\Application\UseCases\EmployeeContacts\GetEmployeeContactService;
use Modules\HR\Application\UseCases\EmployeeContacts\ListEmployeeContactsService;
use Modules\HR\Application\UseCases\EmployeeContacts\UpdateEmployeeContactService;
use Modules\HR\Application\UseCases\EmployeeContracts\CreateEmployeeContractService;
use Modules\HR\Application\UseCases\EmployeeContracts\DeleteEmployeeContractService;
use Modules\HR\Application\UseCases\EmployeeContracts\GetEmployeeContractService;
use Modules\HR\Application\UseCases\EmployeeContracts\ListEmployeeContractsService;
use Modules\HR\Application\UseCases\EmployeeContracts\UpdateEmployeeContractService;
use Modules\HR\Application\UseCases\EmployeeDocuments\CreateEmployeeDocumentService;
use Modules\HR\Application\UseCases\EmployeeDocuments\DeleteEmployeeDocumentService;
use Modules\HR\Application\UseCases\EmployeeDocuments\GetEmployeeDocumentService;
use Modules\HR\Application\UseCases\EmployeeDocuments\ListEmployeeDocumentsService;
use Modules\HR\Application\UseCases\EmployeeDocuments\UpdateEmployeeDocumentService;
use Modules\HR\Application\UseCases\Employees\CreateEmployeeService;
use Modules\HR\Application\UseCases\Employees\DeleteEmployeeService;
use Modules\HR\Application\UseCases\Employees\GetEmployeeService;
use Modules\HR\Application\UseCases\Employees\ListEmployeesService;
use Modules\HR\Application\UseCases\Employees\UpdateEmployeeService;
use Modules\HR\Application\UseCases\EmployeeSalaryAssignments\CreateEmployeeSalaryAssignmentService;
use Modules\HR\Application\UseCases\EmployeeSalaryAssignments\DeleteEmployeeSalaryAssignmentService;
use Modules\HR\Application\UseCases\EmployeeSalaryAssignments\GetEmployeeSalaryAssignmentService;
use Modules\HR\Application\UseCases\EmployeeSalaryAssignments\ListEmployeeSalaryAssignmentsService;
use Modules\HR\Application\UseCases\EmployeeSalaryAssignments\UpdateEmployeeSalaryAssignmentService;
use Modules\HR\Application\UseCases\EmploymentTypes\CreateEmploymentTypeService;
use Modules\HR\Application\UseCases\EmploymentTypes\DeleteEmploymentTypeService;
use Modules\HR\Application\UseCases\EmploymentTypes\GetEmploymentTypeService;
use Modules\HR\Application\UseCases\EmploymentTypes\ListEmploymentTypesService;
use Modules\HR\Application\UseCases\EmploymentTypes\UpdateEmploymentTypeService;
use Modules\HR\Application\UseCases\Holidays\CreateHolidayService;
use Modules\HR\Application\UseCases\Holidays\DeleteHolidayService;
use Modules\HR\Application\UseCases\Holidays\GetHolidayService;
use Modules\HR\Application\UseCases\Holidays\ListHolidaysService;
use Modules\HR\Application\UseCases\Holidays\UpdateHolidayService;
use Modules\HR\Application\UseCases\LeaveAllocations\CreateLeaveAllocationService;
use Modules\HR\Application\UseCases\LeaveAllocations\DeleteLeaveAllocationService;
use Modules\HR\Application\UseCases\LeaveAllocations\GetLeaveAllocationService;
use Modules\HR\Application\UseCases\LeaveAllocations\ListLeaveAllocationsService;
use Modules\HR\Application\UseCases\LeaveAllocations\UpdateLeaveAllocationService;
use Modules\HR\Application\UseCases\LeaveApplications\CreateLeaveApplicationService;
use Modules\HR\Application\UseCases\LeaveApplications\DeleteLeaveApplicationService;
use Modules\HR\Application\UseCases\LeaveApplications\GetLeaveApplicationService;
use Modules\HR\Application\UseCases\LeaveApplications\ListLeaveApplicationsService;
use Modules\HR\Application\UseCases\LeaveApplications\UpdateLeaveApplicationService;
use Modules\HR\Application\UseCases\LeavePolicies\CreateLeavePolicyService;
use Modules\HR\Application\UseCases\LeavePolicies\DeleteLeavePolicyService;
use Modules\HR\Application\UseCases\LeavePolicies\GetLeavePolicyService;
use Modules\HR\Application\UseCases\LeavePolicies\ListLeavePoliciesService;
use Modules\HR\Application\UseCases\LeavePolicies\UpdateLeavePolicyService;
use Modules\HR\Application\UseCases\LeavePolicyLines\CreateLeavePolicyLineService;
use Modules\HR\Application\UseCases\LeavePolicyLines\DeleteLeavePolicyLineService;
use Modules\HR\Application\UseCases\LeavePolicyLines\GetLeavePolicyLineService;
use Modules\HR\Application\UseCases\LeavePolicyLines\ListLeavePolicyLinesService;
use Modules\HR\Application\UseCases\LeavePolicyLines\UpdateLeavePolicyLineService;
use Modules\HR\Application\UseCases\LeaveTypes\CreateLeaveTypeService;
use Modules\HR\Application\UseCases\LeaveTypes\DeleteLeaveTypeService;
use Modules\HR\Application\UseCases\LeaveTypes\GetLeaveTypeService;
use Modules\HR\Application\UseCases\LeaveTypes\ListLeaveTypesService;
use Modules\HR\Application\UseCases\LeaveTypes\UpdateLeaveTypeService;
use Modules\HR\Application\UseCases\PayrollRuns\CreatePayrollRunService;
use Modules\HR\Application\UseCases\PayrollRuns\DeletePayrollRunService;
use Modules\HR\Application\UseCases\PayrollRuns\GetPayrollRunService;
use Modules\HR\Application\UseCases\PayrollRuns\ListPayrollRunsService;
use Modules\HR\Application\UseCases\PayrollRuns\UpdatePayrollRunService;
use Modules\HR\Application\UseCases\PayslipLines\CreatePayslipLineService;
use Modules\HR\Application\UseCases\PayslipLines\DeletePayslipLineService;
use Modules\HR\Application\UseCases\PayslipLines\GetPayslipLineService;
use Modules\HR\Application\UseCases\PayslipLines\ListPayslipLinesService;
use Modules\HR\Application\UseCases\PayslipLines\UpdatePayslipLineService;
use Modules\HR\Application\UseCases\Payslips\CreatePayslipService;
use Modules\HR\Application\UseCases\Payslips\DeletePayslipService;
use Modules\HR\Application\UseCases\Payslips\GetPayslipService;
use Modules\HR\Application\UseCases\Payslips\ListPayslipsService;
use Modules\HR\Application\UseCases\Payslips\UpdatePayslipService;
use Modules\HR\Application\UseCases\PerformanceCycles\CreatePerformanceCycleService;
use Modules\HR\Application\UseCases\PerformanceCycles\DeletePerformanceCycleService;
use Modules\HR\Application\UseCases\PerformanceCycles\GetPerformanceCycleService;
use Modules\HR\Application\UseCases\PerformanceCycles\ListPerformanceCyclesService;
use Modules\HR\Application\UseCases\PerformanceCycles\UpdatePerformanceCycleService;
use Modules\HR\Application\UseCases\PerformanceReviews\CreatePerformanceReviewService;
use Modules\HR\Application\UseCases\PerformanceReviews\DeletePerformanceReviewService;
use Modules\HR\Application\UseCases\PerformanceReviews\GetPerformanceReviewService;
use Modules\HR\Application\UseCases\PerformanceReviews\ListPerformanceReviewsService;
use Modules\HR\Application\UseCases\PerformanceReviews\UpdatePerformanceReviewService;
use Modules\HR\Application\UseCases\SalaryComponents\CreateSalaryComponentService;
use Modules\HR\Application\UseCases\SalaryComponents\DeleteSalaryComponentService;
use Modules\HR\Application\UseCases\SalaryComponents\GetSalaryComponentService;
use Modules\HR\Application\UseCases\SalaryComponents\ListSalaryComponentsService;
use Modules\HR\Application\UseCases\SalaryComponents\UpdateSalaryComponentService;
use Modules\HR\Application\UseCases\SalaryStructureLines\CreateSalaryStructureLineService;
use Modules\HR\Application\UseCases\SalaryStructureLines\DeleteSalaryStructureLineService;
use Modules\HR\Application\UseCases\SalaryStructureLines\GetSalaryStructureLineService;
use Modules\HR\Application\UseCases\SalaryStructureLines\ListSalaryStructureLinesService;
use Modules\HR\Application\UseCases\SalaryStructureLines\UpdateSalaryStructureLineService;
use Modules\HR\Application\UseCases\SalaryStructures\CreateSalaryStructureService;
use Modules\HR\Application\UseCases\SalaryStructures\DeleteSalaryStructureService;
use Modules\HR\Application\UseCases\SalaryStructures\GetSalaryStructureService;
use Modules\HR\Application\UseCases\SalaryStructures\ListSalaryStructuresService;
use Modules\HR\Application\UseCases\SalaryStructures\UpdateSalaryStructureService;
use Modules\HR\Application\UseCases\ShiftAssignments\CreateShiftAssignmentService;
use Modules\HR\Application\UseCases\ShiftAssignments\DeleteShiftAssignmentService;
use Modules\HR\Application\UseCases\ShiftAssignments\GetShiftAssignmentService;
use Modules\HR\Application\UseCases\ShiftAssignments\ListShiftAssignmentsService;
use Modules\HR\Application\UseCases\ShiftAssignments\UpdateShiftAssignmentService;
use Modules\HR\Application\UseCases\Shifts\CreateShiftService;
use Modules\HR\Application\UseCases\Shifts\DeleteShiftService;
use Modules\HR\Application\UseCases\Shifts\GetShiftService;
use Modules\HR\Application\UseCases\Shifts\ListShiftsService;
use Modules\HR\Application\UseCases\Shifts\UpdateShiftService;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\AttendanceLogModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\AttendanceRecordModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\BiometricDeviceModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\DepartmentModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\DesignationModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\EmployeeContactModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\EmployeeContractModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\EmployeeDocumentModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\EmployeeModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\EmployeeSalaryAssignmentModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\EmploymentTypeModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\HolidayModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\LeaveAllocationModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\LeaveApplicationModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\LeavePolicyLineModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\LeavePolicyModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\LeaveTypeModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\PayrollRunModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\PayslipLineModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\PayslipModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\PerformanceCycleModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\PerformanceReviewModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\SalaryComponentModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\SalaryStructureLineModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\SalaryStructureModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\ShiftAssignmentModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\ShiftModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Repositories\EloquentAttendanceLogRepository;
use Modules\HR\Infrastructure\Persistence\Eloquent\Repositories\EloquentAttendanceRecordRepository;
use Modules\HR\Infrastructure\Persistence\Eloquent\Repositories\EloquentBiometricDeviceRepository;
use Modules\HR\Infrastructure\Persistence\Eloquent\Repositories\EloquentDepartmentRepository;
use Modules\HR\Infrastructure\Persistence\Eloquent\Repositories\EloquentDesignationRepository;
use Modules\HR\Infrastructure\Persistence\Eloquent\Repositories\EloquentEmployeeContactRepository;
use Modules\HR\Infrastructure\Persistence\Eloquent\Repositories\EloquentEmployeeContractRepository;
use Modules\HR\Infrastructure\Persistence\Eloquent\Repositories\EloquentEmployeeDocumentRepository;
use Modules\HR\Infrastructure\Persistence\Eloquent\Repositories\EloquentEmployeeRepository;
use Modules\HR\Infrastructure\Persistence\Eloquent\Repositories\EloquentEmployeeSalaryAssignmentRepository;
use Modules\HR\Infrastructure\Persistence\Eloquent\Repositories\EloquentEmploymentTypeRepository;
use Modules\HR\Infrastructure\Persistence\Eloquent\Repositories\EloquentHolidayRepository;
use Modules\HR\Infrastructure\Persistence\Eloquent\Repositories\EloquentLeaveAllocationRepository;
use Modules\HR\Infrastructure\Persistence\Eloquent\Repositories\EloquentLeaveApplicationRepository;
use Modules\HR\Infrastructure\Persistence\Eloquent\Repositories\EloquentLeavePolicyLineRepository;
use Modules\HR\Infrastructure\Persistence\Eloquent\Repositories\EloquentLeavePolicyRepository;
use Modules\HR\Infrastructure\Persistence\Eloquent\Repositories\EloquentLeaveTypeRepository;
use Modules\HR\Infrastructure\Persistence\Eloquent\Repositories\EloquentPayrollRunRepository;
use Modules\HR\Infrastructure\Persistence\Eloquent\Repositories\EloquentPayslipLineRepository;
use Modules\HR\Infrastructure\Persistence\Eloquent\Repositories\EloquentPayslipRepository;
use Modules\HR\Infrastructure\Persistence\Eloquent\Repositories\EloquentPerformanceCycleRepository;
use Modules\HR\Infrastructure\Persistence\Eloquent\Repositories\EloquentPerformanceReviewRepository;
use Modules\HR\Infrastructure\Persistence\Eloquent\Repositories\EloquentSalaryComponentRepository;
use Modules\HR\Infrastructure\Persistence\Eloquent\Repositories\EloquentSalaryStructureLineRepository;
use Modules\HR\Infrastructure\Persistence\Eloquent\Repositories\EloquentSalaryStructureRepository;
use Modules\HR\Infrastructure\Persistence\Eloquent\Repositories\EloquentShiftAssignmentRepository;
use Modules\HR\Infrastructure\Persistence\Eloquent\Repositories\EloquentShiftRepository;

final class HrServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../Config/hr.php', 'hr');

        foreach (
            [
                ListDepartmentsServiceInterface::class => ListDepartmentsService::class,
                GetDepartmentServiceInterface::class => GetDepartmentService::class,
                CreateDepartmentServiceInterface::class => CreateDepartmentService::class,
                UpdateDepartmentServiceInterface::class => UpdateDepartmentService::class,
                DeleteDepartmentServiceInterface::class => DeleteDepartmentService::class,
                ListDesignationsServiceInterface::class => ListDesignationsService::class,
                GetDesignationServiceInterface::class => GetDesignationService::class,
                CreateDesignationServiceInterface::class => CreateDesignationService::class,
                UpdateDesignationServiceInterface::class => UpdateDesignationService::class,
                DeleteDesignationServiceInterface::class => DeleteDesignationService::class,
                ListEmploymentTypesServiceInterface::class => ListEmploymentTypesService::class,
                GetEmploymentTypeServiceInterface::class => GetEmploymentTypeService::class,
                CreateEmploymentTypeServiceInterface::class => CreateEmploymentTypeService::class,
                UpdateEmploymentTypeServiceInterface::class => UpdateEmploymentTypeService::class,
                DeleteEmploymentTypeServiceInterface::class => DeleteEmploymentTypeService::class,
                ListEmployeesServiceInterface::class => ListEmployeesService::class,
                GetEmployeeServiceInterface::class => GetEmployeeService::class,
                CreateEmployeeServiceInterface::class => CreateEmployeeService::class,
                UpdateEmployeeServiceInterface::class => UpdateEmployeeService::class,
                DeleteEmployeeServiceInterface::class => DeleteEmployeeService::class,
                ListEmployeeContactsServiceInterface::class => ListEmployeeContactsService::class,
                GetEmployeeContactServiceInterface::class => GetEmployeeContactService::class,
                CreateEmployeeContactServiceInterface::class => CreateEmployeeContactService::class,
                UpdateEmployeeContactServiceInterface::class => UpdateEmployeeContactService::class,
                DeleteEmployeeContactServiceInterface::class => DeleteEmployeeContactService::class,
                ListEmployeeDocumentsServiceInterface::class => ListEmployeeDocumentsService::class,
                GetEmployeeDocumentServiceInterface::class => GetEmployeeDocumentService::class,
                CreateEmployeeDocumentServiceInterface::class => CreateEmployeeDocumentService::class,
                UpdateEmployeeDocumentServiceInterface::class => UpdateEmployeeDocumentService::class,
                DeleteEmployeeDocumentServiceInterface::class => DeleteEmployeeDocumentService::class,
                ListEmployeeContractsServiceInterface::class => ListEmployeeContractsService::class,
                GetEmployeeContractServiceInterface::class => GetEmployeeContractService::class,
                CreateEmployeeContractServiceInterface::class => CreateEmployeeContractService::class,
                UpdateEmployeeContractServiceInterface::class => UpdateEmployeeContractService::class,
                DeleteEmployeeContractServiceInterface::class => DeleteEmployeeContractService::class,
                ListBiometricDevicesServiceInterface::class => ListBiometricDevicesService::class,
                GetBiometricDeviceServiceInterface::class => GetBiometricDeviceService::class,
                CreateBiometricDeviceServiceInterface::class => CreateBiometricDeviceService::class,
                UpdateBiometricDeviceServiceInterface::class => UpdateBiometricDeviceService::class,
                DeleteBiometricDeviceServiceInterface::class => DeleteBiometricDeviceService::class,
                ListHolidaysServiceInterface::class => ListHolidaysService::class,
                GetHolidayServiceInterface::class => GetHolidayService::class,
                CreateHolidayServiceInterface::class => CreateHolidayService::class,
                UpdateHolidayServiceInterface::class => UpdateHolidayService::class,
                DeleteHolidayServiceInterface::class => DeleteHolidayService::class,
                ListAttendanceLogsServiceInterface::class => ListAttendanceLogsService::class,
                GetAttendanceLogServiceInterface::class => GetAttendanceLogService::class,
                CreateAttendanceLogServiceInterface::class => CreateAttendanceLogService::class,
                UpdateAttendanceLogServiceInterface::class => UpdateAttendanceLogService::class,
                DeleteAttendanceLogServiceInterface::class => DeleteAttendanceLogService::class,
                ListShiftsServiceInterface::class => ListShiftsService::class,
                GetShiftServiceInterface::class => GetShiftService::class,
                CreateShiftServiceInterface::class => CreateShiftService::class,
                UpdateShiftServiceInterface::class => UpdateShiftService::class,
                DeleteShiftServiceInterface::class => DeleteShiftService::class,
                ListShiftAssignmentsServiceInterface::class => ListShiftAssignmentsService::class,
                GetShiftAssignmentServiceInterface::class => GetShiftAssignmentService::class,
                CreateShiftAssignmentServiceInterface::class => CreateShiftAssignmentService::class,
                UpdateShiftAssignmentServiceInterface::class => UpdateShiftAssignmentService::class,
                DeleteShiftAssignmentServiceInterface::class => DeleteShiftAssignmentService::class,
                ListAttendanceRecordsServiceInterface::class => ListAttendanceRecordsService::class,
                GetAttendanceRecordServiceInterface::class => GetAttendanceRecordService::class,
                CreateAttendanceRecordServiceInterface::class => CreateAttendanceRecordService::class,
                UpdateAttendanceRecordServiceInterface::class => UpdateAttendanceRecordService::class,
                DeleteAttendanceRecordServiceInterface::class => DeleteAttendanceRecordService::class,
                ListLeaveTypesServiceInterface::class => ListLeaveTypesService::class,
                GetLeaveTypeServiceInterface::class => GetLeaveTypeService::class,
                CreateLeaveTypeServiceInterface::class => CreateLeaveTypeService::class,
                UpdateLeaveTypeServiceInterface::class => UpdateLeaveTypeService::class,
                DeleteLeaveTypeServiceInterface::class => DeleteLeaveTypeService::class,
                ListLeavePoliciesServiceInterface::class => ListLeavePoliciesService::class,
                GetLeavePolicyServiceInterface::class => GetLeavePolicyService::class,
                CreateLeavePolicyServiceInterface::class => CreateLeavePolicyService::class,
                UpdateLeavePolicyServiceInterface::class => UpdateLeavePolicyService::class,
                DeleteLeavePolicyServiceInterface::class => DeleteLeavePolicyService::class,
                ListLeavePolicyLinesServiceInterface::class => ListLeavePolicyLinesService::class,
                GetLeavePolicyLineServiceInterface::class => GetLeavePolicyLineService::class,
                CreateLeavePolicyLineServiceInterface::class => CreateLeavePolicyLineService::class,
                UpdateLeavePolicyLineServiceInterface::class => UpdateLeavePolicyLineService::class,
                DeleteLeavePolicyLineServiceInterface::class => DeleteLeavePolicyLineService::class,
                ListLeaveAllocationsServiceInterface::class => ListLeaveAllocationsService::class,
                GetLeaveAllocationServiceInterface::class => GetLeaveAllocationService::class,
                CreateLeaveAllocationServiceInterface::class => CreateLeaveAllocationService::class,
                UpdateLeaveAllocationServiceInterface::class => UpdateLeaveAllocationService::class,
                DeleteLeaveAllocationServiceInterface::class => DeleteLeaveAllocationService::class,
                ListLeaveApplicationsServiceInterface::class => ListLeaveApplicationsService::class,
                GetLeaveApplicationServiceInterface::class => GetLeaveApplicationService::class,
                CreateLeaveApplicationServiceInterface::class => CreateLeaveApplicationService::class,
                UpdateLeaveApplicationServiceInterface::class => UpdateLeaveApplicationService::class,
                DeleteLeaveApplicationServiceInterface::class => DeleteLeaveApplicationService::class,
                ListSalaryComponentsServiceInterface::class => ListSalaryComponentsService::class,
                GetSalaryComponentServiceInterface::class => GetSalaryComponentService::class,
                CreateSalaryComponentServiceInterface::class => CreateSalaryComponentService::class,
                UpdateSalaryComponentServiceInterface::class => UpdateSalaryComponentService::class,
                DeleteSalaryComponentServiceInterface::class => DeleteSalaryComponentService::class,
                ListSalaryStructuresServiceInterface::class => ListSalaryStructuresService::class,
                GetSalaryStructureServiceInterface::class => GetSalaryStructureService::class,
                CreateSalaryStructureServiceInterface::class => CreateSalaryStructureService::class,
                UpdateSalaryStructureServiceInterface::class => UpdateSalaryStructureService::class,
                DeleteSalaryStructureServiceInterface::class => DeleteSalaryStructureService::class,
                ListSalaryStructureLinesServiceInterface::class => ListSalaryStructureLinesService::class,
                GetSalaryStructureLineServiceInterface::class => GetSalaryStructureLineService::class,
                CreateSalaryStructureLineServiceInterface::class => CreateSalaryStructureLineService::class,
                UpdateSalaryStructureLineServiceInterface::class => UpdateSalaryStructureLineService::class,
                DeleteSalaryStructureLineServiceInterface::class => DeleteSalaryStructureLineService::class,
                ListEmployeeSalaryAssignmentsServiceInterface::class => ListEmployeeSalaryAssignmentsService::class,
                GetEmployeeSalaryAssignmentServiceInterface::class => GetEmployeeSalaryAssignmentService::class,
                CreateEmployeeSalaryAssignmentServiceInterface::class => CreateEmployeeSalaryAssignmentService::class,
                UpdateEmployeeSalaryAssignmentServiceInterface::class => UpdateEmployeeSalaryAssignmentService::class,
                DeleteEmployeeSalaryAssignmentServiceInterface::class => DeleteEmployeeSalaryAssignmentService::class,
                ListPayrollRunsServiceInterface::class => ListPayrollRunsService::class,
                GetPayrollRunServiceInterface::class => GetPayrollRunService::class,
                CreatePayrollRunServiceInterface::class => CreatePayrollRunService::class,
                UpdatePayrollRunServiceInterface::class => UpdatePayrollRunService::class,
                DeletePayrollRunServiceInterface::class => DeletePayrollRunService::class,
                ListPayslipsServiceInterface::class => ListPayslipsService::class,
                GetPayslipServiceInterface::class => GetPayslipService::class,
                CreatePayslipServiceInterface::class => CreatePayslipService::class,
                UpdatePayslipServiceInterface::class => UpdatePayslipService::class,
                DeletePayslipServiceInterface::class => DeletePayslipService::class,
                ListPayslipLinesServiceInterface::class => ListPayslipLinesService::class,
                GetPayslipLineServiceInterface::class => GetPayslipLineService::class,
                CreatePayslipLineServiceInterface::class => CreatePayslipLineService::class,
                UpdatePayslipLineServiceInterface::class => UpdatePayslipLineService::class,
                DeletePayslipLineServiceInterface::class => DeletePayslipLineService::class,
                ListPerformanceCyclesServiceInterface::class => ListPerformanceCyclesService::class,
                GetPerformanceCycleServiceInterface::class => GetPerformanceCycleService::class,
                CreatePerformanceCycleServiceInterface::class => CreatePerformanceCycleService::class,
                UpdatePerformanceCycleServiceInterface::class => UpdatePerformanceCycleService::class,
                DeletePerformanceCycleServiceInterface::class => DeletePerformanceCycleService::class,
                ListPerformanceReviewsServiceInterface::class => ListPerformanceReviewsService::class,
                GetPerformanceReviewServiceInterface::class => GetPerformanceReviewService::class,
                CreatePerformanceReviewServiceInterface::class => CreatePerformanceReviewService::class,
                UpdatePerformanceReviewServiceInterface::class => UpdatePerformanceReviewService::class,
                DeletePerformanceReviewServiceInterface::class => DeletePerformanceReviewService::class,
            ] as $contract => $implementation
        ) {
            $this->app->singleton($contract, $implementation);
        }

        $this->app->singleton(DepartmentRepositoryInterface::class, function (): DepartmentRepositoryInterface {
            return new EloquentDepartmentRepository(new DepartmentModel());
        });
        $this->app->singleton(DesignationRepositoryInterface::class, function (): DesignationRepositoryInterface {
            return new EloquentDesignationRepository(new DesignationModel());
        });
        $this->app->singleton(EmploymentTypeRepositoryInterface::class, function (): EmploymentTypeRepositoryInterface {
            return new EloquentEmploymentTypeRepository(new EmploymentTypeModel());
        });
        $this->app->singleton(EmployeeRepositoryInterface::class, function (): EmployeeRepositoryInterface {
            return new EloquentEmployeeRepository(new EmployeeModel());
        });
        $this->app->singleton(EmployeeContactRepositoryInterface::class, function (): EmployeeContactRepositoryInterface {
            return new EloquentEmployeeContactRepository(new EmployeeContactModel());
        });
        $this->app->singleton(EmployeeDocumentRepositoryInterface::class, function (): EmployeeDocumentRepositoryInterface {
            return new EloquentEmployeeDocumentRepository(new EmployeeDocumentModel());
        });
        $this->app->singleton(EmployeeContractRepositoryInterface::class, function (): EmployeeContractRepositoryInterface {
            return new EloquentEmployeeContractRepository(new EmployeeContractModel());
        });
        $this->app->singleton(BiometricDeviceRepositoryInterface::class, function (): BiometricDeviceRepositoryInterface {
            return new EloquentBiometricDeviceRepository(new BiometricDeviceModel());
        });
        $this->app->singleton(HolidayRepositoryInterface::class, function (): HolidayRepositoryInterface {
            return new EloquentHolidayRepository(new HolidayModel());
        });
        $this->app->singleton(AttendanceLogRepositoryInterface::class, function (): AttendanceLogRepositoryInterface {
            return new EloquentAttendanceLogRepository(new AttendanceLogModel());
        });
        $this->app->singleton(ShiftRepositoryInterface::class, function (): ShiftRepositoryInterface {
            return new EloquentShiftRepository(new ShiftModel());
        });
        $this->app->singleton(ShiftAssignmentRepositoryInterface::class, function (): ShiftAssignmentRepositoryInterface {
            return new EloquentShiftAssignmentRepository(new ShiftAssignmentModel());
        });
        $this->app->singleton(AttendanceRecordRepositoryInterface::class, function (): AttendanceRecordRepositoryInterface {
            return new EloquentAttendanceRecordRepository(new AttendanceRecordModel());
        });
        $this->app->singleton(LeaveTypeRepositoryInterface::class, function (): LeaveTypeRepositoryInterface {
            return new EloquentLeaveTypeRepository(new LeaveTypeModel());
        });
        $this->app->singleton(LeavePolicyRepositoryInterface::class, function (): LeavePolicyRepositoryInterface {
            return new EloquentLeavePolicyRepository(new LeavePolicyModel());
        });
        $this->app->singleton(LeavePolicyLineRepositoryInterface::class, function (): LeavePolicyLineRepositoryInterface {
            return new EloquentLeavePolicyLineRepository(new LeavePolicyLineModel());
        });
        $this->app->singleton(LeaveAllocationRepositoryInterface::class, function (): LeaveAllocationRepositoryInterface {
            return new EloquentLeaveAllocationRepository(new LeaveAllocationModel());
        });
        $this->app->singleton(LeaveApplicationRepositoryInterface::class, function (): LeaveApplicationRepositoryInterface {
            return new EloquentLeaveApplicationRepository(new LeaveApplicationModel());
        });
        $this->app->singleton(SalaryComponentRepositoryInterface::class, function (): SalaryComponentRepositoryInterface {
            return new EloquentSalaryComponentRepository(new SalaryComponentModel());
        });
        $this->app->singleton(SalaryStructureRepositoryInterface::class, function (): SalaryStructureRepositoryInterface {
            return new EloquentSalaryStructureRepository(new SalaryStructureModel());
        });
        $this->app->singleton(SalaryStructureLineRepositoryInterface::class, function (): SalaryStructureLineRepositoryInterface {
            return new EloquentSalaryStructureLineRepository(new SalaryStructureLineModel());
        });
        $this->app->singleton(EmployeeSalaryAssignmentRepositoryInterface::class, function (): EmployeeSalaryAssignmentRepositoryInterface {
            return new EloquentEmployeeSalaryAssignmentRepository(new EmployeeSalaryAssignmentModel());
        });
        $this->app->singleton(PayrollRunRepositoryInterface::class, function (): PayrollRunRepositoryInterface {
            return new EloquentPayrollRunRepository(new PayrollRunModel());
        });
        $this->app->singleton(PayslipRepositoryInterface::class, function (): PayslipRepositoryInterface {
            return new EloquentPayslipRepository(new PayslipModel());
        });
        $this->app->singleton(PayslipLineRepositoryInterface::class, function (): PayslipLineRepositoryInterface {
            return new EloquentPayslipLineRepository(new PayslipLineModel());
        });
        $this->app->singleton(PerformanceCycleRepositoryInterface::class, function (): PerformanceCycleRepositoryInterface {
            return new EloquentPerformanceCycleRepository(new PerformanceCycleModel());
        });
        $this->app->singleton(PerformanceReviewRepositoryInterface::class, function (): PerformanceReviewRepositoryInterface {
            return new EloquentPerformanceReviewRepository(new PerformanceReviewModel());
        });
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
        $this->loadMigrationsFrom(__DIR__ . '/../../Infrastructure/Persistence/Eloquent/Migrations');
    }
}