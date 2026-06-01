<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Application\Services;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Finance\Application\Contracts\Services\TaxCalculationServiceInterface;
use Modules\VehicleRental\Application\Contracts\Services\VehicleRentalManagementServiceInterface;
use Modules\VehicleRental\Application\Repositories\VehicleRentalAgreementLineRepositoryInterface;
use Modules\VehicleRental\Application\Repositories\VehicleRentalAgreementRateRepositoryInterface;
use Modules\VehicleRental\Application\Repositories\VehicleRentalAgreementRepositoryInterface;
use Modules\VehicleRental\Application\Repositories\VehicleRentalApprovalHistoryRepositoryInterface;
use Modules\VehicleRental\Application\Repositories\VehicleRentalBreakdownRepositoryInterface;
use Modules\VehicleRental\Application\Repositories\VehicleRentalExtraChargeRepositoryInterface;
use Modules\VehicleRental\Application\Repositories\VehicleRentalMetadataDefinitionRepositoryInterface;
use Modules\VehicleRental\Application\Repositories\VehicleRentalMetadataValueRepositoryInterface;
use Modules\VehicleRental\Application\Repositories\VehicleRentalProviderPayableRepositoryInterface;
use Modules\VehicleRental\Application\Repositories\VehicleRentalRateRuleRepositoryInterface;
use Modules\VehicleRental\Application\Repositories\VehicleRentalReplacementRepositoryInterface;
use Modules\VehicleRental\Application\Repositories\VehicleRentalRunningChartLineRepositoryInterface;
use Modules\VehicleRental\Application\Repositories\VehicleRentalRunningChartRepositoryInterface;
use Modules\VehicleRental\Application\Repositories\VehicleRentalSettingRepositoryInterface;
use Modules\VehicleRental\Application\Repositories\VehicleRentalStatusHistoryRepositoryInterface;
use Modules\VehicleRental\Application\Repositories\VehicleRentalVehicleRepositoryInterface;
use Modules\VehicleRental\Domain\Constants\VehicleRentalErrorCode;
use Throwable;

final class VehicleRentalManagementService implements VehicleRentalManagementServiceInterface
{
    public function __construct(
        private readonly VehicleRentalSettingRepositoryInterface $settingRepository,
        private readonly VehicleRentalVehicleRepositoryInterface $vehicleRepository,
        private readonly VehicleRentalAgreementRepositoryInterface $agreementRepository,
        private readonly VehicleRentalAgreementLineRepositoryInterface $agreementLineRepository,
        private readonly VehicleRentalAgreementRateRepositoryInterface $agreementRateRepository,
        private readonly VehicleRentalRateRuleRepositoryInterface $rateRuleRepository,
        private readonly VehicleRentalRunningChartRepositoryInterface $runningChartRepository,
        private readonly VehicleRentalRunningChartLineRepositoryInterface $runningChartLineRepository,
        private readonly VehicleRentalExtraChargeRepositoryInterface $extraChargeRepository,
        private readonly VehicleRentalReplacementRepositoryInterface $replacementRepository,
        private readonly VehicleRentalBreakdownRepositoryInterface $breakdownRepository,
        private readonly VehicleRentalProviderPayableRepositoryInterface $providerPayableRepository,
        private readonly VehicleRentalStatusHistoryRepositoryInterface $statusHistoryRepository,
        private readonly VehicleRentalApprovalHistoryRepositoryInterface $approvalHistoryRepository,
        private readonly VehicleRentalMetadataDefinitionRepositoryInterface $metadataDefinitionRepository,
        private readonly VehicleRentalMetadataValueRepositoryInterface $metadataValueRepository,
        private readonly TaxCalculationServiceInterface $taxCalculationService,
    ) {}

    public function listAgreements(int $tenantId, ?string $agreementRole = null): Result
    {
        try {
            $criteria = ['tenant_id' => $tenantId];
            if ($agreementRole !== null && $agreementRole !== '') {
                $criteria['agreement_role'] = $agreementRole;
            }

            return Result::success($this->normalizeRecords($this->agreementRepository->list($criteria)));
        } catch (Throwable $exception) {
            return $this->failure($exception);
        }
    }

    public function listRentalVehicles(int $tenantId): Result
    {
        try {
            return Result::success($this->normalizeRecords($this->vehicleRepository->list([
                'tenant_id' => $tenantId,
                'is_active' => true,
            ])));
        } catch (Throwable $exception) {
            return $this->failure($exception);
        }
    }

    public function getAgreement(int $agreementId): Result
    {
        try {
            $agreement = $this->agreementRepository->findById($agreementId);
            if (! $agreement instanceof DataRecord) {
                return Result::failure(new Error(VehicleRentalErrorCode::NOT_FOUND, 'Agreement not found.'));
            }

            return Result::success($this->buildAgreementAggregate($agreement));
        } catch (Throwable $exception) {
            return $this->failure($exception);
        }
    }

    public function upsertAgreementAggregate(?int $id, array $payload): Result
    {
        try {
            return $this->agreementRepository->transaction(function () use ($id, $payload): Result {
                $agreementPayload = $this->extractAgreementPayload($payload);
                $agreement = $id === null
                    ? $this->agreementRepository->create($this->withDefaultRowVersion($agreementPayload))
                    : $this->agreementRepository->update($id, $agreementPayload);

                $agreementId = (int) $agreement->id();
                $tenantId = (int) $agreement->get('tenant_id', 0);
                $organizationUnitId = $agreement->get('organization_unit_id');

                if (is_array($payload['lines'] ?? null)) {
                    $result = $this->syncAgreementLinesInternal(
                        $agreementId,
                        $tenantId,
                        $organizationUnitId,
                        $payload['lines'],
                    );
                    if ($result->isFailure()) {
                        return $result;
                    }
                }

                if (is_array($payload['rates'] ?? null)) {
                    $result = $this->syncAgreementRatesInternal(
                        $agreementId,
                        $tenantId,
                        $organizationUnitId,
                        $payload['rates'],
                    );
                    if ($result->isFailure()) {
                        return $result;
                    }
                }

                if (is_array($payload['rate_rules'] ?? null)) {
                    $result = $this->syncRateRulesInternal(
                        $agreementId,
                        $tenantId,
                        $organizationUnitId,
                        $payload['rate_rules'],
                    );
                    if ($result->isFailure()) {
                        return $result;
                    }
                }

                if (is_array($payload['metadata'] ?? null)) {
                    $this->syncMetadataValues(
                        'agreement',
                        $agreementId,
                        $tenantId,
                        $organizationUnitId !== null ? (int) $organizationUnitId : null,
                        $payload['metadata'],
                    );
                }

                $this->recalculateAgreementTotals($agreementId, $tenantId);

                $reloaded = $this->agreementRepository->findById($agreementId);
                if (! $reloaded instanceof DataRecord) {
                    return Result::failure(new Error(VehicleRentalErrorCode::NOT_FOUND, 'Agreement not found.'));
                }

                return Result::success($this->buildAgreementAggregate($reloaded));
            });
        } catch (Throwable $exception) {
            return $this->failure($exception);
        }
    }

