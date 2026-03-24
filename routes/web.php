<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Route::get('/health', function () {
//     return response()->json([
//         'status' => 'ok',
//         'services' => [
//             'database' => DB::connection()->getPdo() ? 'up' : 'down',
//             'cache'    => Cache::store()->ping() ? 'up' : 'down',
//             'queue'    => Queue::size() !== false ? 'up' : 'down',
//         ],
//     ]);
// });
