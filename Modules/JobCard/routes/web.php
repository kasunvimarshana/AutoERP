<?php

use Illuminate\Support\Facades\Route;
use Modules\JobCard\Http\Controllers\JobCardController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('jobcards', JobCardController::class)->names('jobcard');
});
