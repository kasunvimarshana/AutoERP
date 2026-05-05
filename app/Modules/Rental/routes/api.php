<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Rental\Infrastructure\Http\Controllers\AssetController;
use Modules\Rental\Infrastructure\Http\Controllers\RentalBookingController;
use Modules\Rental\Infrastructure\Http\Controllers\RentalRateCardController;

Route::prefix('rental')
    ->middleware(['auth.configured', 'resolve.tenant'])
    ->group(function (): void {

        // Asset management
        Route::get('assets', [AssetController::class, 'index'])
            ->name('rental.assets.index');
        Route::post('assets', [AssetController::class, 'store'])
            ->name('rental.assets.store');
        Route::get('assets/{asset}', [AssetController::class, 'show'])
            ->name('rental.assets.show');
        Route::put('assets/{asset}', [AssetController::class, 'update'])
            ->name('rental.assets.update');
        Route::delete('assets/{asset}', [AssetController::class, 'destroy'])
            ->name('rental.assets.destroy');

        // Rate cards
        Route::get('rate-cards', [RentalRateCardController::class, 'index'])
            ->name('rental.rate-cards.index');
        Route::post('rate-cards', [RentalRateCardController::class, 'store'])
            ->name('rental.rate-cards.store');
        Route::get('rate-cards/{rateCard}', [RentalRateCardController::class, 'show'])
            ->name('rental.rate-cards.show');
        Route::put('rate-cards/{rateCard}', [RentalRateCardController::class, 'update'])
            ->name('rental.rate-cards.update');
        Route::delete('rate-cards/{rateCard}', [RentalRateCardController::class, 'destroy'])
            ->name('rental.rate-cards.destroy');

        // Bookings
        Route::get('bookings', [RentalBookingController::class, 'index'])
            ->name('rental.bookings.index');
        Route::post('bookings', [RentalBookingController::class, 'store'])
            ->name('rental.bookings.store');
        Route::get('bookings/{booking}', [RentalBookingController::class, 'show'])
            ->name('rental.bookings.show');
        Route::post('bookings/{booking}/confirm', [RentalBookingController::class, 'confirm'])
            ->name('rental.bookings.confirm');
        Route::post('bookings/{booking}/cancel', [RentalBookingController::class, 'cancel'])
            ->name('rental.bookings.cancel');
    });
