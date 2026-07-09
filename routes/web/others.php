<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\Common\CountryController;
use App\Http\Controllers\Web\Admin\LanguagesController;
use App\Http\Controllers\Web\Admin\PeakZoneController;
use App\Http\Controllers\Web\Admin\MobileAppSettingController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\PreferencesController;
use App\Http\Controllers\Web\Admin\AirportController;
use App\Http\Controllers\Web\Admin\SubscriptionController;
use App\Http\Controllers\RentalPackageTypeController;
use App\Http\Controllers\AISetupController;
/*
|--------------------------------------------------------------------------
| SPA Auth Routes
|--------------------------------------------------------------------------
|
| These routes are prefixed with '/'.
| These routes use the root namespace 'App\Http\Controllers\Web'.
|
 */


Route::middleware('auth:sanctum', config('jetstream.auth_session'),)->group(function () {

    Route::controller(CountryController::class)->group(function () {
        Route::group(['prefix' => 'country', 'middleware' => 'permission:manage-country'], function () {
            Route::get('/', [App\Http\Controllers\Web\Common\CountryController::class, 'index'])->name('countries.index');
            Route::get('/list', [App\Http\Controllers\Web\Common\CountryController::class, 'list'])->name('countries.list');
            Route::middleware(['permission:add-country'])->get('/create', [App\Http\Controllers\Web\Common\CountryController::class, 'create'])->name('countries.create');
            Route::post('/store', [App\Http\Controllers\Web\Common\CountryController::class, 'store'])->name('countries.store');
            Route::middleware(['permission:toggle_service_Location'])->post('/toggle_status/{country}', [App\Http\Controllers\Web\Common\CountryController::class, 'toggleStatus'])->name('countries.toggle');
            Route::middleware(['permission:edit-country'])->get('/{country}', [App\Http\Controllers\Web\Common\CountryController::class, 'edit'])->name('countries.edit');
            Route::post('/update/{country}', [App\Http\Controllers\Web\Common\CountryController::class, 'update'])->name('countries.update');
        });
    });

    Route::controller(LanguagesController::class)->group(function () {

        Route::group([
            'prefix' => 'languages',
            'middleware' => 'permission:languages'
        ], function () {

            Route::get('/', [LanguagesController::class, 'index'])
                ->name('languages.index');

            Route::middleware(['permission:add_languages'])
                ->get('/create', [LanguagesController::class, 'create'])
                ->name('languages.create');

            Route::get('/list', [LanguagesController::class, 'list'])
                ->name('languages.list');

            Route::middleware(['permission:browse_languages'])
                ->get('/browse/{id}', [LanguagesController::class, 'browse'])
                ->name('languages.browse');

            Route::get('load-translation/{id}', [LanguagesController::class, 'loadTranslation']);

            Route::post('/store', [LanguagesController::class, 'store'])
                ->name('languages.store');

            Route::post('auto-translate/{id}', [LanguagesController::class, 'autoTranslate']);

            Route::post('translate/update/{id}', [LanguagesController::class, 'updateTranslate']);

            Route::post('auto-translate-all/{id}', [LanguagesController::class, 'autoTranslateAll']);

            Route::put('/update/{language}', [LanguagesController::class, 'update'])
                ->name('language.update');

            Route::put('/status/{language}', [LanguagesController::class, 'status']);

            Route::middleware(['permission:delete_languages'])
                ->delete('/delete/{language}', [LanguagesController::class, 'delete']);

            Route::get('download-translation/{id}', [LanguagesController::class, 'downloadTranslation']);

            Route::post('default-set/{lang}', [LanguagesController::class, 'updateAppLocale']);
        });

        Route::get('current-languages', [LanguagesController::class, 'CurrenetLanguagelist'])
            ->name('current-languages');

        Route::get('current-locations', [LanguagesController::class, 'serviceLocationlist'])
            ->name('current-locations');

        Route::get('current-notifications', [LanguagesController::class, 'adminNotification'])
            ->name('current-notifications');

        Route::post('mark-notification-as-read', [LanguagesController::class, 'readNotification'])
            ->name('read-notifications');
    });



    Route::get('user/permissions', [PermissionController::class, 'userPermissions']);

    Route::namespace('Admin')->group(function () {
        Route::group(['prefix' => 'subscription', 'middleware' => 'permission:manage-subscription'], function () {
            Route::get('/', [SubscriptionController::class, 'index']);
            Route::middleware('remove_empty_query')->get('/list', [SubscriptionController::class, 'fetch']);
            Route::middleware(['permission:add-subscription'])->get('/create', [SubscriptionController::class, 'create']);
            Route::middleware(['permission:edit-subscription'])->get('/edit/{plan}', [SubscriptionController::class, 'getById']);
            Route::post('/store', [SubscriptionController::class, 'store']);
            Route::post('/update/{plan}', [SubscriptionController::class, 'update']);
            Route::post('/update-status/{plan}', [SubscriptionController::class, 'toggleStatus']);
            Route::middleware('remove_empty_query')->get('/driver-plan-list/{driver}', [SubscriptionController::class, 'driverSubscriptionList']);
            Route::post('/driver-plan-expire/{driver}', [SubscriptionController::class, 'expireSubsctiption']);
            Route::middleware(['permission:delete-subscription'])->delete('/delete/{plan}', [SubscriptionController::class, 'delete']);
        });

        Route::group(['prefix' => 'peak_zone', 'middleware' => 'permission:peak-zone-view'], function () {
            // prefix('zone')->group(function () {
            Route::get('/', [PeakZoneController::class, 'index']);
            Route::get('/fetch', [PeakZoneController::class, 'getAllZone']);
            Route::middleware(['permission:peak-zone-map-view'])->get('/map/{zone}', [PeakZoneController::class, 'zoneMapView']);

            Route::post('update_status/{peak_zones}', [PeakZoneController::class, 'updateStatus']);
            Route::delete('/delete/{peak_zones}', [PeakZoneController::class, 'destroy']);
        });

        Route::controller(AirportController::class)->group(function () {

            Route::group(['prefix' => 'airport', 'middleware' => 'permission:view-airport'], function () {
                Route::get('/', [AirportController::class, 'index']);
                Route::get('/fetch',  [AirportController::class, 'getAllAirports']);
                Route::get('/list',  [AirportController::class, 'list']);
                Route::middleware(['permission:Map-view-Airport'])->get('/map/{id}',  [AirportController::class, 'airportMapView']);
                Route::middleware(['permission:Add-Airport'])->get('/create',  [AirportController::class, 'create']);
                Route::middleware(['permission:Edit-airport'])->get('/edit/{id}',  [AirportController::class, 'getById']);
                Route::post('update/{airport}',  [AirportController::class, 'update']);
                Route::post('store',  [AirportController::class, 'store']);
                Route::get('/{id}',  [AirportController::class, 'getById']);
                Route::middleware(['permission:Delete-Airport'])->delete('/delete/{airport}',  [AirportController::class, 'delete']);
                Route::post('/update-status/{airport}',  [AirportController::class, 'toggleAirportStatus']);
            });
        });

        Route::controller(MobileAppSettingController::class)->group(function () {
            Route::group(['prefix' => 'app_modules'], function () {
                Route::middleware(['permission:app_modules_view'])->get('/', [MobileAppSettingController::class, 'index'])->name('app_module.index');
                Route::get('/list', [MobileAppSettingController::class, 'fetch'])->name('app_module.fetch');
                Route::get('/listVehicles', [MobileAppSettingController::class, 'fetchVehicleTypes'])->name('app_module.listVehicles');
                Route::middleware(['permission:add_app_modules'])->get('/create', [MobileAppSettingController::class, 'create'])->name('app_module.create');
                Route::post('/store', [MobileAppSettingController::class, 'store'])->name('app_module.store');
                Route::middleware(['permission:toggle_app_modules'])->post('/update-status/{setting}', [MobileAppSettingController::class, 'updateStatus'])->name('app_module.toggle');
                Route::middleware(['permission:edit_app_modules'])->get('/edit/{setting}', [MobileAppSettingController::class, 'getById'])->name('app_module.edit');
                Route::post('/update/{setting}', [MobileAppSettingController::class, 'update'])->name('app_module.update');
                Route::middleware(['permission:delete_app_modules'])->delete('/delete/{setting}', [MobileAppSettingController::class, 'delete']);
            });
        });

        Route::post('/generate-ai-text', [RentalPackageTypeController::class, 'generateAiText'])->name('generateAiText');
        Route::post('/generate-ai-landing-text', [AISetupController::class, 'generateAiLandingText'])->name('generateAiLandingText');



        Route::group(['prefix' => 'preferences'], function () {
            Route::middleware(['permission:preference_view'])->get('/', [PreferencesController::class, 'index'])->name('preferences.index');
            Route::middleware(['permission:preference_view'])->get('/list', [PreferencesController::class, 'list'])->name('preferences.list');
            Route::middleware(['permission:toggle_preference'])->post('/update-status/{preference}', [PreferencesController::class, 'updateStatus'])->name('preferences.toggle');
            Route::post('/', [PreferencesController::class, 'store'])->name('preferences.store');
            Route::post('/{preference}', [PreferencesController::class, 'update'])->name('preferences.update');
            Route::delete('/{preference}', [PreferencesController::class, 'destroy']);
        });
    });
});
