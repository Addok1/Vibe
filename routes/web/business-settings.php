<?php

use App\Http\Controllers\SettingController;
use Illuminate\Support\Facades\Route;


Route::middleware(['auth:sanctum', config('jetstream.auth_session')])->group(function () {

    //general settings
    Route::group(['prefix' => 'general-settings', 'middleware' => 'permission:general-settings-view'], function () {
        Route::get('/', [SettingController::class, 'generalSettings'])->name('settings.generalSettings');
        Route::post('/update', [SettingController::class, 'updateGeneralSettings'])->name('settings.updateGeneralSettings');
        Route::post('/update-status', [SettingController::class, 'updateStatus'])->name('settings.updateStatus');
    });

    Route::post('/users/import', [SettingController::class, 'import'])->name('users.import');

    //customization settings
    Route::group(['prefix' => 'customization-settings', 'middleware' => 'permission:customization-settings-view'], function () {
        Route::get('/', [SettingController::class, 'customizationSettings'])->name('settings.customizationSettings');
        Route::post('/update', [SettingController::class, 'updateCustomizationSettings'])->name('settings.updateCustomizationSettings');
        Route::post('/update-status', [SettingController::class, 'updateCustomizationStatus'])->name('settings.updateCustomizationStatus');
    });

    //Peak Zone Setting
    Route::group(['prefix' => 'peakzone-setting', 'middleware' => 'permission:peak-zone-settings-view'], function () {
        //     Route::get('/', [SettingController::class, 'peakZoneSettings'])->name('settings.peakZoneSettings');
        Route::post('/update', [SettingController::class, 'updatePeakZoneSettings'])->name('settings.updatePeakZoneSettings');
    });

    // transportRide Settings
    Route::group(['prefix' => 'transport-ride-settings', 'middleware' => 'permission:transport-ride-settings-view'], function () {
        Route::get('/', [SettingController::class, 'transportRideSettings'])->name('settings.transportRideSettings');
        Route::post('/update', [SettingController::class, 'updateTransportSettings'])->name('settings.updateTransportSettings');
        Route::post('/update-status', [SettingController::class, 'updateTransportStatus'])->name('settings.updateTransportStatus');
    });

    // transportRide Settings
    Route::group(['prefix' => 'bid-ride-settings', 'middleware' => 'permission:bid-ride-settings-view'], function () {
        Route::get('/', [SettingController::class, 'bidRideSettings'])->name('settings.bidRideSettings');
        Route::post('/update', [SettingController::class, 'updateBidSettings'])->name('settings.updateBidSettings');
    });

    // wallet Settings
    Route::group(['prefix' => 'wallet-settings', 'middleware' => 'permission:wallet-settings-view'], function () {
        Route::get('/', [SettingController::class, 'walletSettings'])->name('settings.walletSettings');
        Route::post('/update', [SettingController::class, 'updateWalletSettings'])->name('settings.updateWalletSettings');
    });

    // tip Settings
    Route::group(['prefix' => 'tip-settings', 'middleware' => 'permission:tip-settings-view'], function () {
        Route::get('/', [SettingController::class, 'tipSettings'])->name('settings.tipSettings');
        Route::post('/update', [SettingController::class, 'updateTipSettings'])->name('settings.updateTipSettings');
        Route::post('/update-status', [SettingController::class, 'updateTipStatus'])->name('settings.updateTipStatus');
    });

    // referral Settings
    Route::group(['prefix' => 'referral-settings', 'middleware' => 'permission:referral-settings-view'], function () {
        Route::get('/', [SettingController::class, 'referralSettings'])->name('settings.referralSettings');
        Route::post('/update', [SettingController::class, 'updateReferralSettings'])->name('settings.updateRefrerralSettings');
        Route::post('/toggle', [SettingController::class, 'updateReferralToggle']);
    });
    Route::group(['prefix' => 'driver-referral-settings', 'middleware' => 'permission:driver-referral-settings-view'], function () {
        Route::get('/', [SettingController::class, 'DriverReferralSettings'])->name('settings.driverIndex');
        Route::post('/update', [SettingController::class, 'updateDriverReferralSettings'])->name('settings.updateDriverRefrerralSettings');
        Route::post('/toggle', [SettingController::class, 'updateDriverReferralToggle']);
    });
    //referral Dashboard
    Route::group(['prefix' => 'referral-dashboard', 'middleware' => 'permission:referral-dashboard-view'], function () {
        Route::get('/', [SettingController::class, 'referralDashboard'])->name('settings.referralDashboard');
        Route::get('/fetch', [SettingController::class, 'referralDashboardData'])->name('settings.referralDashboardData');
    });
    //referral Translation

    Route::group(['prefix' =>  'referral-translation', 'middleware' => 'permission:referral-transaltion-view'], function () {
        Route::get('/', [SettingController::class, 'referralTranslation'])->name('referral.translation');
        Route::post('/update', [SettingController::class, 'updateReferralTranslation'])->name('referral.translation.update');

        Route::get('referral-condition', [SettingController::class, 'referralCondition']);
        Route::get('driver-referral-condition', [SettingController::class, 'driverReferralCondition']);
    });
});
