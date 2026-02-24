<?php

namespace Modules\Localisation\Application\UseCases;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Localisation\Domain\Contracts\LocalePreferenceRepositoryInterface;
use Modules\Localisation\Domain\Events\LocalePreferenceUpdated;

class UpdateLocalePreferenceUseCase
{
    public function __construct(
        private LocalePreferenceRepositoryInterface $preferenceRepo,
    ) {}

    public function execute(array $data): object
    {
        return DB::transaction(function () use ($data) {
            $preference = $this->preferenceRepo->upsert([
                'user_id'       => $data['user_id'],
                'tenant_id'     => $data['tenant_id'],
                'locale'        => $data['locale'],
                'timezone'      => $data['timezone'],
                'date_format'   => $data['date_format'] ?? 'Y-m-d',
                'number_format' => $data['number_format'] ?? '1,234.56',
            ]);

            Event::dispatch(new LocalePreferenceUpdated(
                $data['user_id'],
                $data['tenant_id'],
                $data['locale'],
                $data['timezone'],
            ));

            return $preference;
        });
    }
}