    public function syncAgreementLines(int $agreementId, array $payload): Result
    {
        try {
            $agreement = $this->agreementRepository->findById($agreementId);
            if (! $agreement instanceof DataRecord) {
                return Result::failure(new Error(VehicleRentalErrorCode::NOT_FOUND, 'Agreement not found.'));
            }

            $result = $this->syncAgreementLinesInternal(
                $agreementId,
                (int) $agreement->get('tenant_id', 0),
                $agreement->get('organization_unit_id'),
                is_array($payload['lines'] ?? null) ? $payload['lines'] : [],
            );
            if ($result->isFailure()) {
                return $result;
            }

            $this->recalculateAgreementTotals($agreementId, (int) $agreement->get('tenant_id', 0));

            return Result::success(['agreement_id' => $agreementId, 'synced' => true]);
        } catch (Throwable $exception) {
            return $this->failure($exception);
        }
    }

    public function syncAgreementRates(int $agreementId, array $payload): Result
    {
        try {
            $agreement = $this->agreementRepository->findById($agreementId);
            if (! $agreement instanceof DataRecord) {
                return Result::failure(new Error(VehicleRentalErrorCode::NOT_FOUND, 'Agreement not found.'));
            }

            $result = $this->syncAgreementRatesInternal(
                $agreementId,
                (int) $agreement->get('tenant_id', 0),
                $agreement->get('organization_unit_id'),
                is_array($payload['rates'] ?? null) ? $payload['rates'] : [],
            );
            if ($result->isFailure()) {
                return $result;
            }

            return Result::success(['agreement_id' => $agreementId, 'synced' => true]);
        } catch (Throwable $exception) {
            return $this->failure($exception);
        }
    }

    public function syncRateRules(int $agreementId, array $payload): Result
    {
        try {
            $agreement = $this->agreementRepository->findById($agreementId);
            if (! $agreement instanceof DataRecord) {
                return Result::failure(new Error(VehicleRentalErrorCode::NOT_FOUND, 'Agreement not found.'));
            }

            $result = $this->syncRateRulesInternal(
                $agreementId,
                (int) $agreement->get('tenant_id', 0),
                $agreement->get('organization_unit_id'),
                is_array($payload['rate_rules'] ?? null) ? $payload['rate_rules'] : [],
            );
            if ($result->isFailure()) {
                return $result;
            }

            return Result::success(['agreement_id' => $agreementId, 'synced' => true]);
        } catch (Throwable $exception) {
            return $this->failure($exception);
        }
    }

    public function listRunningCharts(int $tenantId, ?int $agreementId = null): Result
    {
        try {
            $criteria = ['tenant_id' => $tenantId];
            if ($agreementId !== null) {
                $criteria['agreement_id'] = $agreementId;
            }

            return Result::success($this->normalizeRecords($this->runningChartRepository->list($criteria)));
        } catch (Throwable $exception) {
            return $this->failure($exception);
        }
    }

    public function getRunningChart(int $runningChartId): Result
    {
        try {
            $runningChart = $this->runningChartRepository->findById($runningChartId);
            if (! $runningChart instanceof DataRecord) {
                return Result::failure(new Error(VehicleRentalErrorCode::NOT_FOUND, 'Running chart not found.'));
            }

            return Result::success($this->buildRunningChartAggregate($runningChart));
        } catch (Throwable $exception) {
            return $this->failure($exception);
        }
    }

    public function upsertRunningChartAggregate(?int $id, array $payload): Result
    {
        try {
            return $this->runningChartRepository->transaction(function () use ($id, $payload): Result {
                $runningChartPayload = $this->extractRunningChartPayload($payload);
                $runningChart = $id === null
                    ? $this->runningChartRepository->create($this->withDefaultRowVersion($runningChartPayload))
                    : $this->runningChartRepository->update($id, $runningChartPayload);

                $runningChartId = (int) $runningChart->id();
                $tenantId = (int) $runningChart->get('tenant_id', 0);
                $organizationUnitId = $runningChart->get('organization_unit_id');
                $agreementId = (int) $runningChart->get('agreement_id', 0);

                if (is_array($payload['lines'] ?? null)) {
                    $result = $this->syncRunningChartLinesInternal(
                        $runningChartId,
                        $agreementId,
                        $tenantId,
                        $organizationUnitId,
                        $runningChart->get('rental_vehicle_id'),
                        $payload['lines'],
                    );
                    if ($result->isFailure()) {
                        return $result;
                    }
                }

                if (is_array($payload['extra_charges'] ?? null)) {
                    $result = $this->syncExtraChargesInternal(
                        $agreementId,
                        $runningChartId,
                        $tenantId,
                        $organizationUnitId,
                        $payload['extra_charges'],
                    );
                    if ($result->isFailure()) {
                        return $result;
                    }
                }

                if (is_array($payload['metadata'] ?? null)) {
                    $this->syncMetadataValues(
                        'running_chart',
                        $runningChartId,
                        $tenantId,
                        $organizationUnitId !== null ? (int) $organizationUnitId : null,
                        $payload['metadata'],
                    );
                }

                $this->recalculateRunningChartTotals($runningChartId, $tenantId);
                $this->recalculateAgreementTotals($agreementId, $tenantId);

                $reloaded = $this->runningChartRepository->findById($runningChartId);
                if (! $reloaded instanceof DataRecord) {
                    return Result::failure(new Error(VehicleRentalErrorCode::NOT_FOUND, 'Running chart not found.'));
                }

                return Result::success($this->buildRunningChartAggregate($reloaded));
            });
        } catch (Throwable $exception) {
            return $this->failure($exception);
        }
    }

