<?php declare(strict_types=1);

namespace Modules\Rental\Application\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Rental\Application\Contracts\ManageRentalAgreementServiceInterface;
use Modules\Rental\Domain\Entities\RentalAgreement;
use Modules\Rental\Domain\RepositoryInterfaces\RentalAgreementRepositoryInterface;

class ManageRentalAgreementService implements ManageRentalAgreementServiceInterface
{
    public function __construct(
        private readonly RentalAgreementRepositoryInterface $agreements,
    ) {}

    public function create(array $data): RentalAgreement
    {
        return DB::transaction(function () use ($data): RentalAgreement {
            $agreement = new RentalAgreement(
                id: Str::uuid()->toString(),
                tenantId: (string) $data['tenant_id'],
                reservationId: $data['reservation_id'],
                agreementNumber: $this->generateAgreementNumber(),
                signedDate: new \DateTime($data['signed_date'] ?? 'now'),
                termsAndConditions: $data['terms_and_conditions'] ?? null,
                totalPrice: (string) $data['total_price'],
                depositRequired: (string) $data['deposit_required'],
                insuranceRequired: $data['insurance_required'] ?? false,
                additionalNotes: $data['additional_notes'] ?? null,
            );

            $this->agreements->create($agreement);
            return $agreement;
        });
    }

    public function find(int $tenantId, string $id): RentalAgreement
    {
        $agreement = $this->agreements->findById($id);
        if (!$agreement || $agreement->getTenantId() !== (string) $tenantId) {
            throw new \Exception('Rental agreement not found');
        }
        return $agreement;
    }

    public function getActive(int $tenantId): array
    {
        return $this->agreements->getActive((string) $tenantId);
    }

    private function generateAgreementNumber(): string
    {
        return 'AGR-' . date('Ymd') . '-' . Str::random(6);
    }
}
