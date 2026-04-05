<?php
declare(strict_types=1);
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Modules\HR\Domain\Entities\Department;
use Modules\HR\Domain\Entities\Position;
use Modules\HR\Domain\Entities\Employee;
use Modules\HR\Domain\Entities\LeaveType;
use Modules\HR\Domain\Entities\LeaveRequest;
use Modules\HR\Domain\Entities\AttendanceRecord;
use Modules\HR\Domain\Entities\PayrollRecord;
use Modules\HR\Domain\Exceptions\DepartmentNotFoundException;
use Modules\HR\Domain\Exceptions\PositionNotFoundException;
use Modules\HR\Domain\Exceptions\EmployeeNotFoundException;
use Modules\HR\Domain\Exceptions\LeaveTypeNotFoundException;
use Modules\HR\Domain\Exceptions\LeaveRequestNotFoundException;
use Modules\HR\Domain\Exceptions\AttendanceRecordNotFoundException;
use Modules\HR\Domain\Exceptions\PayrollRecordNotFoundException;
use Modules\HR\Domain\Exceptions\InvalidLeaveRequestException;
use Modules\HR\Domain\Exceptions\InvalidPayrollException;
use Modules\HR\Domain\RepositoryInterfaces\DepartmentRepositoryInterface;
use Modules\HR\Domain\RepositoryInterfaces\PositionRepositoryInterface;
use Modules\HR\Domain\RepositoryInterfaces\EmployeeRepositoryInterface;
use Modules\HR\Domain\RepositoryInterfaces\LeaveTypeRepositoryInterface;
use Modules\HR\Domain\RepositoryInterfaces\LeaveRequestRepositoryInterface;
use Modules\HR\Domain\RepositoryInterfaces\AttendanceRepositoryInterface;
use Modules\HR\Domain\RepositoryInterfaces\PayrollRepositoryInterface;
use Modules\HR\Application\Services\DepartmentService;
use Modules\HR\Application\Services\PositionService;
use Modules\HR\Application\Services\EmployeeService;
use Modules\HR\Application\Services\LeaveTypeService;
use Modules\HR\Application\Services\LeaveRequestService;
use Modules\HR\Application\Services\PayrollService;
use Modules\HR\Infrastructure\Biometric\BiometricDeviceManager;
use Modules\HR\Infrastructure\Biometric\Drivers\MockBiometricDriver;

class HRModuleTest extends TestCase
{
    // ──────────────────────────────────────────────────────────────────────
    // Helper factories
    // ──────────────────────────────────────────────────────────────────────

    private function makeDepartment(int $id = 1): Department
    {
        return new Department($id, 1, 'Engineering', 'ENG', 'Engineering dept', null, null, true, new \DateTime(), new \DateTime());
    }

    private function makePosition(int $id = 1): Position
    {
        return new Position($id, 1, 1, 'Senior Engineer', 'SE', null, 'full_time', 60000.0, 100000.0, true, new \DateTime(), new \DateTime());
    }

    private function makeEmployee(int $id = 1, string $status = Employee::STATUS_ACTIVE): Employee
    {
        return new Employee(
            $id, 1, null, 1, 1,
            'EMP001', 'John', 'Doe', 'john@example.com',
            '1234567890', 'male', new \DateTime('1990-01-01'), new \DateTime('2020-01-01'),
            null, $status, 80000.0, null, null, null, null, null, null,
            new \DateTime(), new \DateTime()
        );
    }

    private function makeLeaveType(int $id = 1): LeaveType
    {
        return new LeaveType($id, 1, 'Annual Leave', 'AL', 'Annual paid leave', 21, true, true, true, new \DateTime(), new \DateTime());
    }

    private function makeLeaveRequest(int $id = 1, string $status = LeaveRequest::STATUS_PENDING): LeaveRequest
    {
        return new LeaveRequest(
            $id, 1, 1, 1,
            new \DateTime('2026-05-01'), new \DateTime('2026-05-05'),
            5.0, $status, 'Holiday', null, null, null,
            new \DateTime(), new \DateTime()
        );
    }

    private function makeAttendanceRecord(int $id = 1): AttendanceRecord
    {
        return new AttendanceRecord(
            $id, 1, 1, new \DateTime('2026-04-04'),
            new \DateTime('2026-04-04 09:00:00'), null,
            null, AttendanceRecord::SOURCE_MANUAL, null, null, null,
            false, new \DateTime(), new \DateTime()
        );
    }

