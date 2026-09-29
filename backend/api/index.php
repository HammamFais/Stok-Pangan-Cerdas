<?php

// Entry point Laravel untuk Vercel (runtime vercel-php).
//
// Fungsi serverless Vercel hanya boleh menulis ke /tmp, sedangkan Laravel
// perlu menulis cache view, session, dan log ke folder storage. Karena itu
// storage dipindah ke /tmp/storage sebelum Laravel dijalankan.
$storagePath = '/tmp/storage';

foreach (['framework/views', 'framework/cache/data', 'framework/sessions', 'logs'] as $dir) {
    if (! is_dir("{$storagePath}/{$dir}")) {
        @mkdir("{$storagePath}/{$dir}", 0777, true);
    }
}

putenv("APP_STORAGE_PATH={$storagePath}");
$_ENV['APP_STORAGE_PATH'] = $storagePath;

// Vercel memanggil file ini sebagai /api/index.php. Tanpa normalisasi ini,
// Laravel mengira semua route berawalan /api/index.php dan tidak ada yang cocok.
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = __DIR__.'/../public/index.php';

require __DIR__.'/../public/index.php';
