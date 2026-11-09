<?php

use Illuminate\Support\Facades\Route;
use MrSwapan\Applinks\Http\Controllers\ApplinksController;

Route::middleware(['web'])->group(function () {
    Route::get('/assetlinks.json', [ApplinksController::class, 'assetlinks']);
    Route::get('/.well-known/assetlinks.json', [ApplinksController::class, 'assetlinks']);

    Route::get('/apple-app-site-association', [ApplinksController::class, 'apple']);
    Route::get('/.well-known/apple-app-site-association', [ApplinksController::class, 'apple']);

    Route::get('/get_app_link_parameters', [ApplinksController::class, 'fetch']);
    Route::get('/applinks_save_device_info', [ApplinksController::class, 'saveDeviceInfo']);
    Route::get('/js_editor', [ApplinksController::class, 'jsEditor']);

    $prefix = trim(rtrim(config('applinks.applinks_url_prefix', 'applinks/*'), '/*'), '/');
    if ($prefix === '') {
        $prefix = 'applinks';
    }

    Route::get('/' . $prefix . '/{any?}', [ApplinksController::class, 'redirect'])
        ->where('any', '.*');
});