    public function syncRunningChartLines(int $runningChartId, array $payload): Result
    {
        try {
            $runningChart = $this->runningChartRepository->findById($runningChartId);
            if (! $runningChart instanceof DataRecord) {
                return Result::failure(new Error(VehicleRentalErrorCode::NOT_FOUND, 'Running chart not found.'));
            }

            $result = $this->syncRunningChartLinesInternal(
                $runningChartId,
                (int) $runningChart->get('agreement_id', 0),
                (int) $runningChart->get('tenant_id', 0),
                $runningChart->get('organization_unit_id'),
                $runningChart->get('rental_vehicle_id'),
                is_array($payload['lines'] ?? null) ? $payload['lines'] : [],
            );
            if ($result->isFailure()) {
                return $result;
            }

            $this->recalculateRunningChartTotals($runningChartId, (int) $runningChart->get('tenant_id', 0));

            return Result::success(['running_chart_id' => $runningChartId, 'synced' => true]);
        } catch (Throwable $exception) {
            return $this->failure($exception);
        }
    }

    public function syncExtraCharges(int $agreementId, array $payload): Result
    {
        try {
            $agreement = $this->agreementRepository->findById($agreementId);
            if (! $agreement instanceof DataRecord) {
                return Result::failure(new Error(VehicleRentalErrorCode::NOT_FOUND, 'Agreement not found.'));
            }

            $result = $this->syncExtraChargesInternal(
                $agreementId,
                isset($payload['running_chart_id']) ? (int) $payload['running_chart_id'] : null,
                (int) $agreement->get('tenant_id', 0),
                $agreement->get('organization_unit_id'),
                is_array($payload['extra_charges'] ?? null) ? $payload['extra_charges'] : [],
            );
            if ($result->isFailure()) {
                return $result;
            }

            $this->recalculateAgreementTotals($agreementId, (int) $agreement->get('tenant_id', 0));

            return Result::success(['agreement_id' => $agreementId, 'synced' => true]);
        } catch (Throwable $exception) {
            return $this->failure($exception);
        }
    }

    public function upsertReplacement(?int $id, array $payload): Result
    {
        try {
            $recordPayload = $this->withDefaultRowVersion($payload);
            $replacement = $id === null
                ? $this->replacementRepository->create($recordPayload)
                : $this->replacementRepository->update($id, $payload);

            if (is_array($payload['metadata'] ?? null)) {
                $this->syncMetadataValues(
                    'replacement',
                    (int) $replacement->id(),
                    (int) $replacement->get('tenant_id', 0),
                    $replacement->get('organization_unit_id') !== null
                        ? (int) $replacement->get('organization_unit_id')
                        : null,
                    $payload['metadata'],
                );
            }

            return Result::success($this->normalizeRecord($replacement));
        } catch (Throwable $exception) {
            return $this->failure($exception);
        }
    }

    public function upsertBreakdown(?int $id, array $payload): Result
    {
        try {
            $recordPayload = $this->withDefaultRowVersion($payload);
            $breakdown = $id === null
                ? $this->breakdownRepository->create($recordPayload)
                : $this->breakdownRepository->update($id, $payload);

            if (is_array($payload['metadata'] ?? null)) {
                $this->syncMetadataValues(
                    'breakdown',
                    (int) $breakdown->id(),
                    (int) $breakdown->get('tenant_id', 0),
                    $breakdown->get('organization_unit_id') !== null
                        ? (int) $breakdown->get('organization_unit_id')
                        : null,
                    $payload['metadata'],
                );
            }

            return Result::success($this->normalizeRecord($breakdown));
        } catch (Throwable $exception) {
            return $this->failure($exception);
        }
    }

    public function getStatusHistory(string $entityType, int $entityId, int $tenantId): Result
    {
        try {
            return Result::success($this->normalizeRecords($this->statusHistoryRepository->list([
                'tenant_id' => $tenantId,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
            ])));
        } catch (Throwable $exception) {
            return $this->failure($exception);
        }
    }

    public function getSettings(int $tenantId, ?int $organizationUnitId): Result
    {
        try {
            $settings = $this->resolveSettings($tenantId, $organizationUnitId);

            return Result::success($settings instanceof DataRecord ? $this->normalizeRecord($settings) : null);
        } catch (Throwable $exception) {
            return $this->failure($exception);
        }
    }

    public function upsertSettings(array $payload): Result
    {
        try {
            $tenantId = (int) ($payload['tenant_id'] ?? 0);
            $organizationUnitId = isset($payload['organization_unit_id'])
                ? (int) $payload['organization_unit_id']
                : null;
            $settings = $this->resolveSettings($tenantId, $organizationUnitId);
            $settingsPayload = $this->withDefaultRowVersion($payload);

            $record = $settings instanceof DataRecord
                ? $this->settingRepository->update((int) $settings->id(), $payload)
                : $this->settingRepository->create($settingsPayload);

            return Result::success($this->normalizeRecord($record));
        } catch (Throwable $exception) {
            return $this->failure($exception);
        }
    }

    public function initializeSettings(array $payload): Result
    {
        $defaults = [
            'allow_external_provider_vehicle' => true,
            'allow_replacement_vehicle' => true,
            'allow_without_driver' => true,
            'allow_with_driver' => true,
            'default_daily_hours' => 8,
            'default_monthly_km_limit' => 0,
            'default_extra_km_rate' => 0,
            'default_extra_hour_rate' => 0,
            'default_night_shift_rate' => 0,
            'default_weekend_rate_multiplier' => 1,
            'default_holiday_rate_multiplier' => 1,
            'default_double_rate_multiplier' => 2,
            'is_active' => true,
        ];

        return $this->upsertSettings($defaults + $payload);
    }

    public function getVehicleAvailability(
        int $tenantId,
        int $rentalVehicleId,
        string $startDateTime,
        ?string $endDateTime,
        ?int $excludeAgreementId = null,
    ): Result {
        try {
            $agreements = $this->agreementRepository->list([
                'tenant_id' => $tenantId,
                'rental_vehicle_id' => $rentalVehicleId,
            ]);

            $requestedStart = strtotime($startDateTime) ?: 0;
            $requestedEnd = $endDateTime !== null
                ? (strtotime($endDateTime) ?: \PHP_INT_MAX)
                : \PHP_INT_MAX;
            $conflicts = [];

            foreach ($agreements as $agreement) {
                if (! $agreement instanceof DataRecord) {
                    continue;
                }

                if ($excludeAgreementId !== null && (int) $agreement->id() === $excludeAgreementId) {
                    continue;
                }

                $status = strtolower((string) $agreement->get('status', 'draft'));
                if (in_array($status, ['cancelled', 'reversed'], true)) {
                    continue;
                }

                $existingStart = strtotime((string) $agreement->get('start_datetime')) ?: 0;
                $existingEndRaw = $agreement->get('end_datetime');
                $existingEnd = $existingEndRaw !== null
                    ? (strtotime((string) $existingEndRaw) ?: \PHP_INT_MAX)
                    : \PHP_INT_MAX;

                if ($requestedStart <= $existingEnd && $requestedEnd >= $existingStart) {
                    $conflicts[] = $this->normalizeRecord($agreement);
                }
            }

            return Result::success([
                'available' => $conflicts === [],
                'rental_vehicle_id' => $rentalVehicleId,
                'conflicts' => $conflicts,
            ]);
        } catch (Throwable $exception) {
            return $this->failure($exception);
        }
    }

