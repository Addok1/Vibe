<?php

/*
|--------------------------------------------------------------------------
| Admin API Routes
|--------------------------------------------------------------------------
|
| These routes are prefixed with 'api/v1'.
| These routes use the root namespace 'App\Http\Controllers\Api\V1'.
|
 */
use App\Base\Constants\Auth\Role;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Owner\FleetController;
use App\Http\Controllers\Api\V1\Owner\FleetDriversController;
use App\Http\Controllers\Api\V1\Owner\OwnerController;

/**
 * These routes are prefixed with 'api/v1'.
 * These routes use the root namespace 'App\Http\Controllers\Api\V1\Driver'.
 * These routes use the middleware group 'auth'.
 */


Route::prefix('owner')
    ->middleware(['auth:sanctum', 'throttle:120,1'])
    ->group(function () {

        // Fleet Management
        Route::get('list-fleets', [FleetController::class, 'index']);
        Route::get('fleet/documents/needed', [FleetController::class, 'neededDocuments']);
        Route::get('list-drivers', [FleetController::class, 'listDrivers']);
        Route::post('assign-driver/{fleet}', [FleetController::class, 'assignDriver']);
        Route::post('add-fleet', [FleetController::class, 'storeFleet']);
        Route::post('update-fleet/{fleet}', [FleetController::class, 'updateFleet']);
        Route::post('delete-fleet/{fleet}', [FleetController::class, 'deleteFleet']);

        // Fleet Drivers
        Route::post('add-drivers', [FleetDriversController::class, 'addDriver']);
        Route::get('delete-driver/{driver}', [FleetDriversController::class, 'deleteDriver']);

        // Owner Dashboards
        Route::post('dashboard', [OwnerController::class, 'ownerDashboard']);
        Route::post('fleet-dashboard', [OwnerController::class, 'fleetDashboard']);
        Route::post('fleet-driver-dashboard', [OwnerController::class, 'fleetDriverDashboard']);
   
    });

