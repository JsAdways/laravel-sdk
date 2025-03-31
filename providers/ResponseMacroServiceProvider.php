<?php

namespace Jsadways\LaravelSDK\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Response;

class ResponseMacroServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        /**
         * 請求成功，json 回傳格式
         *
         * @param array $data
         */
        Response::macro('success', function (array $data = []) {
            return Response::json([
                'status_code' => 200,
                'data' => $data,
            ],
                200
            );
        });

        /**
         * 客戶端資料發生錯誤，json 回傳格式
         *
         * @param string $message
         */
        Response::macro('fail', function (string $message = 'fail', ...$content) {
            return Response::json([
                'message' => $message,
                ...$content
            ],
                400
            );
        });

        /**
         * 使用者驗證失敗
         */
        Response::macro('unauthenticated', function () {
            return Response::json([
                'status_code' => 401,
                'message' => '使用者認證失效。'
            ],
                401
            );
        });

        /**
         * 服務端資發生錯誤，json 回傳格式
         *
         * @param string $message
         */
        Response::macro('error', function (string $message = 'error') {
            return Response::json([
                'status_code' => 500,
                'message' => $message,
            ],
                500
            );
        });
    }
}
