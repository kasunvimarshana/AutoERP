<?php

declare(strict_types=1);

namespace Modules\Rental\Domain\Entities;

final class RentalAgreement
{
    private string $id;
    private string $tenantId;
    private string $reservationId;
    private string $agreementNumber;
    private ?string $digitalAgreementUrl;
    private string $securityDeposit;
    private string $currencyCode;
    private string $fuelPolicy;
    private string $mileagePolicy;
    private string $status;
    private \DateTime $signedAt;
    private int $version;

    public function __construct(
        string $id,
        string $tenantId,
        string $reservationId,
        string $agreementNumber,
        ?string $digitalAgreementUrl,
        string $securityDeposit,
        string $currencyCode,
        string $fuelPolicy,
        string $mileagePolicy,
        string $status,
        \DateTime $signedAt,
        int $version = 1,
    ) {
        $this->id = $id;
        $this->tenantId = $tenantId;
        $this->reservationId = $reservationId;
        $this->agreementNumber = $agreementNumber;
        $this->digitalAgreementUrl = $digitalAgreementUrl;
        $this->securityDeposit = $securityDeposit;
        $this->currencyCode = $currencyCode;
        $this->fuelPolicy = $fuelPolicy;
        $this->mileagePolicy = $mileagePolicy;
        $this->status = $status;
        $this->signedAt = $signedAt;
        $this->version = $version;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getTenantId(): string
    {
        return $this->tenantId;
    }

    public function getReservationId(): string
    {
        return $this->reservationId;
    }

    public function getAgreementNumber(): string
    {
        return $this->agreementNumber;
    }

    public function getDigitalAgreementUrl(): ?string
    {
        return $this->digitalAgreementUrl;
    }

    public function getSecurityDeposit(): string
    {
        return $this->securityDeposit;
    }

    public function getCurrencyCode(): string
    {
        return $this->currencyCode;
    }

    public function getFuelPolicy(): string
    {
        return $this->fuelPolicy;
    }

    public function getMileagePolicy(): string
    {
        return $this->mileagePolicy;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getSignedAt(): \DateTime
    {
        return $this->signedAt;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function activate(): void
    {
        $this->status = 'active';
        $this->version++;
    }

    public function complete(): void
    {
        $this->status = 'completed';
        $this->version++;
    }
}
