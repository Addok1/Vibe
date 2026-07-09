<?php

use App\Http\Controllers\OnboardingScreenController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', config('jetstream.auth_session')])->group(function () {

    //onboarding screen
    Route::group(['prefix' => 'onboarding-screen', 'middleware' => 'permission:onboarding-screen-settings-view'], function () {
        Route::get('/', [OnboardingScreenController::class, 'index'])->name('onboardingscreen.index');
        Route::middleware('remove_empty_query')->get('/list', [OnboardingScreenController::class, 'list'])->name('onboardingscreen.list');

        Route::middleware(['permission:edit_onboarding'])->get('/edit/{id}', [OnboardingScreenController::class, 'edit'])->name('onboardingscreen.edit');
        Route::post('/update/{onboarding}', [OnboardingScreenController::class, 'update'])->name('onboardingscreen.update');
        Route::post('/update-status', [OnboardingScreenController::class, 'updateStatus'])->name('onboardingscreen.updateStatus');
    });
});
