<?php

declare(strict_types=1);

namespace Modules\Configuration\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Configuration\Application\Contracts\FindLanguagesServiceInterface;
use Modules\Configuration\Infrastructure\Http\Resources\LanguageResource;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class LanguageController extends Controller
{
    public function __construct(
        private readonly FindLanguagesServiceInterface $findLanguagesService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['code', 'name']);
        $perPage = (int) $request->query('per_page', '15');
        $page = (int) $request->query('page', '1');
        $sort = $request->query('sort');

        $languages = $this->findLanguagesService->list($filters, $perPage, $page, $sort);

        return LanguageResource::collection($languages)->response();
    }

    public function show(int $id): JsonResponse
    {
        $language = $this->findLanguagesService->find($id);

        if ($language === null) {
            throw new NotFoundHttpException('Language not found.');
        }

        return (new LanguageResource($language))->response();
    }
}
