<?php

use App\Http\Controllers\DispatcherAddonsController;
use Illuminate\Support\Facades\Route;


//dispatcher addons
Route::group(['prefix' => 'dispatcher-addons', 'middleware' => 'permission:dispatcher_addons'], function () {
    Route::get('/', [DispatcherAddonsController::class, 'index'])->name('dispatcherAddons.index');
    Route::post('/verfication-submit', [DispatcherAddonsController::class, 'verification_submit'])->name('dispatcherAddons.verfication-submit');
    Route::post('/dipathcer-files', [DispatcherAddonsController::class, 'dispatcher_files_uploads'])->name('dispatcherAddons.dispatcher_files_uploads');
});
