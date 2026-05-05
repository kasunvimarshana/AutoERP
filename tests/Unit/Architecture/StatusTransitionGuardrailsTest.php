<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\TestCase;

class StatusTransitionGuardrailsTest extends TestCase
{
    private string $repoRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repoRoot = dirname(__DIR__, 3);
    }

    public function test_high_risk_workflow_services_keep_transition_guards(): void
    {
        $requiredGuards = [
            'app/Modules/Purchase/Application/Services/SendPurchaseOrderService.php' => [
                '$entity->getStatus() !== \'draft\'',
                'Purchase order cannot be sent in its current state.',
            ],
            'app/Modules/Purchase/Application/Services/ConfirmPurchaseOrderService.php' => [
                'in_array($entity->getStatus(), [\'draft\', \'sent\'], true)',
                'Purchase order cannot be confirmed in its current state.',
            ],
            'app/Modules/Purchase/Application/Services/PostPurchaseReturnService.php' => [
                '$entity->getStatus() !== \'draft\'',
                'Purchase return cannot be posted in its current state.',
            ],
            'app/Modules/Purchase/Application/Services/RecordPurchasePaymentService.php' => [
                'in_array($invoice->getStatus(), [\'approved\', \'partial_paid\'], true)',
                'Payment can only be recorded against approved or partially paid invoices.',
            ],
            'app/Modules/Sales/Application/Services/RecordSalesPaymentService.php' => [
                'in_array($invoice->getStatus(), [\'sent\', \'partial_paid\', \'overdue\'], true)',
                'Payment can only be recorded against sent, partially paid, or overdue invoices.',
            ],
            'app/Modules/Sales/Domain/Entities/SalesInvoice.php' => [
                'in_array($this->status, [\'sent\', \'partial_paid\', \'overdue\'], true)',
                'Invoice cannot be marked as paid from its current status.',
            ],
            'app/Modules/Sales/Domain/Entities/SalesOrder.php' => [
                'in_array($this->status, [\'confirmed\', \'partial\'], true)',
                'Only confirmed or partial orders can be marked as shipped.',
            ],
            'app/Modules/Finance/Application/Services/CancelApprovalRequestService.php' => [
                'in_array($approvalRequest->getStatus(), [\'approved\', \'rejected\', \'cancelled\'], true)',
                'Approval request cannot be cancelled in its current state.',
            ],
            'app/Modules/HR/Application/Services/ApprovePayrollRunService.php' => [
                'PayrollRunStatus::DRAFT',
                'PayrollRunStatus::PROCESSING',
                'Only draft or processing payroll runs can be approved.',
            ],
            'app/Modules/HR/Application/Services/CancelLeaveRequestService.php' => [
                'LeaveRequestStatus::PENDING',
                'LeaveRequestStatus::APPROVED',
                'Only pending or approved leave requests can be cancelled.',
            ],
        ];

        foreach ($requiredGuards as $relativePath => $needles) {
            $contents = $this->readSource($relativePath);

            foreach ($needles as $needle) {
                $this->assertStringContainsString(
                    $needle,
                    $contents,
                    'Missing workflow transition guard in: '.$relativePath.' -> '.$needle
                );
            }
        }
    }

    private function readSource(string $relativePath): string
    {
        $fullPath = $this->repoRoot.'/'.$relativePath;
        $contents = file_get_contents($fullPath);

        if ($contents === false) {
            $this->fail('Unable to read source file: '.$relativePath);
        }

        return $contents;
    }
}
