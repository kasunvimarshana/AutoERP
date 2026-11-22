<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\VehicleRental\Http\Controllers;

use Illuminate\Http\Response;
use Modules\VehicleRental\Http\Requests\RentalIncidentRequest;
use Modules\VehicleRental\Services\RentalIncidentAttachmentService;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class RentalIncidentAttachmentController
{
    public function store(RentalIncidentRequest $request, int $incident, RentalIncidentAttachmentService $service): Response
    {
        $service->attach($request->context(), $incident, $request->expectedVersion(), $request->file('file'));

        return response('', Response::HTTP_CREATED);
    }

    public function download(RentalIncidentRequest $request, int $incident, int $attachment, RentalIncidentAttachmentService $service): StreamedResponse
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
