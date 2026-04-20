<?php

declare(strict_types=1);

namespace Modules\Tax\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Tax\Application\Contracts\RecordTransactionTaxesServiceInterface;
use Modules\Tax\Application\Contracts\ResolveTaxServiceInterface;
use Modules\Tax\Application\DTOs\RecordTransactionTaxesData;
use Modules\Tax\Domain\RepositoryInterfaces\TransactionTaxRepositoryInterface;

class RecordTransactionTaxesService extends BaseService implements RecordTransactionTaxesServiceInterface
{
    public function __construct(
        private readonly TransactionTaxRepositoryInterface $transactionTaxRepository,
        private readonly ResolveTaxServiceInterface $resolveTaxService,
    ) {
        parent::__construct($transactionTaxRepository);
    }

    protected function handle(array $data): array
    {
        $dto = RecordTransactionTaxesData::fromArray($data);

        $taxLines = $dto->tax_lines;
        if ($taxLines === null) {
            if ($dto->taxable_amount === null) {
                throw new \InvalidArgumentException('taxable_amount is required when tax_lines are omitted.');
            }

            $resolved = $this->resolveTaxService->execute([
                'tenant_id' => $dto->tenant_id,
                'taxable_amount' => $dto->taxable_amount,
                'tax_group_id' => $dto->tax_group_id,
                'product_category_id' => $dto->product_category_id,
                'party_type' => $dto->party_type,
                'region' => $dto->region,
                'transaction_date' => $dto->transaction_date,
            ]);

            $taxLines = $resolved['lines'];
        }

        $normalized = array_map(function (array $line) use ($dto): array {
            $taxAccountId = isset($line['tax_account_id']) ? (int) $line['tax_account_id'] : null;
            if ($taxAccountId === null) {
                $taxAccountId = $dto->default_tax_account_id;
            }

            if ($taxAccountId === null) {
                throw new \InvalidArgumentException('Each tax line requires tax_account_id or default_tax_account_id.');
            }

            return [
                'tax_rate_id' => (int) $line['tax_rate_id'],
                'taxable_amount' => $this->formatDecimal((string) $line['taxable_amount']),
                'tax_amount' => $this->formatDecimal((string) $line['tax_amount']),
                'tax_account_id' => $taxAccountId,
            ];
        }, $taxLines);

        $this->transactionTaxRepository->deleteByReference($dto->tenant_id, $dto->reference_type, $dto->reference_id);

        return $this->transactionTaxRepository->saveManyForReference(
            tenantId: $dto->tenant_id,
            referenceType: $dto->reference_type,
            referenceId: $dto->reference_id,
            taxLines: $normalized,
        );
    }

    private function formatDecimal(string $value): string
    {
        return number_format((float) $value, 6, '.', '');
    }
}
