<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\Auth\LoginController;
use App\Http\Controllers\Web\Auth\Registration\UserRegistrationController;
use App\Http\Controllers\Web\Auth\SocialAuthController;
use App\Http\Controllers\Web\User\WebUserLoginController;

// SPA Login Routes
Route::controller(LoginController::class)->group(function () {
    Route::post('user/login', [LoginController::class, 'loginSpaUser'])->name('spa-user-login');
    Route::post('admin-login', [LoginController::class, 'loginWebUsers'])->name('spa-admin-login');
    Route::post('owner-login', [LoginController::class, 'loginFleetowners'])->name('spa-owner-login');
    Route::post('dispatch-login', [LoginController::class, 'loginDispatchUsers'])->name('spa-dispatcher-login');
    Route::post('dispatch-pro-login', [LoginController::class, 'loginDispatchProUsers'])->name('spa-dispatcher-pro-login');
    Route::post('agent-login', [LoginController::class, 'loginAgentUsers'])->name('spa-agent-login');
    Route::post('franchise-login',[LoginController::class,'loginFranchiseUsers'])->name('spa-franchise-login');
});

// Frontend Login (Guest Only)
Route::middleware('guest')
    ->controller(WebUserLoginController::class)
    ->group(function () {
        Route::get('owner-login', [WebUserLoginController::class, 'Ownerindex'])
            ->name('owner-login');
    });

// User Registration
Route::controller(UserRegistrationController::class)->group(function () {
    Route::post('user/register', [UserRegistrationController::class, 'register'])
        ->name('user-register');
});

// Social login (web booking + mobile web fallback)
Route::controller(SocialAuthController::class)->group(function () {
    Route::get('social/{provider}/redirect', 'redirect')->name('social.redirect');
    Route::match(['get', 'post'], 'social/{provider}/callback', 'callback')->name('social.callback');
    Route::post('social/complete', 'complete')->name('social.complete');
    Route::post('social/mobile-otp-complete', 'completeWithMobileOtp')->name('social.mobile-otp-complete');
    Route::post('social/mobile-otp-verified', 'markMobileOtpVerified')->name('social.mobile-otp-verified');
});
