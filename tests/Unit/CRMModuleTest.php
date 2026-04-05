<?php
declare(strict_types=1);
namespace Tests\Unit;

use Modules\CRM\Application\Services\ActivityService;
use Modules\CRM\Application\Services\ContactService;
use Modules\CRM\Application\Services\LeadService;
use Modules\CRM\Application\Services\OpportunityService;
use Modules\CRM\Domain\Entities\Activity;
use Modules\CRM\Domain\Entities\Contact;
use Modules\CRM\Domain\Entities\Lead;
use Modules\CRM\Domain\Entities\Opportunity;
use Modules\CRM\Domain\Exceptions\ActivityNotFoundException;
use Modules\CRM\Domain\Exceptions\ContactNotFoundException;
use Modules\CRM\Domain\Exceptions\LeadNotFoundException;
use Modules\CRM\Domain\Exceptions\OpportunityNotFoundException;
use Modules\CRM\Domain\RepositoryInterfaces\ActivityRepositoryInterface;
use Modules\CRM\Domain\RepositoryInterfaces\ContactRepositoryInterface;
use Modules\CRM\Domain\RepositoryInterfaces\LeadRepositoryInterface;
use Modules\CRM\Domain\RepositoryInterfaces\OpportunityRepositoryInterface;
use PHPUnit\Framework\TestCase;

class CRMModuleTest extends TestCase
{
    // ── helpers ────────────────────────────────────────────────────────────
    private function makeContact(?int $id = 1, bool $active = true): Contact
    {
        return new Contact($id, 1, Contact::TYPE_PERSON, 'John', 'Doe', 'Acme', 'CEO',
            'john@example.com', '123', '456', '1 Main St', null, null, null,
            $active, null, null);
    }

    private function makeLead(?int $id = 1, string $status = Lead::STATUS_NEW): Lead
    {
        return new Lead($id, 1, 'Prospect Corp', 'lead@test.com', null, 'Prospect Corp',
            'web', $status, 5000.0, null, null, null, null, null);
    }

    private function makeOpportunity(?int $id = 1, string $stage = Opportunity::STAGE_PROSPECTING): Opportunity
    {
        return new Opportunity($id, 1, 'Big Deal', null, null, null,
            $stage, 50.0, 10000.0, 'USD',
            new \DateTimeImmutable('+30 days'), null, null, null, null);
    }

    private function makeActivity(?int $id = 1, string $status = Activity::STATUS_PLANNED): Activity
    {
        return new Activity($id, 1, Activity::TYPE_CALL, 'Follow-up call',
            null, $status, null, null, null, null,
            new \DateTimeImmutable('+1 hour'), null, null, null);
    }

    // ── Contact entity tests ─────────────────────────────────────────────
    public function testContactCreation(): void
    {
        $c = $this->makeContact();
        $this->assertSame(1, $c->getId());
        $this->assertSame('John Doe', $c->getFullName());
        $this->assertTrue($c->isActive());
        $this->assertSame(Contact::TYPE_PERSON, $c->getType());
    }

    public function testContactActivateDeactivate(): void
    {
        $c = $this->makeContact(active: false);
        $this->assertFalse($c->isActive());
        $c->activate();
        $this->assertTrue($c->isActive());
        $c->deactivate();
        $this->assertFalse($c->isActive());
    }

    public function testContactUpdate(): void
    {
        $c = $this->makeContact();
        $c->update('Jane', 'Smith', 'Beta Ltd', 'CTO', 'jane@beta.com', null, null, null);
        $this->assertSame('Jane Smith', $c->getFullName());
        $this->assertSame('Beta Ltd', $c->getCompany());
    }

    public function testContactFullNameOrganisationType(): void
    {
        $c = new Contact(null, 1, Contact::TYPE_ORGANISATION, 'Acme', null, null, null, null, null, null, null, null, null, null, true, null, null);
        $this->assertSame('Acme', $c->getFullName());
    }

