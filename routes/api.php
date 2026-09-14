<?php

use Illuminate\Support\Facades\Route;

Route::get('/v1/health', function () {
    return response()->json([
        'success' => true,
        'message' => 'API backend operativa',
        'data' => [
            'service' => 'backend-homologacion',
            'status' => 'ok',
        ],
    ]);
});
