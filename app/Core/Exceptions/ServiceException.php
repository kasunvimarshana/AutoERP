<?php

namespace App\Core\Exceptions;

use Exception;

/**
 * Service Layer Exception
 *
 * Custom exception for service layer errors.
 * Ensures consistent exception propagation across services.
 */
class ServiceException extends Exception
{
    /**
     * ServiceException constructor.
     */
    public function __construct(string $message = '', int $code = 0, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Render the exception as an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function render($request)
    {
        return response()->json([
            'error' => true,
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
        ], 500);
    }
}