    private function makePayrollRecord(int $id = 1, string $status = PayrollRecord::STATUS_DRAFT): PayrollRecord
    {
        return new PayrollRecord(
            $id, 1, 1, 2026, 4,
            80000.0, 5000.0, 2000.0, 8500.0, 74500.0,
            $status, null, null, null, null, null,
            new \DateTime(), new \DateTime()
        );
    }

    // ──────────────────────────────────────────────────────────────────────
    // Department Entity Tests
    // ──────────────────────────────────────────────────────────────────────

    public function test_department_entity_getters(): void
    {
        $dept = $this->makeDepartment();
        $this->assertEquals(1, $dept->getId());
        $this->assertEquals(1, $dept->getTenantId());
        $this->assertEquals('Engineering', $dept->getName());
        $this->assertEquals('ENG', $dept->getCode());
        $this->assertTrue($dept->isActive());
        $this->assertNull($dept->getManagerId());
        $this->assertNull($dept->getParentId());
    }

    public function test_department_update(): void
    {
        $dept = $this->makeDepartment();
        $dept->update('IT', 'IT', 'IT Department', 5, null);
        $this->assertEquals('IT', $dept->getName());
        $this->assertEquals('IT', $dept->getCode());
        $this->assertEquals(5, $dept->getManagerId());
    }

    public function test_department_deactivate_activate(): void
    {
        $dept = $this->makeDepartment();
        $dept->deactivate();
        $this->assertFalse($dept->isActive());
        $dept->activate();
        $this->assertTrue($dept->isActive());
    }

    // ──────────────────────────────────────────────────────────────────────
    // Position Entity Tests
    // ──────────────────────────────────────────────────────────────────────

    public function test_position_entity_getters(): void
    {
        $pos = $this->makePosition();
        $this->assertEquals(1, $pos->getId());
        $this->assertEquals('Senior Engineer', $pos->getTitle());
        $this->assertEquals('SE', $pos->getCode());
        $this->assertEquals('full_time', $pos->getEmploymentType());
        $this->assertEquals(60000.0, $pos->getMinSalary());
        $this->assertTrue($pos->isActive());
    }

    public function test_position_update(): void
    {
        $pos = $this->makePosition();
        $pos->update('Lead Engineer', 'LE', null, 'full_time', 80000.0, 120000.0);
        $this->assertEquals('Lead Engineer', $pos->getTitle());
        $this->assertEquals(80000.0, $pos->getMinSalary());
    }

    // ──────────────────────────────────────────────────────────────────────
    // Employee Entity Tests
    // ──────────────────────────────────────────────────────────────────────

    public function test_employee_entity_getters(): void
    {
        $emp = $this->makeEmployee();
        $this->assertEquals(1, $emp->getId());
        $this->assertEquals('John', $emp->getFirstName());
        $this->assertEquals('Doe', $emp->getLastName());
        $this->assertEquals('John Doe', $emp->getFullName());
        $this->assertEquals('john@example.com', $emp->getEmail());
        $this->assertEquals('EMP001', $emp->getEmployeeCode());
        $this->assertTrue($emp->isActive());
    }

    public function test_employee_terminate(): void
    {
        $emp = $this->makeEmployee();
        $emp->terminate(new \DateTime('2026-12-31'));
        $this->assertTrue($emp->isTerminated());
        $this->assertNotNull($emp->getTerminationDate());
    }

    public function test_employee_set_on_leave_and_return(): void
    {
        $emp = $this->makeEmployee();
        $emp->setOnLeave();
        $this->assertTrue($emp->isOnLeave());
        $emp->returnFromLeave();
        $this->assertTrue($emp->isActive());
    }

    public function test_employee_update_profile(): void
    {
        $emp = $this->makeEmployee();
        $emp->updateProfile('Jane', 'Smith', '9999999999', 'female', null, '123 Main St', 'Mom', '5551234567');
        $this->assertEquals('Jane', $emp->getFirstName());
        $this->assertEquals('Jane Smith', $emp->getFullName());
    }

    public function test_employee_transfer(): void
    {
        $emp = $this->makeEmployee();
        $emp->transfer(2, 3);
        $this->assertEquals(2, $emp->getDepartmentId());
        $this->assertEquals(3, $emp->getPositionId());
    }

    public function test_employee_link_user(): void
    {
        $emp = $this->makeEmployee();
        $this->assertNull($emp->getUserId());
        $emp->linkUser(42);
        $this->assertEquals(42, $emp->getUserId());
    }

    // ──────────────────────────────────────────────────────────────────────
    // LeaveType Entity Tests
    // ──────────────────────────────────────────────────────────────────────