    public function previewBilling(int $agreementId, array $payload): Result
    {
        try {
            $agreement = $this->agreementRepository->findById($agreementId);
            if (! $agreement instanceof DataRecord) {
                return Result::failure(new Error(VehicleRentalErrorCode::NOT_FOUND, 'Agreement not found.'));
            }

            $agreementLines = $this->filterRecords(
                $this->agreementLineRepository->list(['agreement_id' => $agreementId]),
            );
            $rate = $this->resolveActiveRate($agreementId, $payload['effective_at'] ?? null);
            $rules = $this->filterRecords($this->rateRuleRepository->list(['agreement_id' => $agreementId]));
            $extraCharges = $this->filterRecords($this->extraChargeRepository->list(['agreement_id' => $agreementId]));
            $runningChartLines = [];

            if (isset($payload['running_chart_id'])) {
                $runningChartLines = $this->filterRecords($this->runningChartLineRepository->list([
                    'running_chart_id' => (int) $payload['running_chart_id'],
                ]));
            } elseif (is_array($payload['running_chart_lines'] ?? null)) {
                $runningChartLines = array_map(
                    static fn (array $line): array => $line,
                    $payload['running_chart_lines'],
                );
            }

            $usage = $this->summarizeUsage($runningChartLines);
            $baseQuantity = (float) ($payload['base_quantity'] ?? $usage['billable_quantity']);
            $baseRate = $rate instanceof DataRecord ? (float) $rate->get('base_rate', 0) : 0.0;
            $baseCharge = $baseQuantity * $baseRate;
            $ruleCharges = $this->calculateRuleCharges($rules, $usage);
            $lineCharges = 0.0;
            $providerLineCost = 0.0;

            foreach ($agreementLines as $line) {
                $lineCharge = (float) $line->get('line_total', 0);
                if ((bool) $line->get('is_billable', true)) {
                    $lineCharges += $lineCharge;
                }
                if ((bool) $line->get('is_payable', false)) {
                    $providerLineCost += $lineCharge;
                }
            }

            $extraCustomerCharges = 0.0;
            $extraProviderCharges = 0.0;
            foreach ($extraCharges as $extraCharge) {
                $amount = (float) $extraCharge->get('total_amount', 0);
                if ((string) $extraCharge->get('charge_scope', 'customer') === 'provider') {
                    $extraProviderCharges += $amount;

                    continue;
                }

                $extraCustomerCharges += $amount;
            }

            $customerSubtotal = $baseCharge + $lineCharges + $extraCustomerCharges + $ruleCharges['customer'];
            $providerSubtotal = $providerLineCost + $extraProviderCharges + $ruleCharges['provider'];

            return Result::success([
                'agreement_id' => $agreementId,
                'usage' => $usage,
                'rate' => $rate instanceof DataRecord ? $this->normalizeRecord($rate) : null,
                'customer_subtotal' => round($customerSubtotal, 4),
                'provider_subtotal' => round($providerSubtotal, 4),
                'base_charge' => round($baseCharge, 4),
                'line_charges' => round($lineCharges, 4),
                'extra_customer_charges' => round($extraCustomerCharges, 4),
                'extra_provider_charges' => round($extraProviderCharges, 4),
                'rule_charges' => [
                    'customer' => round($ruleCharges['customer'], 4),
                    'provider' => round($ruleCharges['provider'], 4),
                ],
            ]);
        } catch (Throwable $exception) {
            return $this->failure($exception);
        }
    }

    public function listProviderPayables(int $tenantId, ?int $agreementId = null): Result
    {
        try {
            $criteria = ['tenant_id' => $tenantId];
            if ($agreementId !== null) {
                $criteria['agreement_id'] = $agreementId;
            }

            return Result::success($this->normalizeRecords($this->providerPayableRepository->list($criteria)));
        } catch (Throwable $exception) {
            return $this->failure($exception);
        }
    }

    private function buildAgreementAggregate(DataRecord $agreement): array
    {
        $agreementId = (int) $agreement->id();
        $tenantId = (int) $agreement->get('tenant_id', 0);

        return [
            ...$this->normalizeRecord($agreement),
            'lines' => $this->normalizeRecords($this->agreementLineRepository->list(['agreement_id' => $agreementId])),
            'rates' => $this->normalizeRecords($this->agreementRateRepository->list(['agreement_id' => $agreementId])),
            'rate_rules' => $this->normalizeRecords($this->rateRuleRepository->list(['agreement_id' => $agreementId])),
            'extra_charges' => $this->normalizeRecords(
                $this->extraChargeRepository->list(['agreement_id' => $agreementId]),
            ),
            'running_charts' => $this->normalizeRecords(
                $this->runningChartRepository->list(['agreement_id' => $agreementId]),
            ),
            'provider_payables' => $this->normalizeRecords(
                $this->providerPayableRepository->list(['agreement_id' => $agreementId]),
            ),
            'status_history' => $this->normalizeRecords($this->statusHistoryRepository->list([
                'tenant_id' => $tenantId,
                'entity_type' => 'agreement',
                'entity_id' => $agreementId,
            ])),
            'approval_history' => $this->normalizeRecords($this->approvalHistoryRepository->list([
                'tenant_id' => $tenantId,
                'entity_type' => 'agreement',
                'entity_id' => $agreementId,
            ])),
            'metadata' => $this->loadMetadataValues('agreement', $agreementId, $tenantId),
        ];
    }

