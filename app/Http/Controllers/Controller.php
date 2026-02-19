<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Traits\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;

/**
 * Base API Controller
 *
 * All module API controllers should extend this class
 * Provides common functionality and API response methods
 */
abstract class Controller
{
    use ApiResponse;
    use AuthorizesRequests;
    use ValidatesRequests;
}