    public function test_leave_type_entity_getters(): void
    {
        $type = $this->makeLeaveType();
        $this->assertEquals('Annual Leave', $type->getName());
        $this->assertEquals('AL', $type->getCode());
        $this->assertEquals(21, $type->getDefaultDays());
        $this->assertTrue($type->isPaid());
        $this->assertTrue($type->requiresApproval());
        $this->assertTrue($type->isActive());
    }

    public function test_leave_type_update(): void
    {
        $type = $this->makeLeaveType();
        $type->update('Sick Leave', 'SL', 'Medical leave', 10, true, false);
        $this->assertEquals('Sick Leave', $type->getName());
        $this->assertEquals(10, $type->getDefaultDays());
        $this->assertFalse($type->requiresApproval());
    }

    // ──────────────────────────────────────────────────────────────────────
    // LeaveRequest Entity Tests
    // ──────────────────────────────────────────────────────────────────────

    public function test_leave_request_pending_by_default(): void
    {
        $req = $this->makeLeaveRequest();
        $this->assertTrue($req->isPending());
        $this->assertEquals(5.0, $req->getTotalDays());
    }

    public function test_leave_request_approve(): void
    {
        $req = $this->makeLeaveRequest();
        $req->approve(99);
        $this->assertTrue($req->isApproved());
        $this->assertEquals(99, $req->getApprovedById());
    }

    public function test_leave_request_reject(): void
    {
        $req = $this->makeLeaveRequest();
        $req->reject(99, 'Busy period');
        $this->assertTrue($req->isRejected());
        $this->assertEquals('Busy period', $req->getRejectionReason());
    }

    public function test_leave_request_cancel(): void
    {
        $req = $this->makeLeaveRequest();
        $req->cancel();
        $this->assertTrue($req->isCancelled());
    }

    public function test_leave_request_cannot_approve_already_approved(): void
    {
        $req = $this->makeLeaveRequest(1, LeaveRequest::STATUS_APPROVED);
        $this->expectException(InvalidLeaveRequestException::class);
        $req->approve(1);
    }

    public function test_leave_request_cannot_reject_already_rejected(): void
    {
        $req = $this->makeLeaveRequest(1, LeaveRequest::STATUS_REJECTED);
        $this->expectException(InvalidLeaveRequestException::class);
        $req->reject(1, 'reason');
    }

    public function test_leave_request_cannot_cancel_approved(): void
    {
        $req = $this->makeLeaveRequest(1, LeaveRequest::STATUS_APPROVED);
        $this->expectException(InvalidLeaveRequestException::class);
        $req->cancel();
    }

    // ──────────────────────────────────────────────────────────────────────
    // AttendanceRecord Entity Tests
    // ──────────────────────────────────────────────────────────────────────

    public function test_attendance_record_getters(): void
    {
        $rec = $this->makeAttendanceRecord();
        $this->assertEquals(1, $rec->getEmployeeId());
        $this->assertEquals(AttendanceRecord::SOURCE_MANUAL, $rec->getSource());
        $this->assertFalse($rec->isApproved());
        $this->assertNotNull($rec->getCheckIn());
        $this->assertNull($rec->getCheckOut());
    }

    public function test_attendance_checkout_calculates_hours(): void
    {
        $rec = $this->makeAttendanceRecord();
        $checkOut = new \DateTime('2026-04-04 17:00:00');
        $rec->checkOut($checkOut);
        $this->assertNotNull($rec->getCheckOut());
        $this->assertEquals(8.0, $rec->getWorkedHours());
    }

    public function test_attendance_approve_unapprove(): void
    {
        $rec = $this->makeAttendanceRecord();
        $rec->approve();
        $this->assertTrue($rec->isApproved());
        $rec->unapprove();
        $this->assertFalse($rec->isApproved());
    }

    // ──────────────────────────────────────────────────────────────────────
    // PayrollRecord Entity Tests
    // ──────────────────────────────────────────────────────────────────────

    public function test_payroll_record_getters(): void
    {
        $rec = $this->makePayrollRecord();
        $this->assertEquals(2026, $rec->getPeriodYear());
        $this->assertEquals(4, $rec->getPeriodMonth());
        $this->assertEquals(80000.0, $rec->getBasicSalary());
        $this->assertEquals(85000.0, $rec->getGrossSalary());
        $this->assertEquals(74500.0, $rec->getNetSalary());
        $this->assertTrue($rec->isDraft());
    }

    public function test_payroll_process(): void
    {
        $rec = $this->makePayrollRecord();
        $rec->process(1);
        $this->assertTrue($rec->isProcessed());
        $this->assertEquals(1, $rec->getProcessedById());
    }

