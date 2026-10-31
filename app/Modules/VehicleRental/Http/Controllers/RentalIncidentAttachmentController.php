<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Controllers;

use Illuminate\Http\Response;
use Modules\VehicleRental\Http\Requests\AgreementRequest;
use Modules\VehicleRental\Services\RentalIncidentAttachmentService;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class RentalIncidentAttachmentController
{
    public function store(AgreementRequest $request, int $incident, RentalIncidentAttachmentService $service): Response
    {
        $request->validate(['file' => ['required', 'file']]);
        $service->attach($request->context(), $incident, $request->expectedVersion(), $request->file('file'));

        return response('', Response::HTTP_CREATED);
    }

    public function download(AgreementRequest $request, int $incident, int $attachment, RentalIncidentAttachmentService $service): StreamedResponse
    {
        $opened = $service->open($request->context(), $incident, $attachment);

        return response()->streamDownload(function () use ($opened): void {
            try {
                fpassthru($opened['stream']);
            } finally {
                fclose($opened['stream']);
            }
        }, $opened['attachment']->original_filename, [
            'Content-Type' => 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
