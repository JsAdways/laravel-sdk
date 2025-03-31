<?php

use Illuminate\Support\Facades\Route;

Route::get('/docs-api', function () {
    return view('laravel-sdk::docs');
});
