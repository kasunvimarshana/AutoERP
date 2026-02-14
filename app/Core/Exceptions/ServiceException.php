<?php

namespace App\Core\Exceptions;

use Exception;

class ServiceException extends Exception
{
    protected $code = 500;

    public function render($request)
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'code' => $this->getCode()
        ], $this->getCode());
    }
}