    public function test_payroll_approve(): void
    {
        $rec = $this->makePayrollRecord(1, PayrollRecord::STATUS_PROCESSED);
        $rec->approve();
        $this->assertTrue($rec->isApproved());
    }

    public function test_payroll_mark_as_paid(): void
    {
        $rec = $this->makePayrollRecord(1, PayrollRecord::STATUS_APPROVED);
        $rec->markAsPaid(new \DateTime('2026-04-30'), 'TXN123');
        $this->assertTrue($rec->isPaid());
        $this->assertEquals('TXN123', $rec->getPaymentReference());
    }

    public function test_payroll_cannot_process_if_not_draft(): void
    {
        $rec = $this->makePayrollRecord(1, PayrollRecord::STATUS_PROCESSED);
        $this->expectException(InvalidPayrollException::class);
        $rec->process(1);
    }

    public function test_payroll_cannot_approve_if_not_processed(): void
    {
        $rec = $this->makePayrollRecord(1, PayrollRecord::STATUS_DRAFT);
        $this->expectException(InvalidPayrollException::class);
        $rec->approve();
    }

    public function test_payroll_cannot_mark_paid_if_not_approved(): void
    {
        $rec = $this->makePayrollRecord(1, PayrollRecord::STATUS_PROCESSED);
        $this->expectException(InvalidPayrollException::class);
        $rec->markAsPaid(new \DateTime(), 'REF');
    }

    public function test_payroll_cannot_cancel_if_paid(): void
    {
        $rec = $this->makePayrollRecord(1, PayrollRecord::STATUS_PAID);
        $this->expectException(InvalidPayrollException::class);
        $rec->cancel();
    }

    // ──────────────────────────────────────────────────────────────────────
    // Exception message tests
    // ──────────────────────────────────────────────────────────────────────

    public function test_department_not_found_exception(): void
    {
        $e = new DepartmentNotFoundException(7);
        $this->assertStringContainsString('7', $e->getMessage());
        $this->assertStringContainsString('Department', $e->getMessage());
    }

    public function test_employee_not_found_exception(): void
    {
        $e = new EmployeeNotFoundException(42);
        $this->assertStringContainsString('42', $e->getMessage());
        $this->assertStringContainsString('Employee', $e->getMessage());
    }

    public function test_leave_request_not_found_exception(): void
    {
        $e = new LeaveRequestNotFoundException(10);
        $this->assertStringContainsString('10', $e->getMessage());
    }

    public function test_payroll_not_found_exception(): void
    {
        $e = new PayrollRecordNotFoundException(99);
        $this->assertStringContainsString('99', $e->getMessage());
    }

    // ──────────────────────────────────────────────────────────────────────
    // Service Layer Tests (mocked repositories)
    // ──────────────────────────────────────────────────────────────────────

    public function test_department_service_finds_by_id(): void
    {
        /** @var DepartmentRepositoryInterface&MockObject $repo */
        $repo = $this->createMock(DepartmentRepositoryInterface::class);
        $repo->expects($this->once())->method('findById')->with(1)->willReturn($this->makeDepartment());

        $service = new DepartmentService($repo);
        $result  = $service->findById(1);
        $this->assertEquals('Engineering', $result->getName());
    }

    public function test_department_service_throws_not_found(): void
    {
        $repo = $this->createMock(DepartmentRepositoryInterface::class);
        $repo->method('findById')->willReturn(null);

        $service = new DepartmentService($repo);
        $this->expectException(DepartmentNotFoundException::class);
        $service->findById(999);
    }

    public function test_position_service_throws_not_found(): void
    {
        $repo = $this->createMock(PositionRepositoryInterface::class);
        $repo->method('findById')->willReturn(null);

        $service = new PositionService($repo);
        $this->expectException(PositionNotFoundException::class);
        $service->findById(999);
    }

    public function test_employee_service_finds_by_id(): void
    {
        $employee = $this->makeEmployee();
        $repo     = $this->createMock(EmployeeRepositoryInterface::class);
        $repo->expects($this->once())->method('findById')->with(1)->willReturn($employee);

        $service = new EmployeeService($repo);
        $result  = $service->findById(1);
        $this->assertEquals('John', $result->getFirstName());
    }

    public function test_employee_service_throws_not_found(): void
    {
        $repo = $this->createMock(EmployeeRepositoryInterface::class);
        $repo->method('findById')->willReturn(null);

        $service = new EmployeeService($repo);
        $this->expectException(EmployeeNotFoundException::class);
        $service->findById(500);
    }

