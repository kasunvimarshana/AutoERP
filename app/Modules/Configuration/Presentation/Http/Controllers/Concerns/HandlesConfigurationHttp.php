<?php

declare(strict_types=1);

namespace Modules\Configuration\Presentation\Http\Controllers\Concerns;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Configuration\Domain\Exceptions\ConfigurationRecordNotFoundException;

trait HandlesConfigurationHttp
{
    /**
     * @param  array<int, string>  $allowed
     * @return array<string, mixed>
     */
    protected function filters(Request $request, array $allowed): array
    {
        return collect($request->only($allowed))
            ->filter(fn (mixed $value): bool => $value !== null && $value !== '')
            ->all();
    }

    private function perPage(Request $request): ?int
    {
        return $request->filled('per_page') ? max(1, min((int) $request->integer('per_page'), 100)) : null;
    }

    private function notFound(ConfigurationRecordNotFoundException $exception): JsonResponse
    {
        return response()->json(['message' => $exception->getMessage()], 404);
    }
}
