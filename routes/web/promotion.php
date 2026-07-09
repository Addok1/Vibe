<?php

use App\Http\Controllers\PromotionController;
use Illuminate\Support\Facades\Route;


Route::middleware(['auth:sanctum', config('jetstream.auth_session')])->group(function () {

// promotion

Route::group(['prefix' => 'promotion/templates','middleware' => 'permission:view-promotion-templates'], function () {

    Route::get('/', [PromotionController::class, 'index'])->name('promotion.templates.index');

    Route::middleware('remove_empty_query')->get('/list', [PromotionController::class, 'list'])
        ->name('promotion.templates.list');

    Route::get('/create', [PromotionController::class, 'create'])->name('promotion.templates.create');

    Route::post('/store', [PromotionController::class, 'store'])->name('promotion.templates.store');

    Route::get('/edit/{id}', [PromotionController::class, 'edit'])->name('promotion.templates.edit');

    Route::post('/update/{template}', [PromotionController::class, 'update'])->name('promotion.templates.update');

    Route::delete('/delete/{template}', [PromotionController::class, 'destroy'])->name('promotion.templates.delete');
    
    Route::post('/{template}/toggle-active', [PromotionController::class,'toggleActive']);
});
});
