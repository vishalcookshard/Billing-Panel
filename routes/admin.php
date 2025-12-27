<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\PluginsController;

Route::middleware(['auth', 'can:access-admin', 'admin.audit'])->prefix('admin/api')->group(function () {
    Route::get('settings', [SettingsController::class, 'index'])->middleware('permission:settings.view');
    Route::get('settings/{key}', [SettingsController::class, 'show'])->middleware('permission:settings.view');
    Route::post('settings/{key}', [SettingsController::class, 'update'])->middleware('permission:settings.edit');

    Route::get('plugins', [PluginsController::class, 'index'])->middleware('permission:plugins.view');
    Route::post('plugins/{plugin}', [PluginsController::class, 'update'])->middleware('permission:plugins.edit');

    // Plugin configs
    Route::get('plugins/{plugin}/configs', [\App\Http\Controllers\Admin\PluginConfigController::class, 'index'])->middleware('permission:plugins.configs.view');
    Route::post('plugins/{plugin}/configs', [\App\Http\Controllers\Admin\PluginConfigController::class, 'update'])->middleware('permission:plugins.configs.edit');
});
