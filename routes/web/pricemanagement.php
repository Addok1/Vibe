<?php

use App\Http\Controllers\ServiceLocationController;
use App\Http\Controllers\RentalPackageTypeController;
use App\Http\Controllers\SetPriceController;
use App\Http\Controllers\FareFixController;
use App\Http\Controllers\VehicleTypeController;
use App\Http\Controllers\GoodsTypeController;
use App\Http\Controllers\ZoneController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', config('jetstream.auth_session')])->group(function () {

    // vehicle model
    Route::group(['prefix' => 'zones', 'middleware' => 'permission:view-zone'], function () {
        Route::get('/', [ZoneController::class, 'index'])->name('zone.index');
        Route::middleware(['permission:add-zone'])->get('/create', [ZoneController::class, 'create'])->name('zone.create');
        Route::post('/store', [ZoneController::class, 'store'])->name('zone.store');
        Route::get('/fetch', [ZoneController::class, 'fetch'])->name('zone.fetch'); //table fetch
        Route::middleware('remove_empty_query')->get('/list', [ZoneController::class, 'list'])->name('zone.list'); //service location list
        Route::middleware(['permission:edit-price'])->get('/edit/{id}', [ZoneController::class, 'edit'])->name('zone.edit');
        Route::post('/update/{zone}', [ZoneController::class, 'update'])->name('zone.update');
        Route::put('/isactive/{id}', [ZoneController::class, 'isActive']);
        Route::delete('/delete/{zone}', [ZoneController::class, 'destroy']);
        Route::post('/update-status', [ZoneController::class, 'updateStatus'])->name('zone.updateStatus');
        Route::middleware(['permission:view-zone-map'])->get('/map/{id}', [ZoneController::class, 'map'])->name('zone.map');
        Route::post('/peakzone-setting/update', [ZoneController::class, 'updateZoneFeature'])->name('peakzone.update');
    });


    // service locations
    Route::group(['prefix' => 'service-locations', 'middleware' => 'permission:service-location'], function () {
        Route::get('/', [ServiceLocationController::class, 'index'])->name('servicelocation.index');
        Route::get('/list', [ServiceLocationController::class, 'list'])->name('service-location-list');
        Route::middleware(['permission:add_service_Location'])->get('/create', [ServiceLocationController::class, 'create'])->name('servicelocation.create');
        Route::middleware(['permission:edit_service_Location'])->get('/edit/{id}', [ServiceLocationController::class, 'edit'])->name('servicelocation.edit');
        Route::post('/store', [ServiceLocationController::class, 'store'])->name('servicelocation.store');
        Route::post('/update/{location}', [ServiceLocationController::class, 'update'])->name('servicelocation.update');
        Route::post('/toggle/{location}', [ServiceLocationController::class, 'toggle'])->name('servicelocation.toggle');
        // Route::delete('/delete/{location}', [ServiceLocationController::class, 'delete'])->name('servicelocation.delete');
    });



    // rental package types
    Route::group(['prefix' => 'rental-package-types', 'middleware' => 'permission:rental-package'], function () {
        Route::get('/', [RentalPackageTypeController::class, 'index'])->name('rentalpackagetype.index');
        Route::middleware(['permission:add-rental-package'])->get('/create', [RentalPackageTypeController::class, 'create'])->name('rentalpackagetype.create');
        Route::post('/store', [RentalPackageTypeController::class, 'store'])->name('rentalpackagetype.store');
        Route::get('/list', [RentalPackageTypeController::class, 'list'])->name('rentalpackagetype.list');
        Route::middleware(['permission:edit-rental-package'])->get('/edit/{id}', [RentalPackageTypeController::class, 'edit'])->name('rentalpackagetype.edit');
        Route::post('/update/{packageType}', [RentalPackageTypeController::class, 'update'])->name('rentalpackagetype.update');
        Route::post('/update-status', [RentalPackageTypeController::class, 'updateStatus'])->name('rentalpackagetype.updateStatus');
        Route::delete('/delete/{packageType}', [RentalPackageTypeController::class, 'destroy'])->name('rentalpackagetype.delete');
    });



    // set prices
    Route::group(['prefix' => 'set-prices', 'middleware' => 'permission:vehicle-fare'], function () {
        Route::get('/', [SetPriceController::class, 'index'])->name('setprice.index');
        Route::middleware(['permission:add-price'])->get('/create', [SetPriceController::class, 'create'])->name('setprice.create');
        Route::get('/vehicle_types', [SetPriceController::class, 'fetchVehicleTypes'])->name('setprice.vehiclelist');
        Route::post('/store', [SetPriceController::class, 'store'])->name('setprice.store');
        Route::get('/list', [SetPriceController::class, 'list'])->name('setprice.list');
        Route::middleware(['permission:edit-price'])->get('/edit/{id}', [SetPriceController::class, 'edit'])->name('setprice.edit');
        Route::post('/update/{zoneTypePrice}', [SetPriceController::class, 'update'])->name('setprice.update');
        Route::delete('/delete/{id}', [SetPriceController::class, 'destroy'])->name('setprice.delete');
        Route::post('/update-status', [SetPriceController::class, 'updateStatus'])->name('setprice.updateStatus');
        //package-price    
        Route::middleware(['permission:add-package-price'])->get('/packages/{zoneType}', [SetPriceController::class, 'packageIndex'])->name('setprice.packageIndex');
        Route::get('/packages/list/{zoneTypePrice}', [SetPriceController::class, 'packageList'])->name('setprice.packageList');
        Route::middleware(['permission:add-package-price'])->get('/packages/create/{zoneTypePrice}', [SetPriceController::class, 'packageCreate'])->name('setprice.package-create');
        Route::post('/packages/store', [SetPriceController::class, 'packageStore'])->name('setprice.packageStore');
        Route::middleware(['permission:add-package-price'])->get('/packages/edit/{zoneTypePackage}', [SetPriceController::class, 'packageEdit'])->name('setprice.package-edit');
        Route::post('/packages/update/{zoneTypePackage}', [SetPriceController::class, 'updatePackage'])->name('setprice.package-update');
        Route::delete('/packages/delete/{zoneTypePackage}', [SetPriceController::class, 'destroyPackage'])->name('setprice.package-delete');
        Route::post('/packages/update-status', [SetPriceController::class, 'updatePackageStatus'])->name('setprice.updatePackageStatus');

        // Surge Price
        Route::middleware(['permission:zone-surge'])->get('/surge/{zoneType}', [SetPriceController::class, 'surge'])->name('zone.surge');
        Route::post('/surge/update/{zoneType}', [SetPriceController::class, 'updateSurge'])->name('zone.updateSurge');
    });

    // set prices
    Route::group(['prefix' => 'farefix', 'middleware' => 'permission:vehicle-fare'], function () {
        Route::middleware(['permission:add-price'])->get('/create/{zonetype}', [FareFixController::class, 'create'])->name('farefix.create');
        Route::post('/store', [FareFixController::class, 'store'])->name('farefix.store');
        Route::get('/list', [FareFixController::class, 'list'])->name('farefix.list');
        Route::middleware(['permission:edit-price'])->get('/edit/{id}', [FareFixController::class, 'edit'])->name('farefix.edit');
        Route::post('/update/{zoneTypePrice}', [FareFixController::class, 'update'])->name('farefix.update');
        Route::delete('/delete/{id}', [FareFixController::class, 'destroy'])->name('farefix.delete');
        Route::post('/update-status', [FareFixController::class, 'updateStatus'])->name('farefix.updateStatus');
        Route::get('/{zonetype}', [FareFixController::class, 'index'])->name('farefix.index');
    });

    // vehicle Type
    Route::group(['prefix' => 'vehicle_type', 'middleware' => 'permission:vehicle-types'], function () {
        Route::get('/', [VehicleTypeController::class, 'index'])->name('vehicletype.index');
        Route::middleware(['permission:add-vehicle-types'])->get('/create', [VehicleTypeController::class, 'create'])->name('vehicletype.create');
        Route::post('/store', [VehicleTypeController::class, 'store'])->name('vehicletype.store');
        Route::get('/list', [VehicleTypeController::class, 'list'])->name('vehicletype.list');
        Route::middleware(['permission:add-vehicle-types'])->get('/edit/{id}', [VehicleTypeController::class, 'edit'])->name('vehicletype.edit');
        Route::post('/update/{vehicle_type}', [VehicleTypeController::class, 'update'])->name('vehicletype.update');
        // Route::delete('/delete/{vehicle_type}', [VehicleTypeController::class, 'destroy'])->name('vehicletype.delete');
        Route::post('/update-status', [VehicleTypeController::class, 'updateStatus'])->name('vehicletype.updateStatus');
    });

    //goods type
    Route::group(['prefix' => 'goods-type', 'middleware' => 'permission:manage-goods-types'], function () {
        Route::get('/', [GoodsTypeController::class, 'index'])->name('goodstype.index');
        Route::middleware(['permission:add-goods-types'])->get('/create', [GoodsTypeController::class, 'create'])->name('goodstype.create');
        Route::post('/store', [GoodsTypeController::class, 'store'])->name('goodstype.store');
        Route::get('/list', [GoodsTypeController::class, 'list'])->name('goodstype.list');
        Route::middleware(['permission:edit-goods-types'])->get('/edit/{id}', [GoodsTypeController::class, 'edit'])->name('goodstype.edit');
        Route::post('/update/{goods_type}', [GoodsTypeController::class, 'update'])->name('goodstype.update');
        Route::delete('/delete/{goods_type}', [GoodsTypeController::class, 'destroy'])->name('goodstype.delete');
        Route::post('/update-status', [GoodsTypeController::class, 'updateStatus'])->name('goodstype.updateStatus');
    });
});
