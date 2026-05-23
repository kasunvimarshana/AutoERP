<?php

declare(strict_types=1);

namespace Modules\Configuration\Application\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\Configuration\Application\Actions\DeleteConfigurationRecordAction;
use Modules\Configuration\Application\Actions\FindConfigurationRecordAction;
use Modules\Configuration\Application\Actions\ListConfigurationRecordsAction;
use Modules\Configuration\Application\Actions\PersistConfigurationRecordAction;
use Modules\Configuration\Application\DTOs\CountryData;
use Modules\Configuration\Application\DTOs\CurrencyData;
use Modules\Configuration\Application\DTOs\LanguageData;
use Modules\Configuration\Application\DTOs\TimezoneData;
use Modules\Configuration\Application\Repositories\CountryRepositoryInterface;
use Modules\Configuration\Application\Repositories\CurrencyRepositoryInterface;
use Modules\Configuration\Application\Repositories\LanguageRepositoryInterface;
use Modules\Configuration\Application\Repositories\TimezoneRepositoryInterface;
use Modules\Configuration\Domain\Services\ConfigurationDomainService;

class ConfigurationService
{
    public function __construct(
        private readonly CountryRepositoryInterface $countries,
        private readonly CurrencyRepositoryInterface $currencies,
        private readonly LanguageRepositoryInterface $languages,
        private readonly TimezoneRepositoryInterface $timezones,
        private readonly ConfigurationDomainService $domain,
        private readonly ListConfigurationRecordsAction $listRecords,
        private readonly FindConfigurationRecordAction $findRecord,
        private readonly PersistConfigurationRecordAction $persistRecord,
        private readonly DeleteConfigurationRecordAction $deleteRecord,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function listCountries(array $filters = [], ?int $perPage = null): Collection|LengthAwarePaginator
    {
        return $this->listRecords->execute($this->countries, $filters, $perPage);
    }

    public function findCountry(int|string $id): Model
    {
        return $this->findRecord->execute($this->countries, 'Country', $id);
    }

    public function createCountry(CountryData $data): Model
    {
        return $this->persistRecord->create($this->countries, $this->countryAttributes($data));
    }

    public function updateCountry(int|string $id, CountryData $data): Model
    {
        return $this->persistRecord->update($this->countries, $this->findCountry($id), $this->countryAttributes($data));
    }

    public function deleteCountry(int|string $id): bool
    {
        return $this->deleteRecord->execute($this->countries, $this->findCountry($id));
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function listCurrencies(array $filters = [], ?int $perPage = null): Collection|LengthAwarePaginator
    {
        return $this->listRecords->execute($this->currencies, $filters, $perPage);
    }

    public function findCurrency(int|string $id): Model
    {
        return $this->findRecord->execute($this->currencies, 'Currency', $id);
    }

    public function createCurrency(CurrencyData $data): Model
    {
        return $this->persistRecord->create($this->currencies, $this->currencyAttributes($data));
    }

    public function updateCurrency(int|string $id, CurrencyData $data): Model
    {
        return $this->persistRecord->update($this->currencies, $this->findCurrency($id), $this->currencyAttributes($data));
    }

    public function deleteCurrency(int|string $id): bool
    {
        return $this->deleteRecord->execute($this->currencies, $this->findCurrency($id));
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function listLanguages(array $filters = [], ?int $perPage = null): Collection|LengthAwarePaginator
    {
        return $this->listRecords->execute($this->languages, $filters, $perPage);
    }

    public function findLanguage(int|string $id): Model
    {
        return $this->findRecord->execute($this->languages, 'Language', $id);
    }

    public function createLanguage(LanguageData $data): Model
    {
        return $this->persistRecord->create($this->languages, $this->languageAttributes($data));
    }

    public function updateLanguage(int|string $id, LanguageData $data): Model
    {
        return $this->persistRecord->update($this->languages, $this->findLanguage($id), $this->languageAttributes($data));
    }

    public function deleteLanguage(int|string $id): bool
    {
        return $this->deleteRecord->execute($this->languages, $this->findLanguage($id));
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function listTimezones(array $filters = [], ?int $perPage = null): Collection|LengthAwarePaginator
    {
        return $this->listRecords->execute($this->timezones, $filters, $perPage);
    }

    public function findTimezone(int|string $id): Model
    {
        return $this->findRecord->execute($this->timezones, 'Timezone', $id);
    }

    public function createTimezone(TimezoneData $data): Model
    {
        return $this->persistRecord->create($this->timezones, $this->timezoneAttributes($data));
    }

    public function updateTimezone(int|string $id, TimezoneData $data): Model
    {
        return $this->persistRecord->update($this->timezones, $this->findTimezone($id), $this->timezoneAttributes($data));
    }

    public function deleteTimezone(int|string $id): bool
    {
        return $this->deleteRecord->execute($this->timezones, $this->findTimezone($id));
    }

    /**
     * @return array<string, mixed>
     */
    private function countryAttributes(CountryData $data): array
    {
        return [
            'code' => $this->domain->normalizeCode($data->code),
            'name' => $this->domain->normalizeText($data->name),
            'phone_code' => $this->domain->normalizeText($data->phoneCode),
            'metadata' => $this->domain->normalizeMetadata($data->metadata),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function currencyAttributes(CurrencyData $data): array
    {
        $this->domain->assertCurrencyDecimalPlaces($data->decimalPlaces);

        return [
            'code' => $this->domain->normalizeCode($data->code),
            'name' => $this->domain->normalizeText($data->name),
            'symbol' => $this->domain->normalizeText($data->symbol),
            'decimal_places' => $data->decimalPlaces,
            'is_active' => $data->isActive,
            'metadata' => $this->domain->normalizeMetadata($data->metadata),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function languageAttributes(LanguageData $data): array
    {
        return [
            'code' => $this->domain->normalizeCode($data->code),
            'name' => $this->domain->normalizeText($data->name),
            'metadata' => $this->domain->normalizeMetadata($data->metadata),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function timezoneAttributes(TimezoneData $data): array
    {
        return [
            'name' => $this->domain->normalizeText($data->name),
            'offset' => $this->domain->normalizeText($data->offset),
            'metadata' => $this->domain->normalizeMetadata($data->metadata),
        ];
    }
}
