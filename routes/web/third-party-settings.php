<?php

use App\Http\Controllers\PaymentGatewayController;
use App\Http\Controllers\SmsGatewayController;
use App\Http\Controllers\FirebaseController;
use App\Http\Controllers\MailConfigurationController;
use App\Http\Controllers\RecaptchaController;
use App\Http\Controllers\MapSettingController;
use App\Http\Controllers\InvoiceConfigurationController;
use App\Http\Controllers\NotificationChannelController;
use App\Http\Controllers\AISetupController;
use App\Http\Controllers\SocialAuthSettingsController;
use Illuminate\Support\Facades\Route;


Route::middleware(['auth:sanctum', config('jetstream.auth_session')])->group(function () {

    // payment gateway
    Route::group(['prefix' => 'payment-gateway', 'middleware' => 'permission:payment-gateway-settings-view'], function () {
        Route::get('/', [PaymentGatewayController::class, 'index'])->name('paymentgateway.index');
        Route::post('/update', [PaymentGatewayController::class, 'update'])->name('paymentgateway.update');
        Route::post('/update-statuss', [PaymentGatewayController::class, 'updateStatus'])->name('paymentgateway.updateStatus');
    });

    // sms gateway
    Route::group(['prefix' => 'sms-gateway', 'middleware' => 'permission:sms-gateway-settings-view'], function () {
        Route::get('/', [SmsGatewayController::class, 'index'])->name('smsgateway.index');
        Route::post('/update', [SmsGatewayController::class, 'update']);
    });


    // firebase
    Route::group(['prefix' => 'firebase'], function () {
        Route::middleware(['permission:firebase-settings-view'])->get('/', [FirebaseController::class, 'index'])->name('firebase.index');
        Route::get('/get', [FirebaseController::class, 'get'])->name('firebase.get');
        Route::post('/update', [FirebaseController::class, 'update']);
    });
    // mail configuration
    Route::group(['prefix' => 'mail-configuration', 'middleware' => 'permission:mail-configuration-view'], function () {
        Route::get('/', [MailConfigurationController::class, 'index'])->name('mailconfiguration.index');
        Route::post('/update', [MailConfigurationController::class, 'update']);
        Route::get('/test', [MailConfigurationController::class, 'test'])->name('mailconfiguration.test');
        Route::post('/test/send', [MailConfigurationController::class, 'send'])->name('mailconfiguration.test.send');
        Route::post('/test/update', [MailConfigurationController::class, 'update'])->name('mailconfiguration.test.update');
    });

    // recaptcha
    Route::group(['prefix' => 'recaptcha', 'middleware' => 'permission:recaptcha-view'], function () {
        Route::get('/', [RecaptchaController::class, 'index'])->name('recaptcha.index');
        Route::post('/update', [RecaptchaController::class, 'update']);
    });

    // Map settings
    Route::group(['prefix' => 'map', 'middleware' => 'permission:geo-fencing'], function () {
        Route::middleware(['permission:heat-map'])->get('/heat_map', [MapsettingController::class, 'heatmap'])->name('map.heatmap');
        Route::middleware(['permission:gods-eye'])->get('/gods_eye', [MapsettingController::class, 'godseye'])->name('map.godseye');
    });

    // map show settings (separate page)
    Route::group(['prefix' => 'map-show', 'middleware' => 'permission:map-show-setting'], function () {
        Route::get('/', [MapsettingController::class, 'mapShow'])->name('mapshow.index');
        Route::post('/update', [MapsettingController::class, 'mapShowUpdate'])->name('mapshow.update');
    });

    //map settings
    Route::group(['prefix' => 'map-setting', 'middleware' => 'permission:map-settings-view'], function () {
        Route::get('/', [MapsettingController::class, 'index'])->name('mapsettings.index');
        Route::post('/update', [MapsettingController::class, 'update'])->name('mapsettings.update');
    });

     // osm map settings
    Route::group(['prefix' => 'osm-map-setting', 'middleware' => 'permission:osm-map-setting'], function () {
        Route::get('/', [MapsettingController::class, 'osmIndex'])->name('osmmapsettings.index');
        Route::post('/update', [MapsettingController::class, 'osmUpdate'])->name('osmmapsettings.update');
    });

    //invoice configuration
    Route::group(['prefix' => 'invoice-configuration', 'middleware' => 'permission:invoice-configuration-view'], function () {
        Route::get('/', [InvoiceConfigurationController::class, 'index'])->name('invoiceconfiguration.index');
        Route::post('/update', [InvoiceConfigurationController::class, 'update']);
    });

    // notification channel
    Route::group(['prefix' => 'notification-channel', 'middleware' => 'permission:notification-channel-view'], function () {
        Route::get('/', [NotificationChannelController::class, 'index'])->name('notificationchannel.index');
        Route::middleware('remove_empty_query')->get('/list', [NotificationChannelController::class, 'list'])->name('notificationchannel.list');
        Route::get('/edit/{id}', [NotificationChannelController::class, 'edit'])->name('notificationchannel.edit');
        Route::get('/user-invoice-edit/{id}', [NotificationChannelController::class, 'userInvoiceEdit'])->name('notificationchannel.userInvoiceEdit');
        Route::get('/driver-invoice-edit/{id}', [NotificationChannelController::class, 'driverInvoiceEdit'])->name('notificationchannel.driverInvoiceEdit');
        Route::post('/update/{notification}', [NotificationChannelController::class, 'update']);
        Route::post('/update-push-template/{notification}', [NotificationChannelController::class, 'updatePushTemplate']);
        Route::post('/update-status', [NotificationChannelController::class, 'updateStatus'])->name('notificationchannel.updateStatus');

        Route::get('/push-template/edit/{id}', [NotificationChannelController::class, 'editPushTemplate'])->name('notificationchannel.editPushTemplate');
    });

     // sms gateway
    Route::group(['prefix' => 'ai-setup', 'middleware' => 'permission:open-ai-setup'], function () {
        Route::get('/', [AISetupController::class, 'index'])->name('aiSetup.index');
        Route::post('/update', [AISetupController::class, 'update']);
    });

    // Social auth settings (Google/Facebook)
    Route::group(['prefix' => 'social-auth-settings', 'middleware' => 'permission:social-auth-settings'], function () {
        Route::get('/', [SocialAuthSettingsController::class, 'index'])->name('socialAuthSettings.index');
        Route::post('/update', [SocialAuthSettingsController::class, 'update']);
    });
});
