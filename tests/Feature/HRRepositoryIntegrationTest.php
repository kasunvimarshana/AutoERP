<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\HR\Domain\Entities\AttendanceLog;
use Modules\HR\Domain\Entities\AttendanceRecord;
use Modules\HR\Domain\Entities\BiometricDevice;
use Modules\HR\Domain\Entities\EmployeeDocument;
use Modules\HR\Domain\Entities\LeaveBalance;
use Modules\HR\Domain\Entities\LeavePolicy;
use Modules\HR\Domain\Entities\LeaveRequest;
use Modules\HR\Domain\Entities\LeaveType;
use Modules\HR\Domain\Entities\PayrollItem;
use Modules\HR\Domain\Entities\PayrollRun;
use Modules\HR\Domain\Entities\Payslip;
use Modules\HR\Domain\Entities\PerformanceCycle;
use Modules\HR\Domain\Entities\PerformanceReview;
use Modules\HR\Domain\Entities\Shift;
use Modules\HR\Domain\Entities\ShiftAssignment;
use Modules\HR\Domain\RepositoryInterfaces\AttendanceLogRepositoryInterface;
use Modules\HR\Domain\RepositoryInterfaces\AttendanceRecordRepositoryInterface;
use Modules\HR\Domain\RepositoryInterfaces\BiometricDeviceRepositoryInterface;
use Modules\HR\Domain\RepositoryInterfaces\EmployeeDocumentRepositoryInterface;
use Modules\HR\Domain\RepositoryInterfaces\LeaveBalanceRepositoryInterface;
use Modules\HR\Domain\RepositoryInterfaces\LeavePolicyRepositoryInterface;
use Modules\HR\Domain\RepositoryInterfaces\LeaveRequestRepositoryInterface;
use Modules\HR\Domain\RepositoryInterfaces\LeaveTypeRepositoryInterface;
use Modules\HR\Domain\RepositoryInterfaces\PayrollItemRepositoryInterface;
use Modules\HR\Domain\RepositoryInterfaces\PayrollRunRepositoryInterface;
use Modules\HR\Domain\RepositoryInterfaces\PayslipRepositoryInterface;
use Modules\HR\Domain\RepositoryInterfaces\PerformanceCycleRepositoryInterface;
use Modules\HR\Domain\RepositoryInterfaces\PerformanceReviewRepositoryInterface;
use Modules\HR\Domain\RepositoryInterfaces\ShiftAssignmentRepositoryInterface;
use Modules\HR\Domain\RepositoryInterfaces\ShiftRepositoryInterface;
use Modules\HR\Domain\ValueObjects\AttendanceStatus;
use Modules\HR\Domain\ValueObjects\BiometricDeviceStatus;
use Modules\HR\Domain\ValueObjects\LeaveRequestStatus;
use Modules\HR\Domain\ValueObjects\PayrollRunStatus;
use Modules\HR\Domain\ValueObjects\PerformanceRating;
use Modules\HR\Domain\ValueObjects\ShiftType;
use Tests\TestCase;

class HRRepositoryIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private int $tenantId = 1;
    private int $tenant2Id = 2;
    private int $userId1 = 101;
    private int $userId2 = 102;
    private int $employeeId1 = 201;
    private int $employeeId2 = 202;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedReferenceData();
    }

    // ── ShiftRepository ───────────────────────────────────────────────────────

    public function test_shift_save_and_find(): void
    {
        /** @var ShiftRepositoryInterface $repository */
        $repository = app(ShiftRepositoryInterface::class);

        $now = new \DateTimeImmutable();

        $saved = $repository->save(new Shift(
            tenantId: $this->tenantId,
            name: 'Morning Shift',
            code: 'MORN',
            shiftType: ShiftType::REGULAR,
            startTime: '08:00',
            endTime: '17:00',
            breakDuration: 60,
            workDays: [1, 2, 3, 4, 5],
            graceMinutes: 10,
            overtimeThreshold: 480,
            isNightShift: false,
            metadata: [],
            isActive: true,
            createdAt: $now,
            updatedAt: $now,
        ));

        $found = $repository->find($saved->getId());

        $this->assertNotNull($found);
        $this->assertSame($saved->getId(), $found->getId());
        $this->assertSame('Morning Shift', $found->getName());
        $this->assertSame('MORN', $found->getCode());
        $this->assertSame($this->tenantId, $found->getTenantId());
        $this->assertTrue($found->isActive());
    }

    public function test_shift_find_by_tenant_and_code(): void
    {
        /** @var ShiftRepositoryInterface $repository */
        $repository = app(ShiftRepositoryInterface::class);

        $now = new \DateTimeImmutable();

        $saved = $repository->save(new Shift(
            tenantId: $this->tenantId,
            name: 'Night Shift',
            code: 'NIGHT',
            shiftType: ShiftType::NIGHT,
            startTime: '22:00',
            endTime: '06:00',
            breakDuration: 30,
            workDays: [1, 2, 3, 4, 5],
            graceMinutes: 15,
            overtimeThreshold: 480,
            isNightShift: true,
            metadata: [],
            isActive: true,
            createdAt: $now,
            updatedAt: $now,
        ));

        $found = $repository->findByTenantAndCode($this->tenantId, 'NIGHT');
        $wrongTenant = $repository->findByTenantAndCode($this->tenant2Id, 'NIGHT');
        $notFound = $repository->findByTenantAndCode($this->tenantId, 'MISSING');

        $this->assertNotNull($found);
        $this->assertSame($saved->getId(), $found->getId());
        $this->assertNull($wrongTenant);
        $this->assertNull($notFound);
    }

    // ── LeaveTypeRepository ───────────────────────────────────────────────────

    public function test_leave_type_save_and_find(): void
    {
        /** @var LeaveTypeRepositoryInterface $repository */
        $repository = app(LeaveTypeRepositoryInterface::class);

        $now = new \DateTimeImmutable();

        $saved = $repository->save(new LeaveType(
            tenantId: $this->tenantId,
            name: 'Annual Leave',
            code: 'AL',
            description: 'Paid annual leave',
            maxDaysPerYear: 21.0,
            carryForwardDays: 5.0,
            isPaid: true,
            requiresApproval: true,
            applicableGender: null,
            minServiceDays: 90,
            isActive: true,
            metadata: [],
            createdAt: $now,
            updatedAt: $now,
        ));

        $found = $repository->find($saved->getId());

        $this->assertNotNull($found);
        $this->assertSame($saved->getId(), $found->getId());
        $this->assertSame('Annual Leave', $found->getName());
        $this->assertSame('AL', $found->getCode());
        $this->assertSame(21.0, $found->getMaxDaysPerYear());
        $this->assertTrue($found->isPaid());
    }

    public function test_leave_type_find_by_tenant_and_code_is_tenant_scoped(): void
    {
        /** @var LeaveTypeRepositoryInterface $repository */
        $repository = app(LeaveTypeRepositoryInterface::class);

        $now = new \DateTimeImmutable();

        $saved = $repository->save(new LeaveType(
            tenantId: $this->tenantId,
            name: 'Sick Leave',
            code: 'SL',
            description: 'Sick leave',
            maxDaysPerYear: 10.0,
            carryForwardDays: 0.0,
            isPaid: true,
            requiresApproval: false,
            applicableGender: null,
            minServiceDays: 0,
            isActive: true,
            metadata: [],
            createdAt: $now,
            updatedAt: $now,
        ));

        $found = $repository->findByTenantAndCode($this->tenantId, 'SL');
        $wrongTenant = $repository->findByTenantAndCode($this->tenant2Id, 'SL');

        $this->assertNotNull($found);
        $this->assertSame($saved->getId(), $found->getId());
        $this->assertNull($wrongTenant);
    }

    // ── LeavePolicyRepository ─────────────────────────────────────────────────

    public function test_leave_policy_save_and_find(): void
    {
        /** @var LeaveTypeRepositoryInterface $ltRepo */
        $ltRepo = app(LeaveTypeRepositoryInterface::class);

        /** @var LeavePolicyRepositoryInterface $repository */
        $repository = app(LeavePolicyRepositoryInterface::class);

        $now = new \DateTimeImmutable();

        $leaveType = $ltRepo->save(new LeaveType(
            tenantId: $this->tenantId,
            name: 'Casual Leave',
            code: 'CL',
            description: '',
            maxDaysPerYear: 12.0,
            carryForwardDays: 0.0,
            isPaid: true,
            requiresApproval: false,
            applicableGender: null,
            minServiceDays: 0,
            isActive: true,
            metadata: [],
            createdAt: $now,
            updatedAt: $now,
        ));

        $saved = $repository->save(new LeavePolicy(
            tenantId: $this->tenantId,
            leaveTypeId: $leaveType->getId(),
            name: 'Default Policy',
            accrualType: 'monthly',
            accrualAmount: 1.0,
            orgUnitId: null,
            isActive: true,
            metadata: [],
            createdAt: $now,
            updatedAt: $now,
        ));

        $found = $repository->find($saved->getId());

        $this->assertNotNull($found);
        $this->assertSame($saved->getId(), $found->getId());
        $this->assertSame('Default Policy', $found->getName());
        $this->assertSame('monthly', $found->getAccrualType());
        $this->assertSame(1.0, $found->getAccrualAmount());
    }

    // ── LeaveBalanceRepository ────────────────────────────────────────────────

    public function test_leave_balance_save_and_find_by_employee_and_type(): void
    {
        /** @var LeaveTypeRepositoryInterface $ltRepo */
        $ltRepo = app(LeaveTypeRepositoryInterface::class);

        /** @var LeaveBalanceRepositoryInterface $repository */
        $repository = app(LeaveBalanceRepositoryInterface::class);

        $now = new \DateTimeImmutable();

        $lt = $ltRepo->save(new LeaveType(
            tenantId: $this->tenantId,
            name: 'Annual',
            code: 'ANL',
            description: '',
            maxDaysPerYear: 21.0,
            carryForwardDays: 3.0,
            isPaid: true,
            requiresApproval: true,
            applicableGender: null,
            minServiceDays: 0,
            isActive: true,
            metadata: [],
            createdAt: $now,
            updatedAt: $now,
        ));

        $saved = $repository->save(new LeaveBalance(
            tenantId: $this->tenantId,
            employeeId: $this->employeeId1,
            leaveTypeId: $lt->getId(),
            year: 2025,
            allocated: 21.0,
            used: 5.0,
            pending: 2.0,
            carried: 3.0,
            createdAt: $now,
            updatedAt: $now,
        ));

        $found = $repository->findByEmployeeAndType($this->tenantId, $this->employeeId1, $lt->getId(), 2025);
        $wrongEmployee = $repository->findByEmployeeAndType($this->tenantId, $this->employeeId2, $lt->getId(), 2025);
        $wrongYear = $repository->findByEmployeeAndType($this->tenantId, $this->employeeId1, $lt->getId(), 2024);

        $this->assertNotNull($found);
        $this->assertSame($saved->getId(), $found->getId());
        $this->assertSame(21.0, $found->getAllocated());
        $this->assertSame(5.0, $found->getUsed());
        $this->assertNull($wrongEmployee);
        $this->assertNull($wrongYear);
    }

    public function test_leave_balance_get_balances_for_employee(): void
    {
        /** @var LeaveTypeRepositoryInterface $ltRepo */
        $ltRepo = app(LeaveTypeRepositoryInterface::class);

        /** @var LeaveBalanceRepositoryInterface $repository */
        $repository = app(LeaveBalanceRepositoryInterface::class);

        $now = new \DateTimeImmutable();

        $lt1 = $ltRepo->save(new LeaveType(tenantId: $this->tenantId, name: 'AL-B', code: 'AL-B', description: '', maxDaysPerYear: 21.0, carryForwardDays: 0.0, isPaid: true, requiresApproval: true, applicableGender: null, minServiceDays: 0, isActive: true, metadata: [], createdAt: $now, updatedAt: $now));
        $lt2 = $ltRepo->save(new LeaveType(tenantId: $this->tenantId, name: 'SL-B', code: 'SL-B', description: '', maxDaysPerYear: 10.0, carryForwardDays: 0.0, isPaid: true, requiresApproval: false, applicableGender: null, minServiceDays: 0, isActive: true, metadata: [], createdAt: $now, updatedAt: $now));

        $repository->save(new LeaveBalance(tenantId: $this->tenantId, employeeId: $this->employeeId1, leaveTypeId: $lt1->getId(), year: 2025, allocated: 21.0, used: 0.0, pending: 0.0, carried: 0.0, createdAt: $now, updatedAt: $now));
        $repository->save(new LeaveBalance(tenantId: $this->tenantId, employeeId: $this->employeeId1, leaveTypeId: $lt2->getId(), year: 2025, allocated: 10.0, used: 0.0, pending: 0.0, carried: 0.0, createdAt: $now, updatedAt: $now));
        // Different employee — should not appear
        $repository->save(new LeaveBalance(tenantId: $this->tenantId, employeeId: $this->employeeId2, leaveTypeId: $lt1->getId(), year: 2025, allocated: 21.0, used: 0.0, pending: 0.0, carried: 0.0, createdAt: $now, updatedAt: $now));

        $balances = $repository->getBalancesForEmployee($this->tenantId, $this->employeeId1, 2025);

        $this->assertCount(2, $balances);
        $leaveTypeIds = array_map(fn ($b) => $b->getLeaveTypeId(), $balances);
        $this->assertContains($lt1->getId(), $leaveTypeIds);
        $this->assertContains($lt2->getId(), $leaveTypeIds);
    }

    // ── LeaveRequestRepository ────────────────────────────────────────────────

    public function test_leave_request_save_and_find(): void
    {
        /** @var LeaveTypeRepositoryInterface $ltRepo */
        $ltRepo = app(LeaveTypeRepositoryInterface::class);

        /** @var LeaveRequestRepositoryInterface $repository */
        $repository = app(LeaveRequestRepositoryInterface::class);

        $now = new \DateTimeImmutable();

        $lt = $ltRepo->save(new LeaveType(tenantId: $this->tenantId, name: 'PL', code: 'PL', description: '', maxDaysPerYear: 5.0, carryForwardDays: 0.0, isPaid: true, requiresApproval: true, applicableGender: null, minServiceDays: 0, isActive: true, metadata: [], createdAt: $now, updatedAt: $now));

        $saved = $repository->save(new LeaveRequest(
            tenantId: $this->tenantId,
            employeeId: $this->employeeId1,
            leaveTypeId: $lt->getId(),
            startDate: new \DateTimeImmutable('2025-06-01'),
            endDate: new \DateTimeImmutable('2025-06-03'),
            totalDays: 3.0,
            reason: 'Family event',
            status: LeaveRequestStatus::PENDING,
            approverId: null,
            approverNote: '',
            attachmentPath: null,
            metadata: [],
            createdAt: $now,
            updatedAt: $now,
        ));

        $found = $repository->find($saved->getId());

        $this->assertNotNull($found);
        $this->assertSame($saved->getId(), $found->getId());
        $this->assertSame(3.0, $found->getTotalDays());
        $this->assertSame(LeaveRequestStatus::PENDING, $found->getStatus());
        $this->assertSame($this->employeeId1, $found->getEmployeeId());
    }

    public function test_leave_request_find_overlapping(): void
    {
        /** @var LeaveTypeRepositoryInterface $ltRepo */
        $ltRepo = app(LeaveTypeRepositoryInterface::class);

        /** @var LeaveRequestRepositoryInterface $repository */
        $repository = app(LeaveRequestRepositoryInterface::class);

        $now = new \DateTimeImmutable();

        $lt = $ltRepo->save(new LeaveType(tenantId: $this->tenantId, name: 'OL', code: 'OL', description: '', maxDaysPerYear: 14.0, carryForwardDays: 0.0, isPaid: true, requiresApproval: true, applicableGender: null, minServiceDays: 0, isActive: true, metadata: [], createdAt: $now, updatedAt: $now));

        // Existing request: June 10–14
        $existing = $repository->save(new LeaveRequest(
            tenantId: $this->tenantId,
            employeeId: $this->employeeId1,
            leaveTypeId: $lt->getId(),
            startDate: new \DateTimeImmutable('2025-06-10'),
            endDate: new \DateTimeImmutable('2025-06-14'),
            totalDays: 5.0,
            reason: 'Vacation',
            status: LeaveRequestStatus::APPROVED,
            approverId: null,
            approverNote: '',
            attachmentPath: null,
            metadata: [],
            createdAt: $now,
            updatedAt: $now,
        ));

        // Overlapping range (June 12–16) → should find conflict
        $overlaps = $repository->findOverlapping($this->tenantId, $this->employeeId1, '2025-06-12', '2025-06-16');
        // Non-overlapping range
        $noOverlap = $repository->findOverlapping($this->tenantId, $this->employeeId1, '2025-06-20', '2025-06-25');
        // Same range but excluding the existing record
        $excluded = $repository->findOverlapping($this->tenantId, $this->employeeId1, '2025-06-12', '2025-06-16', $existing->getId());

        $this->assertNotEmpty($overlaps);
        $this->assertEmpty($noOverlap);
        $this->assertEmpty($excluded);
    }

    // ── ShiftAssignmentRepository ─────────────────────────────────────────────

    public function test_shift_assignment_save_and_find_current_for_employee(): void
    {
        /** @var ShiftRepositoryInterface $shiftRepo */
        $shiftRepo = app(ShiftRepositoryInterface::class);

        /** @var ShiftAssignmentRepositoryInterface $repository */
        $repository = app(ShiftAssignmentRepositoryInterface::class);

        $now = new \DateTimeImmutable();

        $shift = $shiftRepo->save(new Shift(
            tenantId: $this->tenantId,
            name: 'Day Shift',
            code: 'DAY',
            shiftType: ShiftType::REGULAR,
            startTime: '09:00',
            endTime: '18:00',
            breakDuration: 60,
            workDays: [1, 2, 3, 4, 5],
            graceMinutes: 10,
            overtimeThreshold: 480,
            isNightShift: false,
            metadata: [],
            isActive: true,
            createdAt: $now,
            updatedAt: $now,
        ));

        $saved = $repository->save(new ShiftAssignment(
            tenantId: $this->tenantId,
            employeeId: $this->employeeId1,
            shiftId: $shift->getId(),
            effectiveFrom: new \DateTimeImmutable('2025-01-01'),
            effectiveTo: null,
            createdAt: $now,
            updatedAt: $now,
        ));

        $current = $repository->findCurrentForEmployee($this->tenantId, $this->employeeId1, '2025-06-15');
        $wrongEmployee = $repository->findCurrentForEmployee($this->tenantId, $this->employeeId2, '2025-06-15');
        $beforeStart = $repository->findCurrentForEmployee($this->tenantId, $this->employeeId1, '2024-12-31');

        $this->assertNotNull($current);
        $this->assertSame($saved->getId(), $current->getId());
        $this->assertSame($shift->getId(), $current->getShiftId());
        $this->assertNull($wrongEmployee);
        $this->assertNull($beforeStart);
    }

    // ── AttendanceRecordRepository ────────────────────────────────────────────

    public function test_attendance_record_save_find_by_employee_and_date(): void
    {
        /** @var AttendanceRecordRepositoryInterface $repository */
        $repository = app(AttendanceRecordRepositoryInterface::class);

        $now = new \DateTimeImmutable();

        $saved = $repository->save(new AttendanceRecord(
            tenantId: $this->tenantId,
            employeeId: $this->employeeId1,
            attendanceDate: new \DateTimeImmutable('2025-06-01'),
            checkIn: new \DateTimeImmutable('2025-06-01 09:00:00'),
            checkOut: new \DateTimeImmutable('2025-06-01 17:30:00'),
            breakDuration: 30,
            workedMinutes: 480,
            overtimeMinutes: 0,
            status: AttendanceStatus::PRESENT,
            shiftId: null,
            remarks: '',
            metadata: [],
            createdAt: $now,
            updatedAt: $now,
        ));

        $found = $repository->findByEmployeeAndDate($this->tenantId, $this->employeeId1, '2025-06-01');
        $wrongDate = $repository->findByEmployeeAndDate($this->tenantId, $this->employeeId1, '2025-06-02');
        $wrongEmployee = $repository->findByEmployeeAndDate($this->tenantId, $this->employeeId2, '2025-06-01');

        $this->assertNotNull($found);
        $this->assertSame($saved->getId(), $found->getId());
        $this->assertSame(AttendanceStatus::PRESENT, $found->getStatus());
        $this->assertNull($wrongDate);
        $this->assertNull($wrongEmployee);
    }

    public function test_attendance_record_find_by_employee_and_month(): void
    {
        /** @var AttendanceRecordRepositoryInterface $repository */
        $repository = app(AttendanceRecordRepositoryInterface::class);

        $now = new \DateTimeImmutable();

        $r1 = $repository->save(new AttendanceRecord(tenantId: $this->tenantId, employeeId: $this->employeeId1, attendanceDate: new \DateTimeImmutable('2025-06-01'), checkIn: null, checkOut: null, breakDuration: 0, workedMinutes: 0, overtimeMinutes: 0, status: AttendanceStatus::ABSENT, shiftId: null, remarks: '', metadata: [], createdAt: $now, updatedAt: $now));
        $r2 = $repository->save(new AttendanceRecord(tenantId: $this->tenantId, employeeId: $this->employeeId1, attendanceDate: new \DateTimeImmutable('2025-06-02'), checkIn: null, checkOut: null, breakDuration: 0, workedMinutes: 0, overtimeMinutes: 0, status: AttendanceStatus::PRESENT, shiftId: null, remarks: '', metadata: [], createdAt: $now, updatedAt: $now));
        // Different month — should not appear
        $repository->save(new AttendanceRecord(tenantId: $this->tenantId, employeeId: $this->employeeId1, attendanceDate: new \DateTimeImmutable('2025-07-01'), checkIn: null, checkOut: null, breakDuration: 0, workedMinutes: 0, overtimeMinutes: 0, status: AttendanceStatus::PRESENT, shiftId: null, remarks: '', metadata: [], createdAt: $now, updatedAt: $now));

        $records = $repository->findByEmployeeAndMonth($this->tenantId, $this->employeeId1, 2025, 6);

        $this->assertCount(2, $records);
        $ids = array_map(fn ($r) => $r->getId(), $records);
        $this->assertContains($r1->getId(), $ids);
        $this->assertContains($r2->getId(), $ids);
    }

    // ── AttendanceLogRepository ───────────────────────────────────────────────

    public function test_attendance_log_save_and_find_by_employee_and_date(): void
    {
        /** @var AttendanceLogRepositoryInterface $repository */
        $repository = app(AttendanceLogRepositoryInterface::class);

        $now = new \DateTimeImmutable();

        $log1 = $repository->save(new AttendanceLog(
            tenantId: $this->tenantId,
            employeeId: $this->employeeId1,
            biometricDeviceId: null,
            punchTime: new \DateTimeImmutable('2025-06-01 09:01:00'),
            punchType: 'in',
            source: 'manual',
            rawData: [],
            processedAt: null,
            createdAt: $now,
            updatedAt: $now,
        ));
        $log2 = $repository->save(new AttendanceLog(
            tenantId: $this->tenantId,
            employeeId: $this->employeeId1,
            biometricDeviceId: null,
            punchTime: new \DateTimeImmutable('2025-06-01 17:31:00'),
            punchType: 'out',
            source: 'manual',
            rawData: [],
            processedAt: null,
            createdAt: $now,
            updatedAt: $now,
        ));
        // Different date — should not appear
        $repository->save(new AttendanceLog(
            tenantId: $this->tenantId,
            employeeId: $this->employeeId1,
            biometricDeviceId: null,
            punchTime: new \DateTimeImmutable('2025-06-02 09:00:00'),
            punchType: 'in',
            source: 'manual',
            rawData: [],
            processedAt: null,
            createdAt: $now,
            updatedAt: $now,
        ));

        $logs = $repository->findByEmployeeAndDate($this->tenantId, $this->employeeId1, '2025-06-01');

        $this->assertCount(2, $logs);
        $ids = array_map(fn ($l) => $l->getId(), $logs);
        $this->assertContains($log1->getId(), $ids);
        $this->assertContains($log2->getId(), $ids);
    }

    // ── BiometricDeviceRepository ─────────────────────────────────────────────

    public function test_biometric_device_save_and_find_by_tenant_and_code(): void
    {
        /** @var BiometricDeviceRepositoryInterface $repository */
        $repository = app(BiometricDeviceRepositoryInterface::class);

        $now = new \DateTimeImmutable();

        $saved = $repository->save(new BiometricDevice(
            tenantId: $this->tenantId,
            name: 'Main Entrance Reader',
            code: 'BIO-001',
            deviceType: 'fingerprint',
            ipAddress: '192.168.1.100',
            port: 4370,
            location: 'Main Entrance',
            orgUnitId: null,
            status: BiometricDeviceStatus::ACTIVE,
            metadata: [],
            createdAt: $now,
            updatedAt: $now,
        ));

        $found = $repository->findByTenantAndCode($this->tenantId, 'BIO-001');
        $wrongTenant = $repository->findByTenantAndCode($this->tenant2Id, 'BIO-001');
        $notFound = $repository->findByTenantAndCode($this->tenantId, 'MISSING');

        $this->assertNotNull($found);
        $this->assertSame($saved->getId(), $found->getId());
        $this->assertSame('Main Entrance Reader', $found->getName());
        $this->assertNull($wrongTenant);
        $this->assertNull($notFound);
    }

    // ── EmployeeDocumentRepository ────────────────────────────────────────────

    public function test_employee_document_save_and_find_by_employee(): void
    {
        /** @var EmployeeDocumentRepositoryInterface $repository */
        $repository = app(EmployeeDocumentRepositoryInterface::class);

        $now = new \DateTimeImmutable();

        $doc1 = $repository->save(new EmployeeDocument(
            tenantId: $this->tenantId,
            employeeId: $this->employeeId1,
            documentType: 'id_card',
            title: 'National ID',
            description: 'NIC copy',
            filePath: 'docs/nic.pdf',
            mimeType: 'application/pdf',
            fileSize: 204800,
            issuedDate: new \DateTimeImmutable('2020-01-01'),
            expiryDate: new \DateTimeImmutable('2030-01-01'),
            metadata: [],
            createdAt: $now,
            updatedAt: $now,
        ));
        $doc2 = $repository->save(new EmployeeDocument(
            tenantId: $this->tenantId,
            employeeId: $this->employeeId1,
            documentType: 'contract',
            title: 'Employment Contract',
            description: '',
            filePath: 'docs/contract.pdf',
            mimeType: 'application/pdf',
            fileSize: 512000,
            issuedDate: new \DateTimeImmutable('2024-01-01'),
            expiryDate: null,
            metadata: [],
            createdAt: $now,
            updatedAt: $now,
        ));
        // Different employee — should not appear
        $repository->save(new EmployeeDocument(
            tenantId: $this->tenantId,
            employeeId: $this->employeeId2,
            documentType: 'id_card',
            title: 'Other Employee NIC',
            description: '',
            filePath: 'docs/other.pdf',
            mimeType: 'application/pdf',
            fileSize: 100000,
            issuedDate: null,
            expiryDate: null,
            metadata: [],
            createdAt: $now,
            updatedAt: $now,
        ));

        $docs = $repository->findByEmployee($this->tenantId, $this->employeeId1);

        $this->assertCount(2, $docs);
        $ids = array_map(fn ($d) => $d->getId(), $docs);
        $this->assertContains($doc1->getId(), $ids);
        $this->assertContains($doc2->getId(), $ids);
    }

    // ── PayrollItemRepository ─────────────────────────────────────────────────

    public function test_payroll_item_save_and_find_by_tenant_and_code(): void
    {
        /** @var PayrollItemRepositoryInterface $repository */
        $repository = app(PayrollItemRepositoryInterface::class);

        $now = new \DateTimeImmutable();

        $saved = $repository->save(new PayrollItem(
            tenantId: $this->tenantId,
            name: 'Basic Salary',
            code: 'BASIC',
            type: 'earning',
            calculationType: 'fixed',
            value: '50000.000000',
            isActive: true,
            isTaxable: true,
            accountId: null,
            metadata: [],
            createdAt: $now,
            updatedAt: $now,
        ));

        $found = $repository->findByTenantAndCode($this->tenantId, 'BASIC');
        $wrongTenant = $repository->findByTenantAndCode($this->tenant2Id, 'BASIC');
        $notFound = $repository->findByTenantAndCode($this->tenantId, 'MISSING');

        $this->assertNotNull($found);
        $this->assertSame($saved->getId(), $found->getId());
        $this->assertSame('Basic Salary', $found->getName());
        $this->assertNull($wrongTenant);
        $this->assertNull($notFound);
    }

    // ── PayrollRunRepository ──────────────────────────────────────────────────

    public function test_payroll_run_save_and_find_by_tenant_and_period(): void
    {
        /** @var PayrollRunRepositoryInterface $repository */
        $repository = app(PayrollRunRepositoryInterface::class);

        $now = new \DateTimeImmutable();

        $saved = $repository->save(new PayrollRun(
            tenantId: $this->tenantId,
            periodStart: new \DateTimeImmutable('2025-06-01'),
            periodEnd: new \DateTimeImmutable('2025-06-30'),
            status: PayrollRunStatus::DRAFT,
            processedAt: null,
            approvedAt: null,
            approvedBy: null,
            totalGross: '0.000000',
            totalDeductions: '0.000000',
            totalNet: '0.000000',
            metadata: [],
            createdAt: $now,
            updatedAt: $now,
        ));

        $found = $repository->findByTenantAndPeriod($this->tenantId, '2025-06-01', '2025-06-30');
        $wrongPeriod = $repository->findByTenantAndPeriod($this->tenantId, '2025-07-01', '2025-07-31');
        $wrongTenant = $repository->findByTenantAndPeriod($this->tenant2Id, '2025-06-01', '2025-06-30');

        $this->assertNotNull($found);
        $this->assertSame($saved->getId(), $found->getId());
        $this->assertSame(PayrollRunStatus::DRAFT, $found->getStatus());
        $this->assertNull($wrongPeriod);
        $this->assertNull($wrongTenant);
    }

    // ── PayslipRepository ─────────────────────────────────────────────────────

    public function test_payslip_save_find_by_employee_and_run(): void
    {
        /** @var PayrollRunRepositoryInterface $runRepo */
        $runRepo = app(PayrollRunRepositoryInterface::class);

        /** @var PayslipRepositoryInterface $repository */
        $repository = app(PayslipRepositoryInterface::class);

        $now = new \DateTimeImmutable();

        $run = $runRepo->save(new PayrollRun(
            tenantId: $this->tenantId,
            periodStart: new \DateTimeImmutable('2025-06-01'),
            periodEnd: new \DateTimeImmutable('2025-06-30'),
            status: PayrollRunStatus::APPROVED,
            processedAt: null,
            approvedAt: null,
            approvedBy: null,
            totalGross: '100000.000000',
            totalDeductions: '15000.000000',
            totalNet: '85000.000000',
            metadata: [],
            createdAt: $now,
            updatedAt: $now,
        ));

        $payslip = $repository->save(new Payslip(
            tenantId: $this->tenantId,
            employeeId: $this->employeeId1,
            payrollRunId: $run->getId(),
            periodStart: new \DateTimeImmutable('2025-06-01'),
            periodEnd: new \DateTimeImmutable('2025-06-30'),
            grossSalary: '50000.000000',
            totalDeductions: '7500.000000',
            netSalary: '42500.000000',
            baseSalary: '45000.000000',
            workedDays: 22.0,
            status: 'draft',
            journalEntryId: null,
            metadata: [],
            createdAt: $now,
            updatedAt: $now,
        ));

        $found = $repository->findByEmployeeAndRun($this->tenantId, $this->employeeId1, $run->getId());
        $wrongEmployee = $repository->findByEmployeeAndRun($this->tenantId, $this->employeeId2, $run->getId());

        $this->assertNotNull($found);
        $this->assertSame($payslip->getId(), $found->getId());
        $this->assertSame($this->employeeId1, $found->getEmployeeId());
        $this->assertNull($wrongEmployee);
    }

    public function test_payslip_find_by_payroll_run(): void
    {
        /** @var PayrollRunRepositoryInterface $runRepo */
        $runRepo = app(PayrollRunRepositoryInterface::class);

        /** @var PayslipRepositoryInterface $repository */
        $repository = app(PayslipRepositoryInterface::class);

        $now = new \DateTimeImmutable();

        $run = $runRepo->save(new PayrollRun(
            tenantId: $this->tenantId,
            periodStart: new \DateTimeImmutable('2025-07-01'),
            periodEnd: new \DateTimeImmutable('2025-07-31'),
            status: PayrollRunStatus::DRAFT,
            processedAt: null,
            approvedAt: null,
            approvedBy: null,
            totalGross: '0.000000',
            totalDeductions: '0.000000',
            totalNet: '0.000000',
            metadata: [],
            createdAt: $now,
            updatedAt: $now,
        ));

        $p1 = $repository->save(new Payslip(tenantId: $this->tenantId, employeeId: $this->employeeId1, payrollRunId: $run->getId(), periodStart: new \DateTimeImmutable('2025-07-01'), periodEnd: new \DateTimeImmutable('2025-07-31'), grossSalary: '50000.000000', totalDeductions: '7500.000000', netSalary: '42500.000000', baseSalary: '45000.000000', workedDays: 23.0, status: 'draft', journalEntryId: null, metadata: [], createdAt: $now, updatedAt: $now));
        $p2 = $repository->save(new Payslip(tenantId: $this->tenantId, employeeId: $this->employeeId2, payrollRunId: $run->getId(), periodStart: new \DateTimeImmutable('2025-07-01'), periodEnd: new \DateTimeImmutable('2025-07-31'), grossSalary: '60000.000000', totalDeductions: '9000.000000', netSalary: '51000.000000', baseSalary: '55000.000000', workedDays: 23.0, status: 'draft', journalEntryId: null, metadata: [], createdAt: $now, updatedAt: $now));

        $payslips = $repository->findByPayrollRun($this->tenantId, $run->getId());

        $this->assertCount(2, $payslips);
        $ids = array_map(fn ($p) => $p->getId(), $payslips);
        $this->assertContains($p1->getId(), $ids);
        $this->assertContains($p2->getId(), $ids);
    }

    // ── PerformanceCycleRepository ────────────────────────────────────────────

    public function test_performance_cycle_save_and_find(): void
    {
        /** @var PerformanceCycleRepositoryInterface $repository */
        $repository = app(PerformanceCycleRepositoryInterface::class);

        $now = new \DateTimeImmutable();

        $saved = $repository->save(new PerformanceCycle(
            tenantId: $this->tenantId,
            name: 'Q2 2025 Review',
            periodStart: new \DateTimeImmutable('2025-04-01'),
            periodEnd: new \DateTimeImmutable('2025-06-30'),
            isActive: true,
            metadata: [],
            createdAt: $now,
            updatedAt: $now,
        ));

        $found = $repository->find($saved->getId());

        $this->assertNotNull($found);
        $this->assertSame($saved->getId(), $found->getId());
        $this->assertSame('Q2 2025 Review', $found->getName());
        $this->assertTrue($found->isActive());
    }

    // ── PerformanceReviewRepository ───────────────────────────────────────────

    public function test_performance_review_save_and_find_by_employee_and_cycle(): void
    {
        /** @var PerformanceCycleRepositoryInterface $cycleRepo */
        $cycleRepo = app(PerformanceCycleRepositoryInterface::class);

        /** @var PerformanceReviewRepositoryInterface $repository */
        $repository = app(PerformanceReviewRepositoryInterface::class);

        $now = new \DateTimeImmutable();

        $cycle = $cycleRepo->save(new PerformanceCycle(
            tenantId: $this->tenantId,
            name: 'Annual 2025',
            periodStart: new \DateTimeImmutable('2025-01-01'),
            periodEnd: new \DateTimeImmutable('2025-12-31'),
            isActive: true,
            metadata: [],
            createdAt: $now,
            updatedAt: $now,
        ));

        $saved = $repository->save(new PerformanceReview(
            tenantId: $this->tenantId,
            employeeId: $this->employeeId1,
            cycleId: $cycle->getId(),
            reviewerId: $this->userId2,
            overallRating: PerformanceRating::MEETS_EXPECTATIONS,
            goals: [],
            strengths: 'Excellent team player',
            improvements: 'Time management',
            reviewerComments: 'Good overall performance',
            employeeComments: 'Thank you',
            status: 'submitted',
            acknowledgedAt: null,
            metadata: [],
            createdAt: $now,
            updatedAt: $now,
        ));

        $found = $repository->findByEmployeeAndCycle($this->tenantId, $this->employeeId1, $cycle->getId());
        $wrongEmployee = $repository->findByEmployeeAndCycle($this->tenantId, $this->employeeId2, $cycle->getId());

        $this->assertNotNull($found);
        $this->assertSame($saved->getId(), $found->getId());
        $this->assertSame(PerformanceRating::MEETS_EXPECTATIONS, $found->getOverallRating());
        $this->assertNull($wrongEmployee);
    }

    // ── Seed ──────────────────────────────────────────────────────────────────

    private function seedReferenceData(): void
    {
        foreach ([$this->tenantId, $this->tenant2Id] as $tid) {
            DB::table('tenants')->insert([
                'id' => $tid,
                'name' => 'Tenant '.$tid,
                'slug' => 'tenant-'.$tid,
                'domain' => null,
                'logo_path' => null,
                'database_config' => null,
                'mail_config' => null,
                'cache_config' => null,
                'queue_config' => null,
                'feature_flags' => null,
                'api_keys' => null,
                'settings' => null,
                'plan' => 'free',
                'tenant_plan_id' => null,
                'status' => 'active',
                'active' => true,
                'trial_ends_at' => null,
                'subscription_ends_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ]);
        }

        foreach ([
            ['id' => $this->userId1, 'email' => 'emp1@example.com'],
            ['id' => $this->userId2, 'email' => 'emp2@example.com'],
        ] as $user) {
            DB::table('users')->insert([
                'id' => $user['id'],
                'tenant_id' => $this->tenantId,
                'org_unit_id' => null,
                'first_name' => 'Employee',
                'last_name' => (string) $user['id'],
                'email' => $user['email'],
                'email_verified_at' => null,
                'password' => 'hashed-password',
                'phone' => null,
                'avatar' => null,
                'status' => 'active',
                'preferences' => null,
                'address' => null,
                'remember_token' => null,
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ]);
        }

        DB::table('employees')->insert([
            ['id' => $this->employeeId1, 'tenant_id' => $this->tenantId, 'user_id' => $this->userId1, 'employee_code' => 'EMP-001', 'org_unit_id' => null, 'job_title' => 'Engineer', 'hire_date' => '2024-01-01', 'termination_date' => null, 'metadata' => null, 'row_version' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => $this->employeeId2, 'tenant_id' => $this->tenantId, 'user_id' => $this->userId2, 'employee_code' => 'EMP-002', 'org_unit_id' => null, 'job_title' => 'Manager', 'hire_date' => '2024-01-01', 'termination_date' => null, 'metadata' => null, 'row_version' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
