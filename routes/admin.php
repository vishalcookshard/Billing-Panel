<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\PluginsController;

Route::middleware(['auth', 'can:access-admin', 'admin.audit'])->prefix('admin/api')->group(function () {
    Route::get('settings', [SettingsController::class, 'index']);
    Route::get('settings/{key}', [SettingsController::class, 'show']);
    Route::post('settings/{key}', [SettingsController::class, 'update']);

    Route::get('plugins', [PluginsController::class, 'index']);
    Route::post('plugins/{plugin}', [PluginsController::class, 'update']);

    // Plugin configs
    Route::get('plugins/{plugin}/configs', [\App\Http\Controllers\Admin\PluginConfigController::class, 'index']);
    Route::post('plugins/{plugin}/configs', [\App\Http\Controllers\Admin\PluginConfigController::class, 'update']);
});
