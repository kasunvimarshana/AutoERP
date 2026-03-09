<?php
namespace App\Exceptions;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $dontFlash = ['current_password','password','password_confirmation'];

    public function register(): void
    {
        $this->renderable(function (ModelNotFoundException $e) {
            return response()->json(['error' => 'Not Found', 'message' => $e->getMessage()], 404);
        });
        $this->renderable(function (ValidationException $e) {
            return response()->json(['error' => 'Validation Failed', 'errors' => $e->errors()], 422);
        });
        $this->renderable(function (\InvalidArgumentException $e) {
            return response()->json(['error' => 'Bad Request', 'message' => $e->getMessage()], 400);
        });
        $this->renderable(function (\RuntimeException $e) {
            return response()->json(['error' => 'Conflict', 'message' => $e->getMessage()], 409);
        });
        $this->renderable(function (HttpException $e) {
            return response()->json(['error' => $e->getMessage()], $e->getStatusCode());
        });
    }
}