    private function buildRunningChartAggregate(DataRecord $runningChart): array
    {
        $runningChartId = (int) $runningChart->id();
        $tenantId = (int) $runningChart->get('tenant_id', 0);

        return [
            ...$this->normalizeRecord($runningChart),
            'lines' => $this->normalizeRecords(
                $this->runningChartLineRepository->list(['running_chart_id' => $runningChartId]),
            ),
            'extra_charges' => $this->normalizeRecords(
                $this->extraChargeRepository->list(['running_chart_id' => $runningChartId]),
            ),
            'status_history' => $this->normalizeRecords($this->statusHistoryRepository->list([
                'tenant_id' => $tenantId,
                'entity_type' => 'running_chart',
                'entity_id' => $runningChartId,
            ])),
            'approval_history' => $this->normalizeRecords($this->approvalHistoryRepository->list([
                'tenant_id' => $tenantId,
                'entity_type' => 'running_chart',
                'entity_id' => $runningChartId,
            ])),
            'metadata' => $this->loadMetadataValues('running_chart', $runningChartId, $tenantId),
        ];
    }

    private function syncAgreementLinesInternal(
        int $agreementId,
        int $tenantId,
        mixed $organizationUnitId,
        array $lines,
    ): Result {
        $nextLineNumber = $this->nextLineNumber(
            $this->agreementLineRepository->list(['agreement_id' => $agreementId]),
            'line_number',
        );

        foreach ($lines as $linePayload) {
            if (! is_array($linePayload)) {
                continue;
            }

            $lineId = isset($linePayload['id']) ? (int) $linePayload['id'] : null;
            if ((bool) ($linePayload['_delete'] ?? false) && $lineId !== null) {
                $this->agreementLineRepository->delete($lineId);

                continue;
            }

            $quantity = round((float) ($linePayload['quantity'] ?? 0), 4);
            $unitRate = round((float) ($linePayload['unit_rate'] ?? 0), 4);
            $grossAmount = round($quantity * $unitRate, 4);
            $discountAmount = 0.0;
            $taxAmount = $this->resolveTaxAmount(
                $tenantId,
                isset($linePayload['tax_group_id']) ? (int) $linePayload['tax_group_id'] : null,
                max(0.0, $grossAmount - $discountAmount),
                $linePayload['posting_date'] ?? null,
            );

            $upsert = $this->withDefaultRowVersion([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $organizationUnitId,
                'agreement_id' => $agreementId,
                'line_number' => $linePayload['line_number'] ?? $nextLineNumber++,
                ...$linePayload,
                'quantity' => $quantity,
                'unit_rate' => $unitRate,
                'discount_amount' => $discountAmount,
                'tax_amount' => $taxAmount,
                'line_total' => round($grossAmount - $discountAmount + $taxAmount, 4),
            ]);

            if ($lineId === null) {
                $this->agreementLineRepository->create($upsert);

                continue;
            }

            $this->agreementLineRepository->update($lineId, $upsert);
        }

        return Result::success(['synced' => true]);
    }

    private function syncAgreementRatesInternal(
        int $agreementId,
        int $tenantId,
        mixed $organizationUnitId,
        array $rates,
    ): Result {
        foreach ($rates as $ratePayload) {
            if (! is_array($ratePayload)) {
                continue;
            }

            $rateId = isset($ratePayload['id']) ? (int) $ratePayload['id'] : null;
            if ((bool) ($ratePayload['_delete'] ?? false) && $rateId !== null) {
                $this->agreementRateRepository->delete($rateId);

                continue;
            }

            $upsert = $this->withDefaultRowVersion([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $organizationUnitId,
                'agreement_id' => $agreementId,
                ...$ratePayload,
            ]);

            if ($rateId === null) {
                $this->agreementRateRepository->create($upsert);

                continue;
            }

            $this->agreementRateRepository->update($rateId, $upsert);
        }

        return Result::success(['synced' => true]);
    }

    private function syncRateRulesInternal(
        int $agreementId,
        int $tenantId,
        mixed $organizationUnitId,
        array $rules,
    ): Result {
        foreach ($rules as $rulePayload) {
            if (! is_array($rulePayload)) {
                continue;
            }

            $ruleId = isset($rulePayload['id']) ? (int) $rulePayload['id'] : null;
            if ((bool) ($rulePayload['_delete'] ?? false) && $ruleId !== null) {
                $this->rateRuleRepository->delete($ruleId);

                continue;
            }

            $upsert = $this->withDefaultRowVersion([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $organizationUnitId,
                'agreement_id' => $agreementId,
                ...$rulePayload,
            ]);

            if ($ruleId === null) {
                $this->rateRuleRepository->create($upsert);

                continue;
            }

            $this->rateRuleRepository->update($ruleId, $upsert);
        }

        return Result::success(['synced' => true]);
    }

    private function syncRunningChartLinesInternal(
        int $runningChartId,
        int $agreementId,
        int $tenantId,
        mixed $organizationUnitId,
        mixed $rentalVehicleId,
        array $lines,
    ): Result {
        $nextLineNumber = $this->nextLineNumber(
            $this->runningChartLineRepository->list(['running_chart_id' => $runningChartId]),
            'line_number',
        );

        foreach ($lines as $linePayload) {
            if (! is_array($linePayload)) {
                continue;
            }

            $lineId = isset($linePayload['id']) ? (int) $linePayload['id'] : null;
            if ((bool) ($linePayload['_delete'] ?? false) && $lineId !== null) {
                $this->runningChartLineRepository->delete($lineId);

                continue;
            }

            $calculated = $this->calculateRunningChartLineAmounts($agreementId, $linePayload);
            $upsert = $this->withDefaultRowVersion([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $organizationUnitId,
                'running_chart_id' => $runningChartId,
                'rental_vehicle_id' => $linePayload['rental_vehicle_id'] ?? $rentalVehicleId,
                'line_number' => $linePayload['line_number'] ?? $nextLineNumber++,
                ...$linePayload,
                ...$calculated,
            ]);

            if ($lineId === null) {
                $this->runningChartLineRepository->create($upsert);

                continue;
            }

            $this->runningChartLineRepository->update($lineId, $upsert);
        }

        return Result::success(['synced' => true]);
    }

