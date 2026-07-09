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

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Request\EtaController;


Route::prefix('dispatcher')->group(function () {
    
    Route::post('request/eta', [EtaController::class, 'eta']); 
    Route::post('request/list_packages', [EtaController::class, 'listPackages']);

});


