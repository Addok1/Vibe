<?php

use App\Http\Controllers\FaqController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', config('jetstream.auth_session')])->group(function () {

    //faq
    Route::group(['prefix' => 'faq', 'middleware' => 'permission:view-faq'], function () {
        Route::get('/', [FaqController::class, 'index'])->name('faq.index');
        Route::middleware('remove_empty_query')->get('/list', [FaqController::class, 'list'])->name('faq.list');
        Route::middleware(['permission:add-faq'])->get('/create', [FaqController::class, 'create'])->name('faq.create');
        Route::post('/store', [FaqController::class, 'store'])->name('faq.store');
        Route::middleware(['permission:edit-faq'])->get('/edit/{id}', [FaqController::class, 'edit'])->name('faq.edit');
        Route::post('/update/{faq}', [FaqController::class, 'update'])->name('faq.update');
        Route::post('/update-status', [FaqController::class, 'updateStatus'])->name('faq.updateStatus');
        Route::delete('/delete/{faq}', [FaqController::class, 'destroy'])->name('faq.delete');
    });
});
