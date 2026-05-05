<?php

declare(strict_types=1);

namespace Modules\Rental\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Rental\Domain\Entities\RentalAgreement;
use Modules\Rental\Domain\RepositoryInterfaces\RentalAgreementRepositoryInterface;
use Modules\Rental\Infrastructure\Persistence\Eloquent\Models\RentalAgreementModel;

class EloquentRentalAgreementRepository implements RentalAgreementRepositoryInterface
{
    public function create(RentalAgreement $agreement): void
    {
        RentalAgreementModel::create([
            'id' => $agreement->getId(),
            'tenant_id' => $agreement->getTenantId(),
            'reservation_id' => $agreement->getReservationId(),
            'agreement_number' => $agreement->getAgreementNumber(),
            'digital_agreement_url' => $agreement->getDigitalAgreementUrl(),
            'security_deposit' => $agreement->getSecurityDeposit(),
            'currency_code' => $agreement->getCurrencyCode(),
            'fuel_policy' => $agreement->getFuelPolicy(),
            'mileage_policy' => $agreement->getMileagePolicy(),
            'status' => $agreement->getStatus(),
            'signed_at' => $agreement->getSignedAt(),
            'version' => $agreement->getVersion(),
        ]);
    }

    public function findById(string $id): ?RentalAgreement
    {
        $model = RentalAgreementModel::find($id);

        return $model ? $this->toDomain($model) : null;
    }

    public function findByAgreementNumber(string $tenantId, string $agreementNumber): ?RentalAgreement
    {
        $model = RentalAgreementModel::byTenant($tenantId)
            ->where('agreement_number', $agreementNumber)
            ->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function findByReservationId(string $reservationId): ?RentalAgreement
    {
        $model = RentalAgreementModel::where('reservation_id', $reservationId)->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function getActive(string $tenantId, int $page = 1, int $limit = 50): array
    {
        $query = RentalAgreementModel::byTenant($tenantId)->active();
        $total = $query->count();
        $data = $query->paginate($limit, ['*'], 'page', $page)->items();

        return [
            'data' => array_map(fn ($m) => $this->toDomain($m), $data),
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
        ];
    }

    public function update(RentalAgreement $agreement): void
    {
        RentalAgreementModel::findOrFail($agreement->getId())->update([
            'digital_agreement_url' => $agreement->getDigitalAgreementUrl(),
            'security_deposit' => $agreement->getSecurityDeposit(),
            'currency_code' => $agreement->getCurrencyCode(),
            'fuel_policy' => $agreement->getFuelPolicy(),
            'mileage_policy' => $agreement->getMileagePolicy(),
            'status' => $agreement->getStatus(),
            'signed_at' => $agreement->getSignedAt(),
            'version' => $agreement->getVersion(),
        ]);
    }

    private function toDomain(RentalAgreementModel $model): RentalAgreement
    {
        return new RentalAgreement(
            id: (string) $model->id,
            tenantId: (string) $model->tenant_id,
            reservationId: (string) $model->reservation_id,
            agreementNumber: (string) $model->agreement_number,
            digitalAgreementUrl: $model->digital_agreement_url,
            securityDeposit: (string) $model->security_deposit,
            currencyCode: (string) $model->currency_code,
            fuelPolicy: (string) $model->fuel_policy,
            mileagePolicy: (string) $model->mileage_policy,
            status: (string) $model->status,
            signedAt: $model->signed_at,
            version: (int) $model->version,
        );
    }
}