    // ── Lead entity tests ────────────────────────────────────────────────
    public function testLeadCreation(): void
    {
        $lead = $this->makeLead();
        $this->assertSame('Prospect Corp', $lead->getName());
        $this->assertSame(Lead::STATUS_NEW, $lead->getStatus());
        $this->assertFalse($lead->isConverted());
    }

    public function testLeadQualify(): void
    {
        $lead = $this->makeLead();
        $lead->qualify();
        $this->assertSame(Lead::STATUS_QUALIFIED, $lead->getStatus());
    }

    public function testLeadDisqualify(): void
    {
        $lead = $this->makeLead();
        $lead->disqualify();
        $this->assertSame(Lead::STATUS_DISQUALIFIED, $lead->getStatus());
    }

    public function testLeadConvertRequiresQualified(): void
    {
        $this->expectException(\DomainException::class);
        $lead = $this->makeLead();
        $lead->convert();
    }

    public function testLeadConvertSuccess(): void
    {
        $lead = $this->makeLead(status: Lead::STATUS_QUALIFIED);
        $lead->convert();
        $this->assertTrue($lead->isConverted());
        $this->assertNotNull($lead->getConvertedAt());
    }

    public function testLeadCannotDisqualifyConverted(): void
    {
        $this->expectException(\DomainException::class);
        $lead = $this->makeLead(status: Lead::STATUS_CONVERTED);
        $lead->disqualify();
    }

    // ── Opportunity entity tests ─────────────────────────────────────────
    public function testOpportunityCreation(): void
    {
        $opp = $this->makeOpportunity();
        $this->assertSame('Big Deal', $opp->getName());
        $this->assertSame(50.0, $opp->getProbability());
        $this->assertSame(5000.0, $opp->getWeightedAmount()); // 10000 * 50%
        $this->assertFalse($opp->isClosed());
    }

    public function testOpportunityWeightedAmount(): void
    {
        $opp = $this->makeOpportunity();
        $this->assertEqualsWithDelta(5000.0, $opp->getWeightedAmount(), 0.001);
    }

    public function testOpportunityCloseWon(): void
    {
        $opp = $this->makeOpportunity();
        $opp->closeWon();
        $this->assertSame(Opportunity::STAGE_CLOSED_WON, $opp->getStage());
        $this->assertSame(100.0, $opp->getProbability());
        $this->assertTrue($opp->isClosed());
    }

    public function testOpportunityCloseLost(): void
    {
        $opp = $this->makeOpportunity();
        $opp->closeLost('budget cut');
        $this->assertSame(Opportunity::STAGE_CLOSED_LOST, $opp->getStage());
        $this->assertSame(0.0, $opp->getProbability());
        $this->assertStringContainsString('budget cut', $opp->getDescription());
    }

    public function testOpportunityAdvanceTo(): void
    {
        $opp = $this->makeOpportunity();
        $opp->advanceTo(Opportunity::STAGE_PROPOSAL);
        $this->assertSame(Opportunity::STAGE_PROPOSAL, $opp->getStage());
    }

    // ── Activity entity tests ────────────────────────────────────────────
    public function testActivityCreation(): void
    {
        $a = $this->makeActivity();
        $this->assertSame('Follow-up call', $a->getSubject());
        $this->assertSame(Activity::STATUS_PLANNED, $a->getStatus());
        $this->assertFalse($a->isCompleted());
    }

    public function testActivityComplete(): void
    {
        $a = $this->makeActivity();
        $a->complete('Call done');
        $this->assertTrue($a->isCompleted());
        $this->assertNotNull($a->getCompletedAt());
        $this->assertSame('Call done', $a->getDescription());
    }

    public function testActivityCompleteAlreadyCompletedThrows(): void
    {
        $this->expectException(\DomainException::class);
        $a = $this->makeActivity(status: Activity::STATUS_COMPLETED);
        $a->complete();
    }

    public function testActivityCancel(): void
    {
        $a = $this->makeActivity();
        $a->cancel();
        $this->assertSame(Activity::STATUS_CANCELLED, $a->getStatus());
    }

