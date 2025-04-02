<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Report the exception.
     */
    public function report(Throwable $e): void
    {
        $payload =  property_exists($e, 'payload') ? $e->payload : [];
        $error_info = [
            'message' => empty($e->getMessage()) ? 'Server Error' : $e->getMessage(),
            'exception' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'request' => request()?->all(),
            'payload' => $payload,
        ];

        Log::error(json_encode($error_info, JSON_UNESCAPED_UNICODE));
    }

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        // 覆寫 ValidationException 轉換為 CustomValidationException
        $this->renderable(function (ValidationException $e) {
            $error_message = $e->validator->errors()->getMessages();
            return Response::fail(current(reset($error_message)));
        });

        // 非預期錯誤
        $this->renderable(function (Throwable $e) {
            return Response::fail('發生無法定義之異常，請盡快聯絡IT部。');
        });
    }

    /**
     * 使用者驗證失效
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        return Response::unauthenticated();
    }
}
