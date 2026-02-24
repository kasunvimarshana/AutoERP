<?php

namespace Modules\Localisation\Application\UseCases;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Localisation\Domain\Contracts\LanguagePackRepositoryInterface;
use Modules\Localisation\Domain\Events\LanguagePackCreated;

class CreateLanguagePackUseCase
{
    public function __construct(
        private LanguagePackRepositoryInterface $languagePackRepo,
    ) {}

    public function execute(array $data): object
    {
        return DB::transaction(function () use ($data) {
            $pack = $this->languagePackRepo->create([
                'tenant_id' => $data['tenant_id'],
                'locale'    => $data['locale'],
                'name'      => $data['name'],
                'direction' => $data['direction'] ?? 'ltr',
                'strings'   => $data['strings'] ?? [],
                'is_active' => true,
            ]);

            Event::dispatch(new LanguagePackCreated(
                $pack->id,
                $pack->tenant_id,
                $pack->locale,
            ));

            return $pack;
        });
    }
}
