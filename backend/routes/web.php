<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Dokumentasi API interaktif (Swagger UI + spesifikasi OpenAPI 3.0).
Route::get('/docs', fn () => view('swagger'));

Route::get('/api/openapi.json', function () {
    return response()->file(public_path('openapi.json'), [
        'Content-Type' => 'application/json',
    ]);
});

// Tanpa route ini, membuka /api di browser mengembalikan 404 yang
// membingungkan. Pengunjung diarahkan ke dokumentasi, sementara klien
// yang meminta JSON mendapat ringkasan endpoint utama.
Route::get('/api', function (Request $request) {
    if ($request->wantsJson()) {
        return response()->json([
            'status' => 'success',
            'message' => 'Stok Pangan Cerdas API is running',
            'docs' => '/docs',
            'endpoints' => [
                'openapi_spec' => '/api/openapi.json',
                'ringkasan_publik' => '/api/ringkasan-publik',
                'login' => '/api/login',
            ],
        ]);
    }

    return redirect('/docs');
});