    public function test_leave_type_service_throws_not_found(): void
    {
        $repo = $this->createMock(LeaveTypeRepositoryInterface::class);
        $repo->method('findById')->willReturn(null);

        $service = new LeaveTypeService($repo);
        $this->expectException(LeaveTypeNotFoundException::class);
        $service->findById(999);
    }

    public function test_leave_request_service_throws_not_found(): void
    {
        $repo = $this->createMock(LeaveRequestRepositoryInterface::class);
        $repo->method('findById')->willReturn(null);

        $service = new LeaveRequestService($repo);
        $this->expectException(LeaveRequestNotFoundException::class);
        $service->findById(999);
    }

    public function test_leave_request_service_finds_by_id(): void
    {
        $request = $this->makeLeaveRequest();
        $repo    = $this->createMock(LeaveRequestRepositoryInterface::class);
        $repo->expects($this->once())->method('findById')->with(1)->willReturn($request);

        $service = new LeaveRequestService($repo);
        $result  = $service->findById(1);
        $this->assertEquals(1, $result->getEmployeeId());
    }

    public function test_leave_request_service_finds_pending(): void
    {
        $repo = $this->createMock(LeaveRequestRepositoryInterface::class);
        $repo->expects($this->once())->method('findPendingByTenant')->with(1)->willReturn([$this->makeLeaveRequest()]);

        $service = new LeaveRequestService($repo);
        $result  = $service->findPendingByTenant(1);
        $this->assertCount(1, $result);
    }

    public function test_payroll_service_throws_not_found(): void
    {
        $payrollRepo  = $this->createMock(PayrollRepositoryInterface::class);
        $employeeRepo = $this->createMock(EmployeeRepositoryInterface::class);
        $payrollRepo->method('findById')->willReturn(null);

        $service = new PayrollService($payrollRepo, $employeeRepo);
        $this->expectException(PayrollRecordNotFoundException::class);
        $service->findById(999);
    }

    // ──────────────────────────────────────────────────────────────────────
    // Biometric Device Tests
    // ──────────────────────────────────────────────────────────────────────

    public function test_mock_biometric_driver_ping(): void
    {
        $driver = new MockBiometricDriver();
        $this->assertTrue($driver->ping());
    }

    public function test_mock_biometric_driver_capture_sample(): void
    {
        $driver = new MockBiometricDriver();
        $sample = $driver->captureSample();
        $this->assertNotNull($sample);
        $this->assertStringStartsWith('mock_sample_', $sample);
    }

    public function test_mock_biometric_driver_verify_returns_true_by_default(): void
    {
        $driver = new MockBiometricDriver();
        $this->assertTrue($driver->verify('sample', 'template'));
    }

    public function test_mock_biometric_driver_verify_can_return_false(): void
    {
        $driver = new MockBiometricDriver([], false);
        $this->assertFalse($driver->verify('sample', 'template'));
    }

    public function test_mock_biometric_driver_enroll(): void
    {
        $driver   = new MockBiometricDriver();
        $template = $driver->enroll(1, 'raw_fingerprint');
        $this->assertStringStartsWith('mock_template_1_', $template);
    }

    public function test_mock_biometric_driver_record_attendance_event(): void
    {
        $driver = new MockBiometricDriver(['device_id' => 'dev_01']);
        $event  = $driver->recordAttendanceEvent('fp_data', AttendanceRecord::TYPE_CHECK_IN);
        $this->assertTrue($event['verified']);
        $this->assertEquals('dev_01', $event['device_id']);
        $this->assertEquals(AttendanceRecord::TYPE_CHECK_IN, $event['event_type']);
    }

    public function test_biometric_device_manager_resolves_mock_driver(): void
    {
        $manager = new BiometricDeviceManager([]);
        $driver  = $manager->driver('mock');
        $this->assertInstanceOf(MockBiometricDriver::class, $driver);
    }

    public function test_biometric_device_manager_extend(): void
    {
        $manager    = new BiometricDeviceManager([]);
        $mockDriver = new MockBiometricDriver(['device_id' => 'custom']);
        $manager->extend('custom_device', $mockDriver);
        $resolved = $manager->driver('custom_device');
        $this->assertSame($mockDriver, $resolved);
    }

    public function test_biometric_device_manager_returns_same_instance(): void
    {
        $manager = new BiometricDeviceManager([]);
        $d1      = $manager->driver('mock');
        $d2      = $manager->driver('mock');
        $this->assertSame($d1, $d2);
    }
}