    private function syncExtraChargesInternal(
        int $agreementId,
        ?int $runningChartId,
        int $tenantId,
        mixed $organizationUnitId,
        array $extraCharges,
    ): Result {
        foreach ($extraCharges as $chargePayload) {
            if (! is_array($chargePayload)) {
                continue;
            }

            $chargeId = isset($chargePayload['id']) ? (int) $chargePayload['id'] : null;
            if ((bool) ($chargePayload['_delete'] ?? false) && $chargeId !== null) {
                $this->extraChargeRepository->delete($chargeId);

                continue;
            }

            $quantity = round((float) ($chargePayload['quantity'] ?? 0), 4);
            $unitAmount = round((float) ($chargePayload['unit_amount'] ?? 0), 4);
            $grossAmount = round($quantity * $unitAmount, 4);
            $discountAmount = 0.0;
            $taxAmount = $this->resolveTaxAmount(
                $tenantId,
                isset($chargePayload['tax_group_id']) ? (int) $chargePayload['tax_group_id'] : null,
                max(0.0, $grossAmount - $discountAmount),
                $chargePayload['charge_date'] ?? null,
            );
            $upsert = $this->withDefaultRowVersion([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $organizationUnitId,
                'agreement_id' => $agreementId,
                'running_chart_id' => $chargePayload['running_chart_id'] ?? $runningChartId,
                ...$chargePayload,
                'quantity' => $quantity,
                'unit_amount' => $unitAmount,
                'discount_amount' => $discountAmount,
                'tax_amount' => $taxAmount,
                'total_amount' => round($grossAmount - $discountAmount + $taxAmount, 4),
            ]);

            if ($chargeId === null) {
                $this->extraChargeRepository->create($upsert);

                continue;
            }

            $this->extraChargeRepository->update($chargeId, $upsert);
        }

        return Result::success(['synced' => true]);
    }

    private function extractAgreementPayload(array $payload): array
    {
        return $this->withDefaultRowVersion(array_diff_key($payload, array_flip([
            'lines',
            'rates',
            'rate_rules',
            'metadata',
            'extra_charges',
        ])));
    }

    private function extractRunningChartPayload(array $payload): array
    {
        return $this->withDefaultRowVersion(array_diff_key($payload, array_flip([
            'lines',
            'extra_charges',
            'metadata',
        ])));
    }

    private function recalculateAgreementTotals(int $agreementId, int $tenantId): void
    {
        $agreement = $this->agreementRepository->findById($agreementId);
        if (! $agreement instanceof DataRecord) {
            return;
        }

        $agreementLines = $this->filterRecords($this->agreementLineRepository->list(['agreement_id' => $agreementId]));
        $extraCharges = $this->filterRecords($this->extraChargeRepository->list(['agreement_id' => $agreementId]));
        $providerPayables = $this->filterRecords(
            $this->providerPayableRepository->list(['agreement_id' => $agreementId]),
        );
        $runningCharts = $this->filterRecords($this->runningChartRepository->list(['agreement_id' => $agreementId]));
        $paymentLinks = [];
        $estimatedSubtotal = 0.0;
        $estimatedDiscountTotal = 0.0;
        $estimatedTaxTotal = 0.0;
        $invoicedTotal = 0.0;
        $paidTotal = 0.0;
        $providerPayableTotal = 0.0;
        $providerPaidTotal = 0.0;

        foreach ($agreementLines as $line) {
            if ((bool) $line->get('is_billable', true)) {
                $estimatedSubtotal += ((float) $line->get('quantity', 0) * (float) $line->get('unit_rate', 0));
                $estimatedDiscountTotal += (float) $line->get('discount_amount', 0);
                $estimatedTaxTotal += (float) $line->get('tax_amount', 0);
            }
        }

        foreach ($extraCharges as $extraCharge) {
            if ((string) $extraCharge->get('charge_scope', 'customer') === 'provider') {
                continue;
            }
            $estimatedSubtotal += (float) $extraCharge->get('total_amount', 0);
            $estimatedDiscountTotal += (float) $extraCharge->get('discount_amount', 0);
            $estimatedTaxTotal += (float) $extraCharge->get('tax_amount', 0);
        }

        foreach ($runningCharts as $runningChart) {
            $invoicedTotal += (float) $runningChart->get('customer_bill_total', 0);
        }

        foreach ($providerPayables as $payable) {
            $providerPayableTotal += (float) $payable->get('grand_total', 0);
            $providerPaidTotal += (float) $payable->get('paid_total', 0);
        }

        $estimatedGrandTotal = $estimatedSubtotal - $estimatedDiscountTotal + $estimatedTaxTotal;
        $outstandingBalance = $invoicedTotal - $paidTotal;

        $this->agreementRepository->update($agreementId, [
            'estimated_subtotal' => round($estimatedSubtotal, 4),
            'estimated_discount_total' => round($estimatedDiscountTotal, 4),
            'estimated_tax_total' => round($estimatedTaxTotal, 4),
            'estimated_grand_total' => round($estimatedGrandTotal, 4),
            'invoiced_total' => round($invoicedTotal, 4),
            'paid_total' => round($paidTotal, 4),
            'provider_payable_total' => round($providerPayableTotal, 4),
            'provider_paid_total' => round($providerPaidTotal, 4),
            'outstanding_balance' => round($outstandingBalance, 4),
        ]);

        $this->statusHistoryRepository->create([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $agreement->get('organization_unit_id'),
            'entity_type' => 'agreement',
            'entity_id' => $agreementId,
            'from_status' => $agreement->get('status'),
            'to_status' => $agreement->get('status'),
            'action_name' => 'recalculate',
            'reason' => 'agreement_totals_recalculated',
            'changed_by' => null,
            'changed_at' => now()->toDateTimeString(),
        ]);
    }

