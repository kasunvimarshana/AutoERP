<?php

namespace Modules\Localisation\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Localisation\Application\UseCases\UpdateLocalePreferenceUseCase;
use Modules\Localisation\Domain\Contracts\LocalePreferenceRepositoryInterface;
use Modules\Localisation\Presentation\Requests\UpdateLocalePreferenceRequest;

class LocalePreferenceController extends Controller
{
    public function __construct(
        private LocalePreferenceRepositoryInterface $preferenceRepo,
        private UpdateLocalePreferenceUseCase       $updateUseCase,
    ) {}

    public function show(): JsonResponse
    {
        $preference = $this->preferenceRepo->findByUser(auth()->id());

        return response()->json($preference);
    }

    public function update(UpdateLocalePreferenceRequest $request): JsonResponse
    {
        $preference = $this->updateUseCase->execute(array_merge(
            $request->validated(),
            [
                'user_id'   => auth()->id(),
                'tenant_id' => auth()->user()?->tenant_id,
            ]
        ));

        return response()->json($preference);
    }
}