    public function testActivityCancelCompletedThrows(): void
    {
        $this->expectException(\DomainException::class);
        $a = $this->makeActivity(status: Activity::STATUS_COMPLETED);
        $a->cancel();
    }

    // ── Service tests ────────────────────────────────────────────────────
    public function testContactServiceFindNotFound(): void
    {
        $repo = $this->createMock(ContactRepositoryInterface::class);
        $repo->method('findById')->willReturn(null);
        $this->expectException(ContactNotFoundException::class);
        (new ContactService($repo))->findById(99);
    }

    public function testContactServiceCreate(): void
    {
        $contact = $this->makeContact();
        $repo = $this->createMock(ContactRepositoryInterface::class);
        $repo->method('create')->willReturn($contact);
        $result = (new ContactService($repo))->create(['first_name' => 'John']);
        $this->assertSame('John Doe', $result->getFullName());
    }

    public function testLeadServiceQualify(): void
    {
        $lead = $this->makeLead();
        $qualified = $this->makeLead(status: Lead::STATUS_QUALIFIED);
        $repo = $this->createMock(LeadRepositoryInterface::class);
        $repo->method('findById')->willReturn($lead);
        $repo->method('update')->willReturn($qualified);
        $result = (new LeadService($repo))->qualify(1);
        $this->assertSame(Lead::STATUS_QUALIFIED, $result->getStatus());
    }

    public function testLeadServiceNotFound(): void
    {
        $repo = $this->createMock(LeadRepositoryInterface::class);
        $repo->method('findById')->willReturn(null);
        $this->expectException(LeadNotFoundException::class);
        (new LeadService($repo))->findById(99);
    }

    public function testOpportunityServicePipelineSummary(): void
    {
        $opp1 = $this->makeOpportunity(1, Opportunity::STAGE_PROPOSAL);
        $opp2 = $this->makeOpportunity(2, Opportunity::STAGE_PROPOSAL);
        $repo = $this->createMock(OpportunityRepositoryInterface::class);
        $repo->method('findAllByTenant')->willReturn([$opp1, $opp2]);
        $svc = new OpportunityService($repo);
        $summary = $svc->getPipelineSummary(1);
        $this->assertArrayHasKey(Opportunity::STAGE_PROPOSAL, $summary);
        $this->assertSame(2, $summary[Opportunity::STAGE_PROPOSAL]['count']);
        $this->assertEqualsWithDelta(20000.0, $summary[Opportunity::STAGE_PROPOSAL]['total_amount'], 0.001);
    }

    public function testOpportunityServiceNotFound(): void
    {
        $repo = $this->createMock(OpportunityRepositoryInterface::class);
        $repo->method('findById')->willReturn(null);
        $this->expectException(OpportunityNotFoundException::class);
        (new OpportunityService($repo))->findById(99);
    }

    public function testActivityServiceComplete(): void
    {
        $activity  = $this->makeActivity();
        $completed = $this->makeActivity(status: Activity::STATUS_COMPLETED);
        $repo = $this->createMock(ActivityRepositoryInterface::class);
        $repo->method('findById')->willReturn($activity);
        $repo->method('update')->willReturn($completed);
        $result = (new ActivityService($repo))->complete(1, 'Done');
        $this->assertTrue($result->isCompleted());
    }

    public function testActivityServiceNotFound(): void
    {
        $repo = $this->createMock(ActivityRepositoryInterface::class);
        $repo->method('findById')->willReturn(null);
        $this->expectException(ActivityNotFoundException::class);
        (new ActivityService($repo))->findById(99);
    }

    // ── Exceptions ───────────────────────────────────────────────────────
    public function testExceptionMessages(): void
    {
        $this->assertStringContainsString('42', (new ContactNotFoundException(42))->getMessage());
        $this->assertStringContainsString('7',  (new LeadNotFoundException(7))->getMessage());
        $this->assertStringContainsString('5',  (new OpportunityNotFoundException(5))->getMessage());
        $this->assertStringContainsString('3',  (new ActivityNotFoundException(3))->getMessage());
    }
}