    private function recalculateRunningChartTotals(int $runningChartId, int $tenantId): void
    {
        $runningChart = $this->runningChartRepository->findById($runningChartId);
        if (! $runningChart instanceof DataRecord) {
            return;
        }

        $lines = $this->filterRecords($this->runningChartLineRepository->list(['running_chart_id' => $runningChartId]));
        $extraCharges = $this->filterRecords(
            $this->extraChargeRepository->list(['running_chart_id' => $runningChartId]),
        );
        $totals = [
            'total_hours' => 0.0,
            'allowed_hours' => 0.0,
            'extra_hours' => 0.0,
            'total_km' => 0.0,
            'allowed_km' => 0.0,
            'extra_km' => 0.0,
            'overtime_hours' => 0.0,
            'night_shift_hours' => 0.0,
            'weekend_hours' => 0.0,
            'holiday_hours' => 0.0,
            'double_rate_hours' => 0.0,
            'day_out_count' => 0,
            'night_out_count' => 0,
            'fuel_total' => 0.0,
            'toll_total' => 0.0,
            'parking_total' => 0.0,
            'other_expense_total' => 0.0,
            'customer_bill_total' => 0.0,
            'provider_cost_total' => 0.0,
        ];

        foreach ($lines as $line) {
            foreach (array_keys($totals) as $field) {
                $totals[$field] += (float) $line->get($field, 0);
            }
        }

        foreach ($extraCharges as $extraCharge) {
            $amount = (float) $extraCharge->get('total_amount', 0);
            if ((string) $extraCharge->get('charge_scope', 'customer') === 'provider') {
                $totals['provider_cost_total'] += $amount;

                continue;
            }

            $totals['customer_bill_total'] += $amount;
        }

        foreach ($totals as $field => $value) {
            $totals[$field] = is_int($value) ? $value : round($value, 4);
        }

        $this->runningChartRepository->update($runningChartId, $totals);

        $this->statusHistoryRepository->create([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $runningChart->get('organization_unit_id'),
            'entity_type' => 'running_chart',
            'entity_id' => $runningChartId,
            'from_status' => $runningChart->get('status'),
            'to_status' => $runningChart->get('status'),
            'action_name' => 'recalculate',
            'reason' => 'running_chart_totals_recalculated',
            'changed_by' => null,
            'changed_at' => now()->toDateTimeString(),
        ]);
    }

    private function calculateRunningChartLineAmounts(int $agreementId, array $linePayload): array
    {
        $agreement = $this->agreementRepository->findById($agreementId);
        $rate = $this->resolveActiveRate($agreementId, $linePayload['usage_date'] ?? null);
        $allowedHours = (float) ($linePayload['allowed_hours']
            ?? ($agreement instanceof DataRecord ? $agreement->get('allowed_daily_hours', 0) : 0));
        $allowedKm = (float) ($linePayload['allowed_km']
            ?? ($agreement instanceof DataRecord ? $agreement->get('allowed_daily_km', 0) : 0));
        $totalHours = (float) ($linePayload['total_hours'] ?? 0);
        $totalKm = (float) ($linePayload['total_km'] ?? 0);
        $extraHours = max(0.0, $totalHours - $allowedHours);
        $extraKm = max(0.0, $totalKm - $allowedKm);
        $baseRate = $rate instanceof DataRecord ? (float) $rate->get('base_rate', 0) : 0.0;
        $extraHourRate = $rate instanceof DataRecord ? (float) $rate->get('extra_hour_rate', 0) : 0.0;
        $extraKmRate = $rate instanceof DataRecord ? (float) $rate->get('extra_km_rate', 0) : 0.0;
        $nightShiftRate = $rate instanceof DataRecord ? (float) $rate->get('night_shift_rate', 0) : 0.0;
        $driverRate = $rate instanceof DataRecord ? (float) $rate->get('driver_rate', 0) : 0.0;
        $customerCharge = ($totalHours * $baseRate)
            + ($extraHours * $extraHourRate)
            + ($extraKm * $extraKmRate)
            + ((float) ($linePayload['night_shift_hours'] ?? 0) * $nightShiftRate)
            + ($driverRate > 0 ? $driverRate : 0);
        $providerCost = (float) ($linePayload['provider_cost_amount'] ?? 0);

        return [
            'allowed_hours' => round($allowedHours, 4),
            'extra_hours' => round($extraHours, 4),
            'allowed_km' => round($allowedKm, 4),
            'extra_km' => round($extraKm, 4),
            'customer_charge_amount' => round($customerCharge, 4),
            'provider_cost_amount' => round($providerCost, 4),
        ];
    }

    private function resolveSettings(int $tenantId, ?int $organizationUnitId): ?DataRecord
    {
        $records = $this->filterRecords($this->settingRepository->list(['tenant_id' => $tenantId]));

        foreach ($records as $record) {
            $recordOrganizationUnitId = $record->get('organization_unit_id');
            if ($organizationUnitId !== null && (int) $recordOrganizationUnitId === $organizationUnitId) {
                return $record;
            }
        }

        foreach ($records as $record) {
            if ($record->get('organization_unit_id') === null) {
                return $record;
            }
        }

        return null;
    }

    private function resolveActiveRate(int $agreementId, mixed $effectiveAt = null): ?DataRecord
    {
        $rates = $this->filterRecords($this->agreementRateRepository->list(['agreement_id' => $agreementId]));
        if ($rates === []) {
            return null;
        }

        $effectiveTimestamp = $effectiveAt !== null ? (strtotime((string) $effectiveAt) ?: time()) : time();
        $defaultRate = null;

        foreach ($rates as $rate) {
            if ((bool) $rate->get('is_default', false)) {
                $defaultRate = $rate;
            }

            $effectiveFrom = strtotime((string) $rate->get('effective_from')) ?: 0;
            $effectiveToRaw = $rate->get('effective_to');
            $effectiveTo = $effectiveToRaw !== null
                ? (strtotime((string) $effectiveToRaw) ?: \PHP_INT_MAX)
                : \PHP_INT_MAX;

            if ($effectiveTimestamp >= $effectiveFrom && $effectiveTimestamp <= $effectiveTo) {
                return $rate;
            }
        }

        return $defaultRate ?? $rates[0];
    }

    private function calculateRuleCharges(array $rules, array $usage): array
    {
        $charges = ['customer' => 0.0, 'provider' => 0.0];

        foreach ($rules as $rule) {
            if ($rule instanceof DataRecord) {
                $scope = (string) $rule->get('charge_scope', 'customer');
                $usageValue = $this->resolveUsageValue((string) $rule->get('basis_type', ''), $usage);
                $threshold = (float) $rule->get('threshold_quantity', 0);
                if (! $this->ruleMatches((string) $rule->get('comparator', 'gt'), $usageValue, $threshold)) {
                    continue;
                }

                $charge = ((float) $rule->get('rate_value', 0) * (float) $rule->get('rate_multiplier', 1))
                    + (float) $rule->get('fixed_amount', 0);
                $maximum = (float) $rule->get('maximum_charge_amount', 0);
                if ($maximum > 0) {
                    $charge = min($charge, $maximum);
                }

                $charges[$scope === 'provider' ? 'provider' : 'customer'] += $charge;
            }
        }

        return $charges;
    }

