<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'name'    => 'Graviton E-commerce API',
        'version' => '1.0.0',
        'status'  => 'running',
        'docs'    => '/api/v1',
    ]);
});
