<?php

use Webkernel\CP\System\Http\Controllers\SettingsApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1/settings')->group(function () {
    Route::get('/', [SettingsApiController::class, 'index'])->name('settings.index');
    Route::get('/{dotKey}', [SettingsApiController::class, 'show'])->name('settings.show');
    Route::post('/{dotKey}', [SettingsApiController::class, 'update'])->name('settings.update');
    Route::post('/', [SettingsApiController::class, 'create'])->name('settings.create');
    Route::delete('/{dotKey}', [SettingsApiController::class, 'delete'])->name('settings.delete');
});
