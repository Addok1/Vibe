<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\Password\PasswordResetController;
use App\Http\Controllers\Api\V1\Auth\Registration\UserRegistrationController;
use App\Http\Controllers\Api\V1\Auth\Registration\DriverSignupController;
use App\Http\Controllers\Api\V1\Auth\Registration\ReferralController;
use App\Http\Controllers\Api\V1\Auth\Registration\AdminRegistrationController;

Route::middleware('throttle:120,1')->group(function () {

    // OTP for login
    Route::post('mobile-otp', [LoginController::class, 'mobileOtp']);
    Route::post('validate-otp', [LoginController::class, 'validateSmsOtp']);

    /**
     * Login Routes
     */
    Route::post('user/login', [LoginController::class, 'loginUser']);
    Route::post('driver/login', [LoginController::class, 'loginDriver']);
    Route::post('user/social/{provider}', [LoginController::class, 'socialLoginOrSignupUser']);

    // Logout
    Route::post('logout', [LoginController::class, 'logout'])
        ->middleware(['auth:sanctum', 'throttle:10,1']);

    // Reset password (mobile check)
    Route::post('reset-password', [PasswordResetController::class, 'validateUserMobileIsExistForForgetPassword']);

    /**
     * Registration Routes
     */
    Route::group([], function () {

        // User Registration
        Route::post('user/register', [UserRegistrationController::class, 'register']);
        Route::post('user/validate-mobile', [UserRegistrationController::class, 'validateUserMobile']);
        Route::post('user/validate-mobile-for-login', [UserRegistrationController::class, 'validateUserMobileForLogin']);

        // Reset Password
        Route::post('user/update-password', [UserRegistrationController::class, 'updatePassword']);
        Route::post('driver/update-password', [DriverSignupController::class, 'updatePassword']);

        // Driver Registration
        Route::post('driver/register', [DriverSignupController::class, 'register']);
        Route::post('driver/validate-mobile', [DriverSignupController::class, 'validateDriverMobile']);
        Route::post('driver/validate-mobile-for-login', [DriverSignupController::class, 'validateDriverMobileForLogin']);

        // OTP for registration
        Route::post('user/register/send-otp', [UserRegistrationController::class, 'sendOTP']);

        // Owner Registration
        Route::post('owner/register', [DriverSignupController::class, 'ownerRegister']);

        // Referral Updates
        Route::post('update/user/referral', [ReferralController::class, 'updateUserReferral'])
            ->middleware(['auth:sanctum', 'throttle:10,1']);

        Route::post('update/driver/referral', [ReferralController::class, 'updateDriverReferral'])
            ->middleware(['auth:sanctum', 'throttle:10,1']);

        // Get Referral Code
        Route::get('get/referral', [ReferralController::class, 'index'])
            ->middleware(['auth:sanctum', 'throttle:10,1']);

        // Email OTP
        Route::post('send-mail-otp', [UserRegistrationController::class, 'sendMailOTP']);
        Route::post('validate-email-otp', [UserRegistrationController::class, 'validateEmailOTP']);

        // Validate registration OTP
        Route::post('user/register/validate-otp', [UserRegistrationController::class, 'validateOTP']);

        // Admin Registration
        Route::post('admin/register', [AdminRegistrationController::class, 'register']);
    });

    /**
     * Password Routes
     */
    Route::prefix('password')->group(function () {

        Route::post('forgot', [PasswordResetController::class, 'forgotPassword']);
        Route::post('validate-token', [PasswordResetController::class, 'validateToken']);
        Route::post('reset', [PasswordResetController::class, 'reset']);
    });

    // Additional routes (commented)
    // Route::post('kudi/{mobile}/{otp}/{code}', [LoginController::class, 'enable_kudi']);
});
