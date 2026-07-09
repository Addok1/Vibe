<?php

use App\Http\Controllers\SosController;

use Illuminate\Support\Facades\Route;


Route::middleware(['auth:sanctum', config('jetstream.auth_session')])->group(function () {

    //emergency number
    Route::group(['prefix' => 'sos', 'middleware' => 'permission:view-sos'], function () {
        Route::get('/', [SosController::class, 'index'])->name('sos.index');
        Route::middleware('remove_empty_query')->get('/list', [SosController::class, 'list'])->name('sos.list');
        Route::middleware(['permission:add-sos'])->get('/create', [SosController::class, 'create'])->name('sos.create');
        Route::post('/store', [SosController::class, 'store'])->name('sos.store');
        Route::middleware(['permission:edit-sos'])->get('/edit/{id}', [SosController::class, 'edit'])->name('sos.edit');
        Route::post('/update/{sos}', [SosController::class, 'update'])->name('sos.update');
        Route::post('/update-status', [SosController::class, 'updateStatus'])->name('sos.updateStatus');
        Route::delete('/delete/{sos}', [SosController::class, 'destroy'])->name('sos.delete');
    });
});