    private function summarizeUsage(array $runningChartLines): array
    {
        $usage = [
            'total_hours' => 0.0,
            'total_km' => 0.0,
            'extra_hours' => 0.0,
            'extra_km' => 0.0,
            'overtime_hours' => 0.0,
            'night_shift_hours' => 0.0,
            'weekend_hours' => 0.0,
            'holiday_hours' => 0.0,
            'double_rate_hours' => 0.0,
            'day_out_count' => 0.0,
            'night_out_count' => 0.0,
            'billable_quantity' => 0.0,
        ];

        foreach ($runningChartLines as $line) {
            if ($line instanceof DataRecord) {
                foreach (array_keys($usage) as $field) {
                    if ($field === 'billable_quantity') {
                        continue;
                    }
                    $usage[$field] += (float) $line->get($field, 0);
                }

                continue;
            }

            if (! is_array($line)) {
                continue;
            }

            foreach (array_keys($usage) as $field) {
                if ($field === 'billable_quantity') {
                    continue;
                }
                $usage[$field] += (float) ($line[$field] ?? 0);
            }
        }

        $usage['billable_quantity'] = max($usage['total_hours'], $usage['total_km'], 1.0);

        foreach ($usage as $field => $value) {
            $usage[$field] = round($value, 4);
        }

        return $usage;
    }

    private function resolveUsageValue(string $basisType, array $usage): float
    {
        return (float) ($usage[$basisType] ?? 0);
    }

    private function ruleMatches(string $comparator, float $left, float $right): bool
    {
        return match ($comparator) {
            'gte' => $left >= $right,
            'lt' => $left < $right,
            'lte' => $left <= $right,
            'eq' => abs($left - $right) < 0.0001,
            'neq' => abs($left - $right) >= 0.0001,
            default => $left > $right,
        };
    }

    private function resolveTaxAmount(int $tenantId, ?int $taxGroupId, float $taxableAmount, mixed $postingDate = null): float
    {
        if ($tenantId < 1 || $taxGroupId === null || $taxGroupId < 1 || $taxableAmount <= 0) {
            return 0.0;
        }

        $result = $this->taxCalculationService->calculate(
            $tenantId,
            $taxGroupId,
            $taxableAmount,
            $postingDate !== null ? (string) $postingDate : null,
        );

        if ($result->isFailure()) {
            return 0.0;
        }

        $tax = $result->valueOrFail();

        return round((float) ($tax['tax_amount'] ?? 0), 4);
    }

    private function syncMetadataValues(
        string $entityType,
        int $entityId,
        int $tenantId,
        ?int $organizationUnitId,
        array $metadata,
    ): void {
        foreach ($metadata as $fieldKey => $value) {
            $definition = $this->resolveMetadataDefinition(
                $tenantId,
                $organizationUnitId,
                $entityType,
                (string) $fieldKey,
            );
            $existingValue = $this->findMetadataValue((int) $definition->id(), $entityType, $entityId);
            $payload = [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $organizationUnitId,
                'metadata_definition_id' => (int) $definition->id(),
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'value' => is_scalar($value) || $value === null ? (string) $value : json_encode($value),
            ];

            if ($existingValue instanceof DataRecord) {
                $this->metadataValueRepository->update((int) $existingValue->id(), $payload);

                continue;
            }

            $this->metadataValueRepository->create($this->withDefaultRowVersion($payload));
        }
    }

    private function resolveMetadataDefinition(
        int $tenantId,
        ?int $organizationUnitId,
        string $entityType,
        string $fieldKey,
    ): DataRecord {
        $definitions = $this->filterRecords($this->metadataDefinitionRepository->list([
            'tenant_id' => $tenantId,
            'entity_type' => $entityType,
            'field_key' => $fieldKey,
        ]));

        foreach ($definitions as $definition) {
            if (
                $organizationUnitId !== null
                && (int) $definition->get('organization_unit_id', 0) === $organizationUnitId
            ) {
                return $definition;
            }
        }

        if ($definitions !== []) {
            return $definitions[0];
        }

        return $this->metadataDefinitionRepository->create($this->withDefaultRowVersion([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'entity_type' => $entityType,
            'field_key' => $fieldKey,
            'label' => ucwords(str_replace('_', ' ', $fieldKey)),
            'data_type' => 'string',
            'is_required' => false,
            'is_active' => true,
            'display_order' => 1,
        ]));
    }

    private function findMetadataValue(int $metadataDefinitionId, string $entityType, int $entityId): ?DataRecord
    {
        $values = $this->filterRecords($this->metadataValueRepository->list([
            'metadata_definition_id' => $metadataDefinitionId,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
        ]));

        return $values[0] ?? null;
    }

    private function loadMetadataValues(string $entityType, int $entityId, int $tenantId): array
    {
        $values = $this->filterRecords($this->metadataValueRepository->list([
            'tenant_id' => $tenantId,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
        ]));
        if ($values === []) {
            return [];
        }

        $definitions = $this->filterRecords($this->metadataDefinitionRepository->list([
            'tenant_id' => $tenantId,
            'entity_type' => $entityType,
        ]));
        $definitionMap = [];
        foreach ($definitions as $definition) {
            $definitionMap[(int) $definition->id()] = (string) $definition->get('field_key');
        }

        $metadata = [];
        foreach ($values as $value) {
            $fieldKey = $definitionMap[(int) $value->get('metadata_definition_id', 0)] ?? null;
            if ($fieldKey === null) {
                continue;
            }
            $metadata[$fieldKey] = $value->get('value');
        }

        return $metadata;
    }

    private function nextLineNumber(iterable $records, string $field): int
    {
        $max = 0;
        foreach ($records as $record) {
            if (! $record instanceof DataRecord) {
                continue;
            }
            $max = max($max, (int) $record->get($field, 0));
        }

        return $max + 1;
    }

    private function normalizeRecords(iterable $records): array
    {
        $normalized = [];
        foreach ($records as $record) {
            if ($record instanceof DataRecord) {
                $normalized[] = $this->normalizeRecord($record);
            }
        }

        return $normalized;
    }

    private function filterRecords(iterable $records): array
    {
        $filtered = [];
        foreach ($records as $record) {
            if ($record instanceof DataRecord) {
                $filtered[] = $record;
            }
        }

        return $filtered;
    }

    private function normalizeRecord(DataRecord $record): array
    {
        return array_merge(['id' => $record->id()], $record->toArray());
    }

    private function withDefaultRowVersion(array $payload): array
    {
        if (! array_key_exists('row_version', $payload)) {
            $payload['row_version'] = 1;
        }

        return $payload;
    }

    private function failure(Throwable $exception): Result
    {
        return Result::failure(new Error(VehicleRentalErrorCode::INVALID_VALUE, $exception->getMessage()));
    }
}
