<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Invoice print route (sample)
$__printMiddleware = [
    'web',
    (string) config('core.current_user.middleware_alias', 'current.user'),
    (string) config('core.current_tenant.middleware_alias', 'current.tenant'),
    (string) config('core.current_organization_unit.middleware_alias', 'current.organization-unit').':required',
    'tenant.feature:invoice',
];

Route::middleware($__printMiddleware)->group(function () {
    Route::get('/invoice/print/{id}', [\Modules\Invoice\Http\Controllers\InvoiceController::class, 'printView'])->name('invoice.print');

    Route::get('/invoice/pdf/{id}', function ($id) {
        $invoice = \Modules\Invoice\Models\Invoice::with('lines')->find($id);
        if ($invoice === null) {
            return response(view('invoice.notfound', ['id' => $id]), 404);
        }
        $html = view('invoice.print', ['invoice' => $invoice])->render();

        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return response($dompdf->output(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="invoice_'.$id.'.pdf"');
    })->name('invoice.pdf');
});

// Also expose plural routes used by the SPA for same-origin printing
Route::middleware($__printMiddleware)->group(function () {
    Route::get('/invoices/{id}/print', [\Modules\Invoice\Http\Controllers\InvoiceController::class, 'printView'])->name('invoices.print');

    Route::get('/invoices/{id}/pdf', function ($id) {
        $invoice = \Modules\Invoice\Models\Invoice::with('lines')->find($id);
        if ($invoice === null) {
            return response(view('invoice.notfound', ['id' => $id]), 404);
        }
        $html = view('invoice.print', ['invoice' => $invoice])->render();

        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return response($dompdf->output(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="invoice_'.$id.'.pdf"');
    })->name('invoices.pdf');
});

// Public signed print route (no auth) - validates signature and tenant param
Route::get('/public/invoices/{invoice}/print/{tenant}', [\Modules\Invoice\Http\Controllers\InvoiceController::class, 'publicPrint'])
    ->name('invoices.public.print')
    ->middleware('signed');

Route::get('/public/invoices/{invoice}/pdf/{tenant}', function (int $invoice, int $tenant, \Modules\Core\Contracts\TenantExecutionContextInterface $executionContext) {
    $model = $executionContext->runForTenant(
        $tenant,
        fn (): ?\Modules\Invoice\Models\Invoice => \Modules\Invoice\Models\Invoice::query()->with('lines')->find($invoice),
    );
    if ($model === null) {
        return response(view('invoice.notfound', ['id' => $invoice]), 404);
    }
    $html = view('invoice.print', ['invoice' => $model])->render();

    $dompdf = new \Dompdf\Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    return response($dompdf->output(), 200)
        ->header('Content-Type', 'application/pdf')
        ->header('Content-Disposition', 'inline; filename="invoice_'.$invoice.'.pdf"');
})->name('invoices.public.pdf')->middleware('signed');

// Development-only debug helpers to diagnose route/invoice issues
if (config('app.debug')) {
    Route::get('/_debug/invoices/first', function (\Modules\Core\Contracts\TenantExecutionContextInterface $executionContext) {
        $id = $executionContext->runAsControlPlane(
            fn () => \Modules\Invoice\Models\Invoice::query()->value('id'),
        );
        return response()->json(['first_id' => $id]);
    });

    Route::get('/_debug/invoices/{id}', function ($id, \Modules\Core\Contracts\TenantExecutionContextInterface $executionContext) {
        $exists = $executionContext->runAsControlPlane(
            fn (): bool => \Modules\Invoice\Models\Invoice::query()->where('id', $id)->exists(),
        );
        $routes = collect(\Route::getRoutes())->filter(function ($r) {
            $uri = method_exists($r, 'uri') ? $r->uri() : (property_exists($r, 'uri') ? $r->uri : null);
            $name = method_exists($r, 'getName') ? $r->getName() : null;
            return str_contains((string) $uri, 'invoice') || str_contains((string) $name, 'invoice');
        })->map(function ($r) {
            return [
                'uri' => method_exists($r, 'uri') ? $r->uri() : null,
                'name' => method_exists($r, 'getName') ? $r->getName() : null,
            ];
        })->values();

        return response()->json(['exists' => $exists, 'routes' => $routes]);
    });

    Route::get('/_debug/invoices/{id}/signed-print', function ($id, \Modules\Core\Contracts\TenantExecutionContextInterface $executionContext) {
        $invoice = $executionContext->runAsControlPlane(
            fn (): ?\Modules\Invoice\Models\Invoice => \Modules\Invoice\Models\Invoice::query()->where('id', $id)->first(),
        );
        if (! $invoice) return response()->json(['error' => 'not_found'], 404);
        $tenant = $invoice->tenant_id;
        $url = \Illuminate\Support\Facades\URL::temporarySignedRoute('invoices.public.print', now()->addMinutes(60), ['invoice' => $id, 'tenant' => $tenant]);
        $pdf = \Illuminate\Support\Facades\URL::temporarySignedRoute('invoices.public.pdf', now()->addMinutes(60), ['invoice' => $id, 'tenant' => $tenant]);
        return response()->json(['print_url' => $url, 'pdf_url' => $pdf]);
    });
}

Route::get('/{any}', function () {
    return view('welcome');
})->where('any', '^(?!api).*$');
