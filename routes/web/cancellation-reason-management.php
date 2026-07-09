<?php

use App\Http\Controllers\CancellationController;
use Illuminate\Support\Facades\Route;


Route::middleware(['auth:sanctum', config('jetstream.auth_session')])->group(function () {


    //cancellation
    Route::group(['prefix' => 'cancellation', 'middleware' => 'permission:view-cancellation'], function () {
        Route::get('/', [CancellationController::class, 'index'])->name('cancellation.index');
        Route::middleware('remove_empty_query')->get('/list', [CancellationController::class, 'list'])->name('cancellation.list');
        Route::middleware(['permission:add-cancellation'])->get('/create', [CancellationController::class, 'create'])->name('cancellation.create');
        Route::post('/store', [CancellationController::class, 'store'])->name('cancellation.store');
        Route::middleware(['permission:edit-cancellation'])->get('/edit/{id}', [CancellationController::class, 'edit'])->name('cancellation.edit');
        Route::post('/update/{cancellationReason}', [CancellationController::class, 'update'])->name('cancellation.update');
        Route::post('/update-status', [CancellationController::class, 'updateStatus'])->name('cancellation.updateStatus');
        Route::delete('/delete/{cancellationReason}', [CancellationController::class, 'delete'])->name('cancellation.delete');
    });
});
